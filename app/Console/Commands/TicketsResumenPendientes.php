<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ResumenTicketsPendientesNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Corre los días 15 y 30 de cada mes (ver routes/console.php). A petición
 * explícita del usuario: Gerente (y Admin) necesitan ver TODOS los tickets
 * pendientes de cada propiedad, pero SIN saturarse de correos — nada de un
 * aviso por cada ticket (eso ya lo hacen TicketRecordatorioNotification y
 * TicketSlaVencidoNotification). Aquí se manda UN solo correo por
 * destinatario por PROPIEDAD, con el resumen completo de esa propiedad.
 *
 * "Sin seguimiento" = no hay ninguna nota en la bitácora (de nadie) en los
 * últimos DIAS_SIN_SEGUIMIENTO_UMBRAL días. Mismo umbral que ya usa el
 * recordatorio manual del usuario (ver
 * TicketController::UMBRAL_RECORDATORIO_DIAS) para no inventar un segundo
 * criterio de "abandono" en el sistema.
 *
 * Admin recibe el resumen de TODAS las propiedades (un correo por
 * propiedad con pendientes, igual que Gerente recibe el de la suya) — es
 * el mismo criterio de "quién supervisa qué" que ya usan
 * TicketRecordatorioNotification/TicketSlaVencidoNotification (admin +
 * el rol de esa propiedad/departamento). De paso esto cubre una propiedad
 * que tenga pendientes pero NINGÚN Gerente asignado todavía: antes se
 * habría quedado sin avisar a nadie; ahora al menos Admin se entera.
 *
 * Corre sin usuario autenticado (consola): HotelScope no filtra nada en
 * ese caso (ver App\Models\Scopes\HotelScope), así que Ticket::query() ya
 * trae de todas las propiedades — por eso se filtra explícitamente por
 * hotel_code en cada iteración, propiedad por propiedad.
 */
class TicketsResumenPendientes extends Command
{
    // --solo-admin: para probar el contenido/formato del correo sin
    // avisarle a Gerente de verdad (a petición explícita del usuario:
    // "para estas pruebas que solo me llegue el correo para no molestar a
    // la gerente"). Ej.: php artisan tickets:resumen-pendientes --solo-admin
    protected $signature   = 'tickets:resumen-pendientes {--solo-admin : Enviar solo a Administradores, sin avisar a Gerente (para probar el correo)}';
    protected $description = 'Envía a Admin/Gerente UN correo resumen por propiedad (no por ticket) de los tickets pendientes sin seguimiento';

    private const DIAS_SIN_SEGUIMIENTO_UMBRAL = 2;

    public function handle(): void
    {
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No hay usuarios con rol Admin; si usas --solo-admin no habrá a quién enviarle.');
        }

        // Con --solo-admin, ningún Gerente entra en $destinatarios más
        // abajo (el ->get($hotelCode, collect()) siempre cae al default
        // vacío), así que solo reciben los Administradores.
        $gerentesPorPropiedad = $this->option('solo-admin')
            ? collect()
            : User::where('role', 'gerente')
                ->whereNotNull('hotel_code')
                ->get()
                ->groupBy(fn (User $u) => strtoupper($u->hotel_code));

        // Propiedades a procesar: cualquiera con al menos un ticket
        // pendiente, tenga o no Gerente asignado (Admin igual se entera).
        $hotelCodesConPendientes = Ticket::query()
            ->whereIn('status', ['Abierto', 'Reabierto'])
            ->distinct()
            ->pluck('hotel_code')
            ->filter()
            ->map(fn ($h) => strtoupper($h))
            ->unique();

        if ($hotelCodesConPendientes->isEmpty()) {
            $this->info('No hay tickets pendientes en ninguna propiedad; nada que enviar.');
            return;
        }

        if ($this->option('solo-admin')) {
            $this->comment('🧪 Modo prueba (--solo-admin): Gerente NO será notificado en esta corrida.');
        }

        $correosEnviados = 0;

        foreach ($hotelCodesConPendientes as $hotelCode) {
            $tickets = Ticket::query()
                ->where('hotel_code', $hotelCode)
                ->whereIn('status', ['Abierto', 'Reabierto'])
                ->with(['user', 'notas' => fn ($q) => $q->latest()])
                ->orderBy('created_at')
                ->get();

            if ($tickets->isEmpty()) {
                continue;
            }

            $destinatarios = $gerentesPorPropiedad->get($hotelCode, collect())
                ->concat($admins)
                ->unique('id');

            // Ni Gerente ni Admin configurados (caso raro, pero posible en
            // un entorno recién armado): no hay a quién mandarle.
            if ($destinatarios->isEmpty()) {
                continue;
            }

            $pendientes = $tickets->map(function (Ticket $ticket) {
                // Última actividad real: la nota más reciente si existe,
                // si no, la fecha de creación del ticket.
                // (int): Carbon 3 devuelve diffInDays() como float con
                // muchos decimales (ej. "32.981333907384") — para un
                // correo de lectura humana solo interesan días completos.
                $ultimaActividad    = $ticket->notas->first()?->created_at ?? $ticket->created_at;
                $diasSinSeguimiento = (int) $ultimaActividad->diffInDays(now());

                return collect([
                    'ticket'               => $ticket,
                    'dias_abierto'         => (int) $ticket->created_at->diffInDays(now()),
                    'dias_sin_seguimiento' => $diasSinSeguimiento,
                    'sin_seguimiento'      => $diasSinSeguimiento >= self::DIAS_SIN_SEGUIMIENTO_UMBRAL,
                ]);
            });

            $hotelNombre = Hotel::where('hotel_code', $hotelCode)->value('nombre') ?? $hotelCode;

            Notification::send(
                $destinatarios,
                new ResumenTicketsPendientesNotification($pendientes, $hotelCode, $hotelNombre, self::DIAS_SIN_SEGUIMIENTO_UMBRAL)
            );

            $correosEnviados += $destinatarios->count();

            $this->info("📋 {$hotelNombre} ({$hotelCode}): {$tickets->count()} pendiente(s) → " . $destinatarios->count() . ' destinatario(s) notificado(s).');
        }

        $this->info("✅ Resumen enviado. {$correosEnviados} correo(s) en total.");
    }
}
