<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;
use App\Models\TicketNota;

class NuevoComentarioPublicoNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $nota;

    public function __construct(Ticket $ticket, TicketNota $nota)
    {
        $this->ticket = $ticket;
        $this->nota   = $nota;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;
        $nota   = $this->nota;

        return (new MailMessage)
            ->subject("Nuevo comentario en tu ticket #{$ticket->id} — {$ticket->area}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$nota->user->name} agregó un comentario en la bitácora de tu reporte.")
            ->line("Área: {$ticket->area}")
            ->line("Comentario: {$nota->nota}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Puedes responder o dar seguimiento desde el detalle del ticket.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'mensaje'   => 'Nuevo comentario de: ' . $this->nota->user->name,
            'area'      => $this->ticket->area,
        ];
    }
}
