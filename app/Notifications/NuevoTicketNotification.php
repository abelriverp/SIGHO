<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
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
        return ['database']; // Aquí puedes agregar 'mail' después si configuras el servidor
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'mensaje' => 'Nuevo ticket de: ' . $this->ticket->user->name,
            'asunto' => $this->ticket->asunto,
        ];
    }
}