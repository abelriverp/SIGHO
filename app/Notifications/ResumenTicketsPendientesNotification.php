<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/**
 * Resumen periódico (NO por ticket) para Gerente/Admin: días 15 y 30 de
 * cada mes (ver routes/console.php y
 * App\Console\Commands\TicketsResumenPendientes), un solo correo por
 * destinatario por propiedad con TODOS los tickets pendientes (Abierto/
 * Reabierto), marcando cuáles llevan DIAS_SIN_SEGUIMIENTO_UMBRAL días o
 * más sin actividad en la bitácora.
 *
 * A propósito distinta de TicketRecordatorioNotification (un correo por
 * CADA recordatorio manual) y TicketSlaVencidoNotification (un correo por
 * CADA ticket que vence su SLA): el usuario pidió explícitamente que no
 * se sature de correos individuales — esta es la vía de "panorama
 * completo" en batch.
 *
 * El correo usa una vista markdown PROPIA (resources/views/emails/tickets/
 * resumen-pendientes.blade.php) en vez de encadenar ->line(): con tablas
 * armadas a mano dentro de ->line() el correo salía como texto plano con
 * pipes literales sin convertir (probado y descartado — ver comentario en
 * esa vista). @component('mail::table') sí funciona porque parsea su
 * propio contenido markdown de forma aislada.
 */
class ResumenTicketsPendientesNotification extends Notification
{
    use Queueable;

    private const EMOJI_PRIORIDAD = [
        'Alta'  => '🔴',
        'Media' => '🟡',
        'Baja'  => '🟢',
    ];

    // Ancho de la "barra" de proporción sin seguimiento, en segmentos.
    private const SEGMENTOS_BARRA = 10;

    /** @var Collection Cada item: ['ticket' => Ticket, 'dias_abierto' => int, 'dias_sin_seguimiento' => int, 'sin_seguimiento' => bool] */
    public Collection $pendientes;
    public string $hotelCode;
    public string $hotelNombre;
    public int $umbralDias;

    public function __construct(Collection $pendientes, string $hotelCode, string $hotelNombre, int $umbralDias)
    {
        $this->pendientes  = $pendientes;
        $this->hotelCode   = $hotelCode;
        $this->hotelNombre = $hotelNombre;
        $this->umbralDias  = $umbralDias;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $total          = $this->pendientes->count();
        $sinSeguimiento = $this->pendientes->where('sin_seguimiento', true)->count();
        $porcentaje     = $total > 0 ? (int) round(($sinSeguimiento / $total) * 100) : 0;

        // Los más "abandonados" primero, para que lo más urgente se lea
        // sin tener que escanear todo el correo.
        $ordenados = $this->pendientes->sortByDesc('dias_sin_seguimiento');

        // La vista no hace lógica de negocio: recibe filas ya formateadas
        // (texto/emoji listos para imprimir), no objetos Ticket crudos.
        $filas = $ordenados->map(function (Collection $item) {
            $ticket = $item['ticket'];

            return [
                'numero'       => "#{$ticket->id}",
                'departamento' => $ticket->departamento_destino,
                'prioridad'    => (self::EMOJI_PRIORIDAD[$ticket->prioridad] ?? '⚪') . ' ' . $ticket->prioridad,
                'dias_abierto' => $item['dias_abierto'],
                'seguimiento'  => $item['sin_seguimiento']
                    ? "⚠️ {$item['dias_sin_seguimiento']} día(s)"
                    : "{$item['dias_sin_seguimiento']} día(s)",
                'reporto'      => $ticket->user->name ?? 'Usuario eliminado',
            ];
        });

        return (new MailMessage)
            ->subject("Resumen de tickets pendientes — {$this->hotelNombre} — {$total} pendiente(s)")
            ->markdown('emails.tickets.resumen-pendientes', [
                'notifiable'   => $notifiable,
                'hotelNombre'  => $this->hotelNombre,
                'hotelCode'    => $this->hotelCode,
                'total'        => $total,
                'sinSeguimiento' => $sinSeguimiento,
                'porcentaje'   => $porcentaje,
                'umbralDias'   => $this->umbralDias,
                'barra'        => $this->barraProporcion($porcentaje),
                'filas'        => $filas,
                'actionUrl'    => route('tickets.index'),
            ]);
    }

    // Barra de texto (emoji) con la proporción sin seguimiento — se ve
    // como una mini-gráfica en CUALQUIER cliente de correo (Gmail,
    // Outlook, Apple Mail, móvil) porque son puros caracteres Unicode, sin
    // depender de que el cliente soporte CSS/SVG/imágenes remotas.
    private function barraProporcion(int $porcentaje): string
    {
        $llenos = (int) round(($porcentaje / 100) * self::SEGMENTOS_BARRA);
        $llenos = max(0, min(self::SEGMENTOS_BARRA, $llenos));
        $vacios = self::SEGMENTOS_BARRA - $llenos;

        return str_repeat('🟥', $llenos) . str_repeat('⬜', $vacios);
    }

    public function toArray($notifiable)
    {
        return [
            'hotel_code'       => $this->hotelCode,
            'total_pendientes' => $this->pendientes->count(),
            'sin_seguimiento'  => $this->pendientes->where('sin_seguimiento', true)->count(),
            'mensaje'          => "Resumen de tickets pendientes de {$this->hotelNombre}: {$this->pendientes->count()} pendiente(s).",
        ];
    }
}
