<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NuevoUsuarioCredencialesNotification extends Notification
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
            ->subject('Tu cuenta en ' . config('app.name') . ' ha sido creada')
            ->greeting("Hola {$notifiable->name},")
            ->line('Se creó una cuenta para ti en el sistema. Estos son tus datos de acceso:')
            ->line("**Usuario:** {$notifiable->email}")
            ->line("**Contraseña temporal:** {$this->passwordTemporal}")
            ->action('Iniciar sesión', route('login'))
            ->line('Por seguridad, se te pedirá cambiar esta contraseña al iniciar sesión por primera vez.')
            ->line('Si no esperabas este correo, contacta al administrador del sistema.');
    }
}
