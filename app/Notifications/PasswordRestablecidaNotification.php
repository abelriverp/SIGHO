<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Se envía cuando un administrador le asigna una nueva contraseña a un
 * usuario YA EXISTENTE desde Usuarios -> Editar (distinta de
 * NuevoUsuarioCredencialesNotification, que es solo para altas nuevas —
 * el texto "tu cuenta fue creada" no aplicaba aquí y confundía).
 */
class PasswordRestablecidaNotification extends Notification
{
    use Queueable;

    protected string $passwordTemporal;

    public function __construct(string $passwordTemporal)
    {
        $this->passwordTemporal = $passwordTemporal;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Tu contraseña en ' . config('app.name') . ' fue restablecida')
            ->greeting("Hola {$notifiable->name},")
            ->line('Un administrador restableció la contraseña de tu cuenta. Estos son tus nuevos datos de acceso:')
            ->line("**Usuario:** {$notifiable->email}")
            ->line("**Contraseña temporal:** {$this->passwordTemporal}")
            ->action('Iniciar sesión', route('login'))
            ->line('Por seguridad, se te pedirá cambiar esta contraseña al iniciar sesión.')
            ->line('Si no esperabas este correo, contacta al administrador del sistema.');
    }
}
