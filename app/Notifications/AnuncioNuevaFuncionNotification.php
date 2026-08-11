<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Anuncio;

/**
 * Correo de "nueva función/cambio" que un admin redacta desde Admin ->
 * Anuncios (ver AnuncioController::store), dirigido solo a quienes tienen
 * acceso al módulo afectado (Anuncio::destinatarios()).
 */
class AnuncioNuevaFuncionNotification extends Notification
{
    use Queueable;

    public Anuncio $anuncio;

    public function __construct(Anuncio $anuncio)
    {
        $this->anuncio = $anuncio;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Novedades en ' . config('app.name') . ': ' . $this->anuncio->titulo)
            ->greeting("Hola {$notifiable->name},");

        if ($this->anuncio->modulo) {
            $mail->line("Hay una función nueva en **{$this->anuncio->modulo->nombre}**:");
        } else {
            $mail->line('Hay novedades en el sistema:');
        }

        // El mensaje puede traer varias líneas (ej. pasos de un mini
        // tutorial); ->line() no interpreta saltos de línea por sí solo,
        // así que se manda cada línea no vacía por separado para que se
        // vea igual de ordenado que como se escribió.
        foreach (preg_split('/\r\n|\r|\n/', trim($this->anuncio->mensaje)) as $linea) {
            if (trim($linea) !== '') {
                $mail->line($linea);
            }
        }

        $mail->action('Ver en el sistema', route('novedades.index'))
             ->line('Si tienes dudas sobre este cambio, contacta a Sistemas.');

        return $mail;
    }
}
