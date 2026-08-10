<?php

namespace App\Services;

use App\Models\InventarioItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Centraliza las alertas "proactivas" del sistema: stock bajo y tickets
 * vencidos. Antes esto vivía como consultas sueltas (DB::table) dentro de
 * layouts/app.blade.php, con dos bugs reales:
 *   1. Usaban env('HOTEL_CODE', 'MAZ') en vez del hotel del usuario,
 *      ignorando el HotelScope y filtrando por la propiedad equivocada.
 *   2. El badge de tickets contaba TODOS los abiertos, no los realmente
 *      atrasados, y no pasaba por Eloquent (sin scoping multipropiedad).
 *
 * Al usar los modelos Eloquent aquí, HotelScope se aplica solo: cada
 * usuario ve nada más las alertas de su propia propiedad (el Admin ve todas).
 *
 * "Vencido"/"por vencer" ya NO se calculan aquí: se delegan por completo a
 * App\Services\SlaService, que reemplaza el umbral fijo por prioridad que
 * antes vivía hardcodeado en esta clase (HORAS_VENCIDO) y que ahora es
 * configurable por propiedad/departamento/prioridad desde /tickets/sla.
 * Se conserva la firma pública de estos métodos sin cambios para no romper
 * layouts/app.blade.php ni AlertasServiceTest.
 */
class AlertasService
{
    public function __construct(private SlaService $sla)
    {
    }

    public function stockBajo(User $user, int $limite = 5): Collection
    {
        if (!$user->tieneModulo('inventario_suministros')) {
            return collect();
        }

        return InventarioItem::query()
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereColumn('cantidad', '<=', 'stock_minimo')
            ->orderBy('cantidad')
            ->limit($limite)
            ->get();
    }

    public function stockBajoCount(User $user): int
    {
        if (!$user->tieneModulo('inventario_suministros')) {
            return 0;
        }

        return InventarioItem::query()
            ->where('activo', true)
            ->where('stock_minimo', '>', 0)
            ->whereColumn('cantidad', '<=', 'stock_minimo')
            ->count();
    }

    public function ticketsVencidos(User $user, int $limite = 5): Collection
    {
        return $this->sla->ticketsVencidos($user, $limite);
    }

    public function ticketsVencidosCount(User $user): int
    {
        return $this->sla->ticketsVencidosCount($user);
    }

    public function ticketsPorVencer(User $user, int $limite = 5): Collection
    {
        return $this->sla->ticketsPorVencer($user, $limite);
    }

    public function ticketsPorVencerCount(User $user): int
    {
        return $this->sla->ticketsPorVencerCount($user);
    }

    /**
     * Total de tickets "Abierto" (vencidos o no) visibles para este usuario.
     * Reemplaza el DB::table('tickets') suelto que había en el layout: ese
     * no pasaba por Eloquent, así que ignoraba el HotelScope por completo.
     */
    public function ticketsAbiertosCount(User $user): int
    {
        if (!($user->puedeVerTodos() || $user->isTecnico())) {
            return 0;
        }

        $query = Ticket::query()->where('status', 'Abierto');

        if ($user->isTecnico() && !$user->puedeVerTodos()) {
            $query->where('departamento_destino', $user->departamento);
        }

        return $query->count();
    }

    /**
     * Resumen combinado, listo para pintar en la campana de notificaciones.
     */
    public function resumen(User $user): array
    {
        return [
            'stock_bajo'             => $this->stockBajo($user),
            'stock_bajo_count'       => $this->stockBajoCount($user),
            'tickets_vencidos'       => $this->ticketsVencidos($user),
            'tickets_vencidos_count' => $this->ticketsVencidosCount($user),
            'tickets_por_vencer'       => $this->ticketsPorVencer($user),
            'tickets_por_vencer_count' => $this->ticketsPorVencerCount($user),
        ];
    }
}
