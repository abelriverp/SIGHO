<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\ReporteMensualTINotification;
use App\Services\TableroDireccionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * El dia 1 de cada mes manda a gerentes y admins de cada propiedad el
 * reporte de TI del mes anterior en PDF (Fase 5).
 */
class ReporteMensualTI extends Command
{
    protected $signature = 'sigho:reporte-mensual {--mes= : AAAA-MM, por defecto el mes anterior}';

    protected $description = 'Envía por correo el reporte mensual de TI en PDF';

    public function handle(TableroDireccionService $servicio): int
    {
        $mes = $this->option('mes') ?: now()->subMonthNoOverflow()->format('Y-m');
        $desde = Carbon::createFromFormat('Y-m-d', $mes . '-01')->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();
        $mesTexto = $desde->locale('es')->translatedFormat('F Y');

        foreach (Hotel::pluck('hotel_code') as $hotel) {
            $destinatarios = User::where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($w) => $w->where('role', 'gerente')->where('hotel_code', $hotel)))->get();
            $autor = $destinatarios->firstWhere('role', 'admin') ?? $destinatarios->first();
            if (! $autor) {
                continue;
            }

            $t = $servicio->generar($autor, $hotel, $desde, $hasta);
            $nombre = Hotel::nombrePara($hotel);
            $pdf = Pdf::loadView('direccion.pdf', ['t' => $t, 'mes' => $mes, 'hotelNombre' => $nombre, 'generadoPor' => 'SIGHO (automático)'])->setPaper('letter')->output();

            Notification::send($destinatarios, new ReporteMensualTINotification($pdf, "Reporte_TI_{$hotel}_{$mes}.pdf", $nombre, $mesTexto, [
                'creados'        => $t['tickets']['creados'],
                'resueltos'      => $t['tickets']['resueltos'],
                'sla'            => $t['tickets']['slaPorcentaje'],
                'csat'           => $t['tickets']['calidad']['csatPromedio'],
                'backlog'        => $t['backlogTotal'],
                'disponibilidad' => $t['disponibilidad'],
            ]));
            $this->line("{$hotel}: reporte {$mes} enviado a {$destinatarios->count()} destinatario(s).");
        }

        return self::SUCCESS;
    }
}
