<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;

class TicketReasignadoNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $departamentoAnterior;
    public $motivo;

    public function __construct(Ticket $ticket, string $departamentoAnterior, string $motivo)
    {
        $this->ticket = $ticket;
        $this->departamentoAnterior = $departamentoAnterior;
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
            ->subject("Ticket #{$ticket->id} reasignado a \"{$ticket->departamento_destino}\" ({$ticket->prioridad})")
            ->greeting("Hola {$notifiable->name},")
            ->line("El ticket #{$ticket->id} fue reasignado de \"{$this->departamentoAnterior}\" a \"{$ticket->departamento_destino}\".")
            ->line("Reportado originalmente por: {$ticket->user->name}")
            ->line("Motivo de la reasignación: {$this->motivo}")
            ->line("Área: {$ticket->area}")
            ->line("Prioridad: {$ticket->prioridad}")
            ->line("Descripción: {$ticket->descripcion}")
            ->action('Ver ticket', route('tickets.show', $ticket->id))
            ->line('Por favor atiende este reporte lo antes posible.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id'              => $this->ticket->id,
            'mensaje'                => "Ticket reasignado desde \"{$this->departamentoAnterior}\": " . $this->ticket->user->name,
            'area'                   => $this->ticket->area,
            'departamento_anterior'  => $this->departamentoAnterior,
        ];
    }
}
