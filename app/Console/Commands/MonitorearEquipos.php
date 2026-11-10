<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Departamento;
use App\Models\MonitoreoEvento;
use App\Models\Scopes\HotelScope;
use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use App\Services\Monitoreo\Pinger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Monitoreo por ping de los activos marcados "monitorear" (Fase 4).
 * Programado cada 5 minutos. Tras N fallas seguidas (config
 * app.monitoreo_fallas, default 3) marca el equipo caido, registra el
 * evento y abre UN ticket de prioridad Alta. Cuando vuelve a responder
 * agrega una nota a ese ticket (no lo resuelve: alguien debe explicar la causa).
 */
class MonitorearEquipos extends Command
{
    protected $signature = 'sigho:monitorear';

    protected $description = 'Hace ping a los equipos monitoreados y abre tickets cuando se caen';

    public function handle(Pinger $pinger): int
    {
        $activos = Asset::withoutGlobalScope(HotelScope::class)
            ->where('monitorear', true)->whereNotNull('ip_address')->where('status', '!=', 'Baja')
            ->get();

        if ($activos->isEmpty()) {
            $this->info('Sin equipos para monitorear.');
            return self::SUCCESS;
        }

        $resultados = $pinger->pingVarias($activos->pluck('ip_address')->all());
        $umbral = max(1, (int) config('app.monitoreo_fallas', 3));
        $ahora = now();
        $caidos = $recuperados = 0;

        foreach ($activos as $a) {
            $latencia = $resultados[$a->ip_address] ?? null;

            if ($latencia !== null) {
                if ($a->estado_monitoreo === 'caido') {
                    $this->recuperar($a, $ahora);
                    $recuperados++;
                }
                $a->forceFill([
                    'estado_monitoreo'    => 'activo',
                    'ultimo_ping_at'      => $ahora,
                    'latencia_ms'         => $latencia,
                    'fallas_consecutivas' => 0,
                    'caido_desde'         => null,
                ])->save();
                continue;
            }

            $fallas = min(255, $a->fallas_consecutivas + 1);
            $a->forceFill(['fallas_consecutivas' => $fallas, 'latencia_ms' => null])->save();

            if ($fallas >= $umbral && $a->estado_monitoreo !== 'caido') {
                $this->caer($a, $ahora);
                $caidos++;
            }
        }

        $this->info("Monitoreados: {$activos->count()} · nuevos caídos: {$caidos} · recuperados: {$recuperados}");

        return self::SUCCESS;
    }

    private function caer(Asset $a, $ahora): void
    {
        $ticket = $this->abrirTicket($a);

        $a->forceFill([
            'estado_monitoreo'    => 'caido',
            'caido_desde'         => $ahora,
            'ticket_monitoreo_id' => $ticket?->id,
        ])->save();

        MonitoreoEvento::create([
            'hotel_code'  => $a->hotel_code,
            'asset_id'    => $a->id,
            'tipo'        => 'caida',
            'ocurrido_at' => $ahora,
            'ticket_id'   => $ticket?->id,
        ]);

        $this->warn("CAÍDO: {$a->nomenclatura} ({$a->ip_address})" . ($ticket ? " → ticket #{$ticket->id}" : ''));
    }

    private function recuperar(Asset $a, $ahora): void
    {
        $minutos = $a->caido_desde ? (int) $a->caido_desde->diffInMinutes($ahora) : null;

        MonitoreoEvento::create([
            'hotel_code'   => $a->hotel_code,
            'asset_id'     => $a->id,
            'tipo'         => 'recuperacion',
            'ocurrido_at'  => $ahora,
            'duracion_min' => $minutos,
            'ticket_id'    => $a->ticket_monitoreo_id,
        ]);

        if ($a->ticket_monitoreo_id) {
            $ticket = Ticket::withoutGlobalScope(HotelScope::class)->find($a->ticket_monitoreo_id);
            if ($ticket) {
                TicketNota::create([
                    'ticket_id'     => $ticket->id,
                    'user_id'       => $ticket->user_id,
                    'nota'          => "Monitoreo: {$a->nomenclatura} volvió a responder al ping el {$ahora->format('d/m/Y H:i')}" . ($minutos !== null ? " tras {$minutos} min caído." : '.') . ' Registra la causa antes de resolver.',
                    'tipo'          => 'publica',
                    'es_automatica' => true,
                ]);
            }
        }

        $a->forceFill(['ticket_monitoreo_id' => null])->save();
        $this->info("RECUPERADO: {$a->nomenclatura}");
    }

    private function abrirTicket(Asset $a): ?Ticket
    {
        $autor = User::where('hotel_code', $a->hotel_code)->whereIn('role', ['admin', 'gerente'])->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->first()
            ?? User::where('role', 'admin')->first();
        if (! $autor) {
            return null;
        }

        $depto = config('app.monitoreo_departamento', 'Sistemas');
        if (! Departamento::where('nombre', $depto)->exists()) {
            $depto = Departamento::activos()->orderBy('nombre')->value('nombre') ?? $depto;
        }

        $ticket = Ticket::create([
            'hotel_code'            => $a->hotel_code,
            'user_id'               => $autor->id,
            'area'                  => 'Monitoreo automático',
            'departamento_destino'  => $depto,
            'descripcion_ubicacion' => $a->nomenclatura,
            'asset_id'              => $a->id,
            'ubicacion_id'          => $a->ubicacion_id,
            'descripcion'           => "[Monitoreo] {$a->nomenclatura} ({$a->tipo_equipo}, IP {$a->ip_address}) dejó de responder al ping. Revisar energía, cable/puerto del switch y el equipo.",
            'campos_extra'          => ['categoria_label' => 'Monitoreo de red', 'subcategoria_label' => 'Equipo sin respuesta', 'campos' => []],
            'prioridad'             => 'Alta',
            'status'                => 'Abierto',
        ]);

        $destinatarios = User::where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($w) => $w->where('hotel_code', $a->hotel_code)->where('role', 'tecnico')->where('departamento', $depto)))->get();
        Notification::send($destinatarios, new NuevoTicketNotification($ticket));

        return $ticket;
    }
}
