<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Reporte mensual de TI en PDF para gerentes y admins (Fase 5). */
class ReporteMensualTINotification extends Notification
{
    use Queueable;

    public function __construct(public string $pdf, public string $archivo, public string $hotelNombre, public string $mesTexto, public array $resumen)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $r = $this->resumen;
        return (new MailMessage)
            ->subject("SIGHO · Reporte de TI {$this->mesTexto} · {$this->hotelNombre}")
            ->greeting("Reporte de TI de {$this->mesTexto}")
            ->line("Tickets: {$r['creados']} levantados, {$r['resueltos']} resueltos" . ($r['sla'] !== null ? ", {$r['sla']}% dentro de SLA" : '') . '.')
            ->line('Satisfacción: ' . ($r['csat'] !== null ? "{$r['csat']} / 5" : 'sin encuestas') . ' · Pendientes al cierre: ' . $r['backlog'] . '.')
            ->line('Disponibilidad de equipos monitoreados: ' . ($r['disponibilidad'] !== null ? "{$r['disponibilidad']}%" : 'sin monitoreo') . '.')
            ->line('Adjunto el reporte completo en PDF.')
            ->action('Ver tablero', route('direccion.index'))
            ->attachData($this->pdf, $this->archivo, ['mime' => 'application/pdf']);
    }
}
