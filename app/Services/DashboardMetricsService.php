<?php

namespace App\Services;

use App\Models\Operador;
use App\Models\Scopes\HotelScope;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos para las gráficas del Dashboard (Panel de Control): estado de
 * tickets, tiempo de resolución por departamento, tendencia semanal y
 * ranking de técnicos.
 *
 * Reutiliza el MISMO criterio de visibilidad por rol que ya usan
 * AlertasService (campana de notificaciones) y TicketController (listado):
 *   - admin/gerente: ven todos los tickets de la propiedad activa.
 *   - técnico: solo los tickets de su propio departamento (departamento_destino).
 *   - usuario: solo los tickets que él mismo reportó.
 *
 * Nota sobre HotelScope: se filtra explícitamente por $hotelActivo (la
 * misma propiedad que ya usan las tarjetas de DashboardController, vía
 * ResuelveHotelActivo) en lugar de dejar que el scope automático decida.
 * Así, para un Administrador, las gráficas muestran la misma propiedad que
 * las tarjetas de arriba y no una mezcla de todas — evita que ambos bloques
 * del mismo dashboard cuenten historias distintas.
 */
class DashboardMetricsService
{
    private const SEMANAS_TENDENCIA = 8;
    private const MAX_TECNICOS_RANKING = 6;

    // SLA de negocio: un ticket debe quedar Resuelto dentro de estas horas
    // desde que se creó. Ajustable a futuro si se define un SLA distinto
    // por prioridad o departamento; por ahora es un solo valor fijo, tal
    // como lo pidió el negocio.
    private const SLA_HORAS = 24;

    // Ventana que usa la tarjeta resumen ("¿vamos bien esta semana?") y su
    // comparación contra el periodo inmediato anterior, para poder mostrar
    // tendencia (↑/↓) sin necesitar otra consulta.
    private const DIAS_PERIODO_SLA = 7;

    public function metricas(User $user, string $hotelActivo): array
    {
        $tickets = $this->ticketsVisibles($user, $hotelActivo)
            ->get(['id', 'status', 'departamento_destino', 'resolved_by', 'operador_id', 'created_at', 'fecha_resolucion']);

        return [
            'total'                 => $tickets->count(),
            'cumplimientoSla'       => $this->cumplimientoSla($tickets),
            'ticketsPorEstado'      => $this->porEstado($tickets),
            'tiempoPorDepartamento' => $this->tiempoPorDepartamento($tickets),
            'tendenciaTickets'      => $this->tendenciaSemanal($tickets),
            // El ranking por técnico solo tiene sentido para quien supervisa
            // a varios técnicos; un técnico no necesita ver el desempeño de
            // sus compañeros y un usuario regular no tiene ninguno asignado.
            'rankingTecnicos'       => $user->puedeVerTodos()
                ? $this->rankingTecnicos($tickets)
                : [],
            // Contraparte de rankingTecnicos para el propio técnico: su
            // desglose individual (lo que SÍ le corresponde ver) dentro de
            // los tickets de su departamento.
            'miDesempeno'           => $user->isTecnico()
                ? $this->miDesempeno($tickets, $user)
                : null,
        ];
    }

    /**
     * KPIs de un "corte" para un rango de fechas arbitrario (ej. "todo
     * julio"), pensado para que direccion/gerencia pueda pedir un reporte
     * de un periodo especifico bajo demanda — a diferencia de metricas(),
     * que siempre mira los ultimos SEMANAS_TENDENCIA/DIAS_PERIODO_SLA dias.
     *
     * Distingue dos universos dentro del rango:
     *  - "creados": tickets que se LEVANTARON en el rango (para saber
     *    cuanta demanda entro y en que estado quedaron, sin importar si ya
     *    se resolvieron o no).
     *  - "resueltos": tickets que se MARCARON COMO RESUELTOS en el rango
     *    (para el "corte de reportes terminados" — el % de SLA, tiempo
     *    promedio y ranking de tecnicos se calculan sobre este universo,
     *    sin importar cuando se hayan creado).
     *
     * Respeta el mismo criterio de visibilidad por rol que metricas().
     */
    public function metricasPeriodo(User $user, string $hotelActivo, Carbon $desde, Carbon $hasta): array
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->endOfDay();

        $tickets = $this->ticketsVisibles($user, $hotelActivo)
            ->get(['id', 'status', 'departamento_destino', 'resolved_by', 'operador_id', 'created_at', 'fecha_resolucion',
                   'asset_id', 'ubicacion_id', 'veces_reasignado', 'motivo_reapertura', 'csat_calificacion', 'csat_at']);

        $creados = $tickets->filter(
            fn ($t) => $t->created_at && $t->created_at->between($desde, $hasta)
        );

