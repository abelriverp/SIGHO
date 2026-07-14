<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

class NuevoTicketNotification extends Notification
{
    use Queueable;
    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject("Nuevo ticket #{$ticket->id} — {$ticket->departamento_destino} ({$ticket->prioridad})")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se creó un nuevo reporte asignado a \"{$ticket->departamento_destino}\".")
            ->line("Reportado por: {$ticket->user->name}")
            ->line("Área: {$ticket->area}")
            ->line("Prioridad: {$ticket->prioridad}")
            ->line("Descripción: {$ticket->descripcion}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Por favor atiende este reporte lo antes posible.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'mensaje'   => 'Nuevo ticket de: ' . $this->ticket->user->name,
            'area'      => $this->ticket->area,
        ];
    }
}
