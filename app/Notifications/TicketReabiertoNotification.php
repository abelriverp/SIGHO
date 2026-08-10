<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

class TicketReabiertoNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $motivo;

    public function __construct(Ticket $ticket, string $motivo)
    {
        $this->ticket = $ticket;
        $this->motivo = $motivo;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject("Ticket #{$ticket->id} reabierto — {$ticket->departamento_destino} ({$ticket->prioridad})")
            ->greeting("Hola {$notifiable->name},")
            ->line("El usuario indicó que el problema NO quedó resuelto y reabrió el ticket #{$ticket->id}.")
            ->line("Reportado originalmente por: {$ticket->user->name}")
            ->line("Motivo de la reapertura: {$this->motivo}")
            ->line("Área: {$ticket->area}")
            ->line("Departamento: {$ticket->departamento_destino}")
            ->line("Prioridad: {$ticket->prioridad}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Por favor revisa nuevamente este reporte lo antes posible.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'mensaje'   => 'Ticket reabierto por: ' . $this->ticket->user->name,
            'area'      => $this->ticket->area,
        ];
    }
}