        $resueltos = $tickets->filter(
            fn ($t) => $t->fecha_resolucion && $t->fecha_resolucion->between($desde, $hasta)
        );

        $cumplidos = $resueltos->filter(
            fn ($t) => $this->horasEntre($t->created_at, $t->fecha_resolucion) <= self::SLA_HORAS
        )->count();

        return [
            'desde'                 => $desde->format('Y-m-d'),
            'hasta'                 => $hasta->format('Y-m-d'),
            'sla_horas'             => self::SLA_HORAS,
            'creados'               => $creados->count(),
            'resueltos'             => $resueltos->count(),
            'slaCumplidos'          => $cumplidos,
            'slaPorcentaje'         => $resueltos->isEmpty()
                ? null
                : round(($cumplidos / $resueltos->count()) * 100, 1),
            'ticketsPorEstado'      => $this->porEstado($creados),
            'tiempoPorDepartamento' => $this->tiempoPorDepartamento($resueltos),
            // Igual que en metricas(): el ranking por técnico es para quien
            // supervisa, no para comparar compañeros entre sí.
            'rankingTecnicos'       => $user->puedeVerTodos()
                ? $this->rankingTecnicos($resueltos)
                : [],
            // Desglose individual del propio técnico en este mismo rango:
            // lo que necesita para su reporte de fin de mes, sin depender
            // de que alguien le comparta el ranking completo del área.
            'miDesempeno'           => $user->isTecnico()
                ? $this->miDesempeno($resueltos, $user)
                : null,
            // Fase 2 (nov 2026): calidad y dónde se concentran las fallas.
            'calidad'               => $this->calidad($tickets, $resueltos, $desde, $hasta),
            'topActivos'            => $this->topRelacionados($creados, 'asset_id'),
            'topUbicaciones'        => $this->topRelacionados($creados, 'ubicacion_id'),
        ];
    }

    /**
     * CSAT promedio (encuestas contestadas en el rango), % de tickets
     * resueltos en el rango que alguna vez se reabrieron y % resueltos sin
     * reasignar.
     */
    private function calidad(Collection $todos, Collection $resueltos, Carbon $desde, Carbon $hasta): array
    {
        $encuestas = $todos->filter(fn ($t) => $t->csat_calificacion && $t->csat_at && $t->csat_at->between($desde, $hasta));
        $total = $resueltos->count();

        return [
            'csatPromedio'       => $encuestas->isEmpty() ? null : round($encuestas->avg('csat_calificacion'), 2),
            'csatRespuestas'     => $encuestas->count(),
            'reaperturaPct'      => $total ? round($resueltos->filter(fn ($t) => filled($t->motivo_reapertura))->count() / $total * 100, 1) : null,
            'sinReasignarPct'    => $total ? round($resueltos->filter(fn ($t) => (int) $t->veces_reasignado === 0)->count() / $total * 100, 1) : null,
        ];
    }

    /** Top 10 activos o ubicaciones con más tickets levantados en el rango. */
    private function topRelacionados(Collection $creados, string $columna): array
    {
        $conteo = $creados->whereNotNull($columna)->countBy($columna)->sortDesc()->take(10);
        if ($conteo->isEmpty()) {
            return [];
        }

        $modelos = $columna === 'asset_id'
            ? \App\Models\Asset::whereIn('id', $conteo->keys())->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo'])->keyBy('id')
            : \App\Models\Ubicacion::whereIn('id', $conteo->keys())->get(['id', 'tipo', 'codigo', 'nombre'])->keyBy('id');

        return $conteo->map(function ($total, $id) use ($modelos, $columna) {
            $m = $modelos->get($id);
            return [
                'id'       => (int) $id,
                'etiqueta' => $m
                    ? ($columna === 'asset_id' ? $m->nomenclatura . ' · ' . ($m->tipo_equipo ?: $m->nombre) : $m->etiqueta)
                    : 'Eliminado',
                'total'    => $total,
            ];
        })->values()->all();
    }

    private function ticketsVisibles(User $user, string $hotelActivo)
    {
        $query = Ticket::withoutGlobalScope(HotelScope::class)
            ->where('hotel_code', $hotelActivo);

        if ($user->puedeVerTodos()) {
            return $query;
        }

        if ($user->isTecnico()) {
            return $query->where('departamento_destino', $user->departamento);
        }

        return $query->where('user_id', $user->id);
    }

    private function porEstado(Collection $tickets): array
    {
        return $tickets->countBy('status')->all();
    }

    private function tiempoPorDepartamento(Collection $tickets): array
    {
        return $tickets
            ->whereNotNull('fecha_resolucion')
            ->groupBy('departamento_destino')
            ->map(function (Collection $grupo, $departamento) {
                return [
                    'departamento'   => $departamento ?: 'Sin asignar',
                    'total'          => $grupo->count(),
                    'horas_promedio' => round($grupo->avg(
                        fn ($t) => $this->horasEntre($t->created_at, $t->fecha_resolucion)
                    ), 1),
                ];
            })
            ->sortByDesc('horas_promedio')
            ->values()
            ->all();
    }

    private function tendenciaSemanal(Collection $tickets): array
    {
        $inicio = now()->subWeeks(self::SEMANAS_TENDENCIA - 1)->startOfWeek();

        $creadosPorSemana = $tickets
            ->filter(fn ($t) => $t->created_at && $t->created_at->gte($inicio))
            ->groupBy(fn ($t) => $t->created_at->copy()->startOfWeek()->format('Y-m-d'));

        $resueltosPorSemana = $tickets
            ->filter(fn ($t) => $t->fecha_resolucion && $t->fecha_resolucion->gte($inicio))
            ->groupBy(fn ($t) => $t->fecha_resolucion->copy()->startOfWeek()->format('Y-m-d'));

        return collect(range(0, self::SEMANAS_TENDENCIA - 1))
            ->map(function (int $i) use ($inicio, $creadosPorSemana, $resueltosPorSemana) {
                $semana = $inicio->copy()->addWeeks($i);
                $clave  = $semana->format('Y-m-d');

                return [
                    'semana'    => $semana->format('d/M'),
                    'creados'   => $creadosPorSemana->get($clave, collect())->count(),
                    'resueltos' => $resueltosPorSemana->get($clave, collect())->count(),
                ];
            })
            ->all();
    }

    private function rankingTecnicos(Collection $tickets): array
    {
        $resueltos = $tickets->whereNotNull('resolved_by')->whereNotNull('fecha_resolucion');

        if ($resueltos->isEmpty()) {
            return [];
        }

        $porTecnico = $resueltos->groupBy('resolved_by');

        $nombres = User::whereIn('id', $porTecnico->keys())->pluck('name', 'id');

        return $porTecnico
            ->map(function (Collection $grupo, $userId) use ($nombres) {
                return [
                    'nombre'         => $nombres->get($userId, "Usuario #{$userId}"),
                    'total'          => $grupo->count(),
                    'horas_promedio' => round($grupo->avg(
                        fn ($t) => $this->horasEntre($t->created_at, $t->fecha_resolucion)
                    ), 1),
                ];
            })
            ->sortByDesc('total')
            ->take(self::MAX_TECNICOS_RANKING)
            ->values()
            ->all();
    }

    /**
     * Desglose de UN solo técnico (quien está consultando) dentro del
     * conjunto de tickets ya visible para él (su propio departamento). Es
     * la misma cuenta que rankingTecnicos() haría por técnico, pero
     * calculada solo para $user, para no exponerle el desempeño de sus
     * compañeros — eso sigue siendo exclusivo de admin/gerente.
     *
     * IMPORTANTE: se basa en `resolved_by`, que hoy solo registra quién
     * marcó el ticket como Resuelto en el sistema, no necesariamente quién
     * hizo el trabajo en campo si varias personas colaboraron. Es un proxy
     * razonable mientras no exista asignación individual de tickets.
     */
    private function miDesempeno(Collection $tickets, User $user): array
    {
        $propios = $tickets
            ->where('resolved_by', $user->id)
            ->whereNotNull('fecha_resolucion');

        if ($propios->isEmpty()) {
            return ['total' => 0, 'horas_promedio' => null, 'slaCumplidos' => 0, 'slaPorcentaje' => null, 'porOperador' => []];
        }

        $cumplidos = $propios->filter(
            fn ($t) => $this->horasEntre($t->created_at, $t->fecha_resolucion) <= self::SLA_HORAS
        )->count();

        return [
            'total'          => $propios->count(),
            'horas_promedio' => round($propios->avg(
                fn ($t) => $this->horasEntre($t->created_at, $t->fecha_resolucion)
            ), 1),
            'slaCumplidos'   => $cumplidos,
            'slaPorcentaje'  => round(($cumplidos / $propios->count()) * 100, 1),
            'porOperador'    => $this->porOperador($propios, $user),
        ];
    }

    /**
     * "¿Cuántos tickets hizo Juan?" — desglose de los tickets propios del
     * técnico (ya resueltos por él) agrupados por operador (ver Operador).
     * Los operadores son un catálogo COMPARTIDO por departamento (ver
     * Operador::scopeDeDepartamento) — un ticket resuelto por este técnico
     * puede traer marcado un operador que dio de alta un compañero del
     * mismo departamento (ej. Ana usa un operador que registró
     * Beto), así que los nombres se resuelven por departamento, no por
     * quién lo creó. Los tickets sin operador marcado quedan fuera: ese
     * dato es opcional y no todos los técnicos lo usan.
     */
    private function porOperador(Collection $ticketsPropios, User $user): array
    {
        $conOperador = $ticketsPropios->whereNotNull('operador_id');

        if ($conOperador->isEmpty()) {
            return [];
        }

        $porOperador = $conOperador->groupBy('operador_id');

        $nombres = Operador::deDepartamento($user->departamento)
            ->whereIn('id', $porOperador->keys())
            ->pluck('nombre', 'id');

        return $porOperador
            ->map(function (Collection $grupo, $operadorId) use ($nombres) {
                return [
                    'nombre' => $nombres->get($operadorId, "Operador #{$operadorId}"),
                    'total'  => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function horasEntre(?Carbon $desde, ?Carbon $hasta): ?float
    {
        if (!$desde || !$hasta) {
            return null;
        }

        return $desde->diffInMinutes($hasta) / 60;
    }

    /**
     * Tarjeta resumen para lectura ejecutiva: "¿vamos bien esta semana?" en
     * un solo número, con semáforo y tendencia contra el periodo anterior,
     * en vez de tener que interpretar una gráfica.
     *
     * Compara los últimos DIAS_PERIODO_SLA días contra los
     * DIAS_PERIODO_SLA días inmediatamente anteriores.
     */
    private function cumplimientoSla(Collection $tickets): array
    {
        $ahora          = now();
        $inicioActual   = $ahora->copy()->subDays(self::DIAS_PERIODO_SLA);
        $inicioAnterior = $inicioActual->copy()->subDays(self::DIAS_PERIODO_SLA);

        $actual   = $this->calcularSlaPeriodo($tickets, $inicioActual, $ahora);
        $anterior = $this->calcularSlaPeriodo($tickets, $inicioAnterior, $inicioActual);

        $tendencia = ($actual['porcentaje'] === null || $anterior['porcentaje'] === null)
            ? null
            : round($actual['porcentaje'] - $anterior['porcentaje'], 1);

        return [
            'sla_horas'   => self::SLA_HORAS,
            'periodo_dias'=> self::DIAS_PERIODO_SLA,
            'total'       => $actual['total'],
            'cumplidos'   => $actual['cumplidos'],
            'incumplidos' => $actual['incumplidos'],
            'porcentaje'  => $actual['porcentaje'],
            'tendencia'   => $tendencia,
            'semaforo'    => $this->semaforoSla($actual['porcentaje']),
        ];
    }

    /**
     * Un ticket solo se puede "juzgar" contra el SLA si:
     *  - ya se resolvió (a tiempo o tarde), o
     *  - sigue abierto pero YA pasaron más de SLA_HORAS desde que se creó
     *    (ya incumplió, aunque nadie lo haya marcado como resuelto).
     *
     * Un ticket abierto que todavía está dentro de sus primeras SLA_HORAS
     * no cuenta ni como cumplido ni como incumplido: el reloj sigue
     * corriendo y penalizarlo ahora sería injusto con el departamento.
     */
    private function calcularSlaPeriodo(Collection $tickets, Carbon $desde, Carbon $hasta): array
    {
        $enPeriodo = $tickets->filter(
            fn ($t) => $t->created_at && $t->created_at->between($desde, $hasta)
        );

        $evaluables = $enPeriodo->filter(function ($t) {
            if ($t->fecha_resolucion) {
                return true;
            }

            return $t->created_at->diffInHours(now()) >= self::SLA_HORAS;
        });

        $total = $evaluables->count();

        if ($total === 0) {
            return ['total' => 0, 'cumplidos' => 0, 'incumplidos' => 0, 'porcentaje' => null];
        }

        $cumplidos = $evaluables->filter(function ($t) {
            return $t->fecha_resolucion
                && $this->horasEntre($t->created_at, $t->fecha_resolucion) <= self::SLA_HORAS;
        })->count();

        return [
            'total'       => $total,
            'cumplidos'   => $cumplidos,
            'incumplidos' => $total - $cumplidos,
            'porcentaje'  => round(($cumplidos / $total) * 100, 1),
        ];
    }

    private function semaforoSla(?float $porcentaje): string
    {
        if ($porcentaje === null) {
            return 'sin_datos';
        }

        if ($porcentaje >= 80) {
            return 'verde';
        }

        if ($porcentaje >= 60) {
            return 'amarillo';
        }

        return 'rojo';
    }
}
