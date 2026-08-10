<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\TicketNota;
use App\Models\User;
use App\Notifications\TicketSlaVencidoNotification;
use App\Services\SlaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Corre cada hora (ver routes/console.php) y busca tickets abiertos que ya
 * rebasaron su SLA (App\Services\SlaService) y todavía no fueron escalados
 * (sla_escalado_at null). Por cada uno: notifica a admin + gerente de esa
 * propiedad, deja una nota automática en la bitácora, registra en
 * AuditLog, y marca sla_escalado_at para no repetir el aviso en cada
 * corrida mientras el ticket siga sin atenderse.
 *
 * A propósito NO cambia el estado ni la prioridad del ticket: es solo una
 * alerta de supervisión (ver decisión de alcance v1 del motor de SLA).
 */
class EscalarTicketsSla extends Command
{
    protected $signature   = 'tickets:escalar-sla';
    protected $description = 'Notifica a admin/gerente los tickets abiertos que rebasaron su SLA y aún no fueron escalados';

    public function handle(SlaService $sla): void
    {
        $tickets = $sla->ticketsPendientesDeEscalar();

        foreach ($tickets as $ticketCompleto) {
            $horasResolucion = $sla->horasResolucion($ticketCompleto->hotel_code, $ticketCompleto->departamento_destino, $ticketCompleto->prioridad);

            $destinatarios = User::where(function ($q) use ($ticketCompleto) {
                $q->where('role', 'admin')
                  ->orWhere(function ($q2) use ($ticketCompleto) {
                      $q2->where('role', 'gerente')
                         ->where('hotel_code', $ticketCompleto->hotel_code);
                  });
            })->get();

            Notification::send($destinatarios, new TicketSlaVencidoNotification($ticketCompleto, $horasResolucion));

            TicketNota::create([
                'ticket_id'     => $ticketCompleto->id,
                'user_id'       => null, // sistema
                'nota'          => "⏱️ Este ticket superó el SLA de {$horasResolucion} horas para prioridad \"{$ticketCompleto->prioridad}\" en \"{$ticketCompleto->departamento_destino}\". Se notificó a dirección.",
                'tipo'          => 'publica',
                'es_automatica' => true,
            ]);

            AuditLog::registrar(
                'ticket.sla_vencido',
                "El ticket #{$ticketCompleto->id} (\"{$ticketCompleto->area}\", \"{$ticketCompleto->departamento_destino}\") superó su SLA de {$horasResolucion} horas. Se notificó a admin/gerente.",
                $ticketCompleto,
                null,
                ['sla_escalado_at' => now()->toDateTimeString(), 'horas_resolucion' => $horasResolucion]
            );

            $sla->marcarEscalado($ticketCompleto);
        }

        $this->info("⏱️ {$tickets->count()} ticket(s) escalado(s) por incumplir su SLA.");
    }
}
