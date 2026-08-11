<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

/**
 * El propio reportante la dispara manualmente cuando su ticket lleva
 * varios días abierto y no ve seguimiento (TicketController::recordatorio).
 * Distinta de TicketSlaVencidoNotification: esa es la escalación
 * automática a admin/gerente por incumplir el SLA; esta es un recordatorio
 * humano al departamento responsable, sin importar si el SLA formal ya
 * venció o no.
 */
class TicketRecordatorioNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $diasAbierto;

    public function __construct(Ticket $ticket, int $diasAbierto)
    {
        $this->ticket = $ticket;
        $this->diasAbierto = $diasAbierto;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject("Recordatorio: ticket #{$ticket->id} sigue abierto — {$ticket->departamento_destino} ({$ticket->prioridad})")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$ticket->user->name} envió un recordatorio: su ticket #{$ticket->id} lleva {$this->diasAbierto} días abierto sin que vea seguimiento.")
            ->line("Área: {$ticket->area}")
            ->line("Departamento: {$ticket->departamento_destino}")
            ->line("Prioridad: {$ticket->prioridad}")
            ->line("Descripción: {$ticket->descripcion}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Por favor dale seguimiento o actualiza al usuario sobre el estado de su reporte.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id'    => $this->ticket->id,
            'mensaje'      => "Recordatorio de {$this->ticket->user->name}: ticket #{$this->ticket->id} lleva {$this->diasAbierto} días abierto.",
            'area'         => $this->ticket->area,
        ];
    }
}
