<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Contrato;
use App\Models\Licencia;
use App\Models\Scopes\HotelScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Junta en una sola lista lo que vence: contratos, licencias y garantias
 * de activos (Fase 3). La usan la pantalla de Vencimientos y el comando
 * de alertas por correo.
 */
class VencimientosService
{
    /**
     * @return Collection<array{tipo:string, nombre:string, detalle:?string, fecha:Carbon, dias:int, ruta:?string, id:int}>
     */
    public function proximos(?string $hotelCode, int $dias = 90, bool $incluirVencidos = true): Collection
    {
        $hoy = Carbon::today();
        $hasta = $hoy->copy()->addDays($dias);
        $desde = $incluirVencidos ? $hoy->copy()->subDays(365) : $hoy;
        $deHotel = fn ($q) => $hotelCode ? $q->withoutGlobalScope(HotelScope::class)->where('hotel_code', $hotelCode) : $q;

        $items = collect();

        $deHotel(Contrato::query())->with('proveedor:id,nombre')->where('activo', true)
            ->whereNotNull('fecha_fin')->whereBetween('fecha_fin', [$desde->toDateString(), $hasta->toDateString()])
            ->get()->each(fn ($c) => $items->push([
                'tipo' => 'Contrato', 'id' => $c->id, 'nombre' => $c->nombre,
                'detalle' => trim(($c->proveedor?->nombre ?? '') . ($c->renovacion_automatica ? ' · renovación automática' : '')),
                'fecha' => $c->fecha_fin, 'dias' => (int) $hoy->diffInDays($c->fecha_fin, false), 'ruta' => 'contratos.show',
                'aviso' => $c->dias_aviso,
            ]));

        $deHotel(Licencia::query())->withCount('activos')
            ->whereNotNull('fecha_vencimiento')->whereBetween('fecha_vencimiento', [$desde->toDateString(), $hasta->toDateString()])
            ->get()->each(fn ($l) => $items->push([
                'tipo' => 'Licencia', 'id' => $l->id, 'nombre' => trim("{$l->software} {$l->edicion}"),
                'detalle' => "{$l->activos_count} de {$l->cantidad} en uso",
                'fecha' => $l->fecha_vencimiento, 'dias' => (int) $hoy->diffInDays($l->fecha_vencimiento, false), 'ruta' => 'licencias.edit',
                'aviso' => 30,
            ]));

        $deHotel(Asset::query())->vigentes()
            ->whereNotNull('garantia_hasta')->whereBetween('garantia_hasta', [$desde->toDateString(), $hasta->toDateString()])
            ->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo', 'marca', 'modelo', 'garantia_hasta'])
            ->each(fn ($a) => $items->push([
                'tipo' => 'Garantía', 'id' => $a->id, 'nombre' => $a->nomenclatura,
                'detalle' => trim(($a->tipo_equipo ?: $a->nombre) . " {$a->marca} {$a->modelo}"),
                'fecha' => $a->garantia_hasta, 'dias' => (int) $hoy->diffInDays($a->garantia_hasta, false), 'ruta' => 'assets.show',
                'aviso' => 30,
            ]));

        return $items->sortBy('fecha')->values();
    }
}
