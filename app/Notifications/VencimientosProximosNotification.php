<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Aviso de contratos, licencias y garantias por vencer (Fase 3).
 * Lo manda sigho:alertas-vencimiento solo en los dias de aviso
 * (dias_aviso del contrato, 30, 15, 7, 1 y el dia del vencimiento) para
 * no saturar con un correo diario.
 */
class VencimientosProximosNotification extends Notification
{
    use Queueable;

    public function __construct(public Collection $items, public string $hotelNombre)
    {
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject("SIGHO · {$this->items->count()} vencimiento(s) próximos en {$this->hotelNombre}")
            ->greeting('Vencimientos próximos')
            ->line('Estos contratos, licencias o garantías vencen pronto:');

        foreach ($this->items as $i) {
            $cuando = $i['dias'] === 0 ? 'vence HOY' : ($i['dias'] === 1 ? 'vence mañana' : "vence en {$i['dias']} días");
            $mail->line("• {$i['tipo']}: **{$i['nombre']}** — {$cuando} ({$i['fecha']->format('d/m/Y')})" . ($i['detalle'] ? " · {$i['detalle']}" : ''));
        }

        return $mail->action('Ver vencimientos', route('vencimientos.index'))
            ->line('Renueva, negocia o da de baja a tiempo.');
    }

    public function toArray($notifiable)
    {
        return [
            'tipo'    => 'vencimientos',
            'mensaje' => "{$this->items->count()} vencimiento(s) próximos",
            'url'     => route('vencimientos.index'),
        ];
    }
}
