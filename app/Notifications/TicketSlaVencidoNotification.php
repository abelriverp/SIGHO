<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

/**
 * Escalamiento automático: se dispara una sola vez por ticket (ver
 * Ticket::sla_escalado_at y App\Console\Commands\EscalarTicketsSla) cuando
 * un ticket abierto rebasa las horas de resolución que le tocaban según su
 * prioridad/departamento (App\Services\SlaService). Va dirigida a
 * admin/gerente, no al técnico (que ya fue notificado al crearse/reasignarse
 * el ticket): esto es una alerta de supervisión, no un recordatorio más.
 */
class TicketSlaVencidoNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $horasResolucion;

    public function __construct(Ticket $ticket, int $horasResolucion)
    {
        $this->ticket          = $ticket;
        $this->horasResolucion = $horasResolucion;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject("⏱️ SLA vencido: ticket #{$ticket->id} (\"{$ticket->departamento_destino}\", {$ticket->prioridad})")
            ->greeting("Hola {$notifiable->name},")
            ->line("El ticket #{$ticket->id} lleva abierto más de {$this->horasResolucion} horas (el SLA para prioridad \"{$ticket->prioridad}\" en \"{$ticket->departamento_destino}\") sin resolverse.")
            ->line("Reportado por: {$ticket->user->name}")
            ->line("Área: {$ticket->area}")
            ->line("Descripción: {$ticket->descripcion}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Este aviso se envía a dirección porque el equipo asignado no lo atendió dentro del tiempo esperado.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id'         => $this->ticket->id,
            'mensaje'           => "SLA vencido en \"{$this->ticket->departamento_destino}\": ticket #{$this->ticket->id} ({$this->ticket->prioridad})",
            'area'              => $this->ticket->area,
            'horas_resolucion'  => $this->horasResolucion,
        ];
    }
}
