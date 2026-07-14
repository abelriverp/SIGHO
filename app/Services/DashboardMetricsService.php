<?php

namespace App\Services;

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

    public function metricas(User $user, string $hotelActivo): array
    {
        $tickets = $this->ticketsVisibles($user, $hotelActivo)
            ->get(['id', 'status', 'departamento_destino', 'resolved_by', 'created_at', 'fecha_resolucion']);

        return [
            'total'                 => $tickets->count(),
            'ticketsPorEstado'      => $this->porEstado($tickets),
            'tiempoPorDepartamento' => $this->tiempoPorDepartamento($tickets),
            'tendenciaTickets'      => $this->tendenciaSemanal($tickets),
            // El ranking por técnico solo tiene sentido para quien supervisa
            // a varios técnicos; un técnico no necesita ver el desempeño de
            // sus compañeros y un usuario regular no tiene ninguno asignado.
            'rankingTecnicos'       => $user->puedeVerTodos()
                ? $this->rankingTecnicos($tickets)
                : [],
        ];
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

    private function horasEntre(?Carbon $desde, ?Carbon $hasta): ?float
    {
        if (!$desde || !$hasta) {
            return null;
        }

        return $desde->diffInMinutes($hasta) / 60;
    }
}
