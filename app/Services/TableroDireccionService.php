<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BajaActivo;
use App\Models\Cambio;
use App\Models\Contrato;
use App\Models\MonitoreoEvento;
use App\Models\Problema;
use App\Models\Scopes\HotelScope;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Indicadores de TI para direccion en un solo lugar (Fase 5). Reusa
 * DashboardMetricsService para tickets/SLA/CSAT y agrega activos,
 * monitoreo, contratos, problemas y cambios. Siempre filtra por la
 * propiedad indicada (sirve igual en la web que en el comando mensual).
 */
class TableroDireccionService
{
    public function __construct(private DashboardMetricsService $metricas, private VencimientosService $vencimientos)
    {
    }

    public function generar(User $user, string $hotel, Carbon $desde, Carbon $hasta): array
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->endOfDay();
        $h = fn ($modelo) => $modelo::withoutGlobalScope(HotelScope::class)->where('hotel_code', $hotel);

        $tickets = $this->metricas->metricasPeriodo($user, $hotel, $desde, $hasta);

        $resueltos = $h(Ticket::class)->whereBetween('fecha_resolucion', [$desde, $hasta])->get(['created_at', 'fecha_resolucion', 'prioridad']);
        $mttr = $resueltos->isEmpty() ? null : round($resueltos->avg(fn ($t) => $t->created_at->diffInMinutes($t->fecha_resolucion) / 60), 1);
        $mttrPorPrioridad = $resueltos->groupBy('prioridad')->map(fn ($g) => round($g->avg(fn ($t) => $t->created_at->diffInMinutes($t->fecha_resolucion) / 60), 1));

        $abiertos = $h(Ticket::class)->whereIn('status', ['Abierto', 'Reabierto'])->get(['created_at']);
        $backlog = [
            'menos_1_dia' => $abiertos->filter(fn ($t) => $t->created_at->gt(now()->subDay()))->count(),
            '1_a_3_dias'  => $abiertos->filter(fn ($t) => $t->created_at->between(now()->subDays(3), now()->subDay()))->count(),
            '3_a_7_dias'  => $abiertos->filter(fn ($t) => $t->created_at->between(now()->subDays(7), now()->subDays(3)))->count(),
            'mas_7_dias'  => $abiertos->filter(fn ($t) => $t->created_at->lt(now()->subDays(7)))->count(),
        ];

        $activosPorEstado = $h(Asset::class)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $valorActivos = (float) $h(Asset::class)->where('status', '!=', 'Baja')->sum('costo');

        $monitoreados = $h(Asset::class)->where('monitorear', true)->where('status', '!=', 'Baja')->count();
        $caidas = $h(MonitoreoEvento::class)->where('tipo', 'caida')->whereBetween('ocurrido_at', [$desde, $hasta])->count();
        $minCaido = (int) $h(MonitoreoEvento::class)->where('tipo', 'recuperacion')->whereBetween('ocurrido_at', [$desde, $hasta])->sum('duracion_min');
        $minPeriodo = max(1, $desde->diffInMinutes($hasta));
        $disponibilidad = $monitoreados ? round(100 - ($minCaido / ($minPeriodo * $monitoreados)) * 100, 2) : null;

        $gastoAnual = $h(Contrato::class)->where('activo', true)->where('moneda', 'MXN')->get()->sum(fn ($c) => match ($c->periodicidad_pago) {
            'mensual' => $c->costo * 12, 'trimestral' => $c->costo * 4, default => (float) $c->costo,
        });

        return [
            'hotel'            => $hotel,
            'desde'            => $desde,
            'hasta'            => $hasta,
            'tickets'          => $tickets,
            'mttr'             => $mttr,
            'mttrPorPrioridad' => $mttrPorPrioridad,
            'backlog'          => $backlog,
            'backlogTotal'     => $abiertos->count(),
            'activosPorEstado' => $activosPorEstado,
            'activosTotal'     => $activosPorEstado->sum(),
            'valorActivos'     => $valorActivos,
            'bajasPeriodo'     => $h(BajaActivo::class)->where('estado', 'aprobada')->whereBetween('revisado_at', [$desde, $hasta])->count(),
            'monitoreados'     => $monitoreados,
            'caidas'           => $caidas,
            'minutosCaido'     => $minCaido,
            'disponibilidad'   => $disponibilidad,
            'caidosAhora'      => $h(Asset::class)->where('estado_monitoreo', 'caido')->where('monitorear', true)->pluck('nomenclatura'),
            'gastoAnual'       => $gastoAnual,
            'vencimientos'     => $this->vencimientos->proximos($hotel, 90, false)->take(10),
            'problemasAbiertos'=> $h(Problema::class)->whereNotIn('estado', ['resuelto', 'cerrado'])->count(),
            'problemasResueltos'=> $h(Problema::class)->whereBetween('resuelto_at', [$desde, $hasta])->count(),
            'cambiosCompletados'=> $h(Cambio::class)->where('estado', 'completado')->whereBetween('completado_at', [$desde, $hasta])->count(),
            'cambiosFallidos'  => $h(Cambio::class)->where('estado', 'fallido')->whereBetween('completado_at', [$desde, $hasta])->count(),
            'cambiosProximos'  => $h(Cambio::class)->where('estado', 'aprobado')->where('ventana_inicio', '>=', now())->orderBy('ventana_inicio')->limit(5)->get(['id', 'titulo', 'ventana_inicio', 'riesgo']),
        ];
    }
}
