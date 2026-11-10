<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\TicketRecurrente;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Genera los tickets de mantenimiento preventivo que tocan hoy (Fase 2).
 * Programado a diario 06:00 en routes/console.php. Corre sin usuario
 * autenticado, asi que HotelScope no filtra: cada ticket toma el
 * hotel_code de su plantilla.
 */
class GenerarTicketsRecurrentes extends Command
{
    protected $signature = 'tickets:generar-recurrentes {--fecha= : Fecha a evaluar (AAAA-MM-DD), por defecto hoy}';

    protected $description = 'Crea los tickets de mantenimiento preventivo programados para hoy';

    public function handle(): int
    {
        $hoy = $this->option('fecha') ? Carbon::parse($this->option('fecha'))->startOfDay() : Carbon::today();
        $generados = 0;

        $pendientes = TicketRecurrente::with(['asset', 'ubicacion', 'creador'])
            ->where('activo', true)
            ->whereDate('proxima_fecha', '<=', $hoy->toDateString())
            ->get();

        foreach ($pendientes as $r) {
            if (! $r->creador) {
                $this->warn("Plantilla #{$r->id} sin creador; se omite.");
                continue;
            }

            $ticket = Ticket::create([
                'hotel_code'            => $r->hotel_code,
                'user_id'               => $r->creado_por,
                'area'                  => 'Mantenimiento preventivo',
                'departamento_destino'  => $r->departamento_destino,
                'descripcion_ubicacion' => $r->asset?->nomenclatura ?? $r->ubicacion?->etiqueta,
                'asset_id'              => $r->asset_id,
                'ubicacion_id'          => $r->ubicacion_id ?? $r->asset?->ubicacion_id,
                'descripcion'           => "[Preventivo] {$r->titulo}\n\n{$r->descripcion}",
                'campos_extra'          => ['categoria_label' => 'Mantenimiento preventivo', 'subcategoria_label' => $r->titulo, 'campos' => []],
                'prioridad'             => $r->prioridad,
                'status'                => 'Abierto',
            ]);

            TicketNota::create([
                'ticket_id'     => $ticket->id,
                'user_id'       => $r->creado_por,
                'nota'          => "Ticket generado automáticamente por el mantenimiento programado \"{$r->titulo}\" ({$r->frecuencia}).",
                'tipo'          => 'interna',
                'es_automatica' => true,
            ]);

            // Siguiente fecha: se avanza desde la fecha programada (no desde
            // hoy) para no perder el calendario si el comando dejo de correr.
            $siguiente = $r->siguienteFecha($r->proxima_fecha);
            while ($siguiente->lte($hoy)) {
                $siguiente = $r->siguienteFecha($siguiente);
            }
            $r->update(['proxima_fecha' => $siguiente, 'ultimo_ticket_id' => $ticket->id]);

            $destinatarios = User::where('hotel_code', $r->hotel_code)
                ->where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($q2) => $q2->where('role', 'tecnico')->where('departamento', $r->departamento_destino)))
                ->get();
            Notification::send($destinatarios, new NuevoTicketNotification($ticket));

            $generados++;
            $this->line("Ticket #{$ticket->id}: {$r->titulo} (siguiente: {$siguiente->format('Y-m-d')})");
        }

        $this->info("Tickets preventivos generados: {$generados}");

        return self::SUCCESS;
    }
}
