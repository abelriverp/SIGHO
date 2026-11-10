<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\VencimientosProximosNotification;
use App\Services\VencimientosService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa por correo a gerentes y admins de cada propiedad lo que vence
 * (Fase 3). Programado a diario 07:00; solo manda correo en los dias de
 * aviso para no saturar.
 */
class AlertasVencimiento extends Command
{
    protected $signature = 'sigho:alertas-vencimiento {--todos : Manda todo lo que vence en 30 dias, sin filtrar por dias de aviso}';

    protected $description = 'Envía avisos de contratos, licencias y garantías por vencer';

    private const DIAS_AVISO = [30, 15, 7, 1, 0];

    public function handle(VencimientosService $servicio): int
    {
        $enviados = 0;

        foreach (Hotel::pluck('hotel_code') as $hotelCode) {
            $items = $servicio->proximos($hotelCode, 60, false)->filter(function ($i) {
                if ($this->option('todos')) {
                    return $i['dias'] <= 30;
                }
                return in_array($i['dias'], self::DIAS_AVISO, true) || $i['dias'] === (int) $i['aviso'];
            })->values();

            if ($items->isEmpty()) {
                continue;
            }

            $destinatarios = User::where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($w) => $w->where('role', 'gerente')->where('hotel_code', $hotelCode)))->get();
            if ($destinatarios->isEmpty()) {
                continue;
            }

            Notification::send($destinatarios, new VencimientosProximosNotification($items, Hotel::nombrePara($hotelCode)));
            $enviados++;
            $this->line("{$hotelCode}: {$items->count()} aviso(s) a {$destinatarios->count()} destinatario(s).");
        }

        $this->info("Propiedades notificadas: {$enviados}");

        return self::SUCCESS;
    }
}
