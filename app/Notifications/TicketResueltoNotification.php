<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

class TicketResueltoNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $token;

    public function __construct(Ticket $ticket, string $token)
    {
        $this->ticket = $ticket;
        $this->token  = $token;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject("Tu ticket #{$ticket->id} fue resuelto — {$ticket->area}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu reporte fue marcado como resuelto por el equipo de \"{$ticket->departamento_destino}\".")
            ->line("Área: {$ticket->area}")
            ->line("Descripción original: {$ticket->descripcion}")
            ->line("Solución aplicada: {$ticket->notas_admin}")
            ->line("Token de resolución: {$this->token}")
            ->action('Ver ticket y confirmar', route('tickets.show', $ticket->id))
            ->line('Por favor confirma si el problema quedó solucionado. Si no es así, puedes reabrir el ticket desde el detalle.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'mensaje'   => 'Tu ticket fue marcado como resuelto: ' . $this->ticket->area,
            'area'      => $this->ticket->area,
        ];
    }
}
