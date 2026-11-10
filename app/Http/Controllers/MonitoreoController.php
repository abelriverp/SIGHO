<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MonitoreoEvento;

/** Tablero de monitoreo por ping (Fase 4). */
class MonitoreoController extends Controller
{
    public function index()
    {
        $equipos = Asset::where('monitorear', true)->vigentes()
            ->with(['ubicacion:id,tipo,codigo,nombre'])
            ->orderByRaw("CASE WHEN estado_monitoreo = 'caido' THEN 0 WHEN estado_monitoreo IS NULL THEN 1 ELSE 2 END")
            ->orderBy('nomenclatura')
            ->get();

        $desde = now()->subDays(30);
        $eventos = MonitoreoEvento::with(['asset:id,nomenclatura,tipo_equipo', 'ticket:id,status'])
            ->where('ocurrido_at', '>=', $desde)->latest('ocurrido_at')->limit(50)->get();

        // Disponibilidad aproximada a 30 días: minutos caído (recuperaciones
        // + caídas abiertas) contra el total del periodo.
        $minutosPeriodo = 30 * 24 * 60;
        $caidoPorActivo = MonitoreoEvento::where('tipo', 'recuperacion')->where('ocurrido_at', '>=', $desde)
            ->selectRaw('asset_id, SUM(duracion_min) as total')->groupBy('asset_id')->pluck('total', 'asset_id');
        $disponibilidad = $equipos->mapWithKeys(function ($a) use ($caidoPorActivo, $minutosPeriodo) {
            $min = (int) ($caidoPorActivo[$a->id] ?? 0);
            if ($a->estado_monitoreo === 'caido' && $a->caido_desde) {
                $min += (int) $a->caido_desde->diffInMinutes(now());
            }
            return [$a->id => round(max(0, 100 - $min / $minutosPeriodo * 100), 2)];
        });

        $candidatos = Asset::vigentes()->where('monitorear', false)->whereNotNull('ip_address')
            ->whereIn('categoria', ['Red', 'Cómputo', 'Seguridad', 'Energía', 'Telefonía', 'Impresión'])
            ->orderBy('categoria')->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'tipo_equipo', 'categoria', 'ip_address']);

        return view('monitoreo.index', compact('equipos', 'eventos', 'disponibilidad', 'candidatos'));
    }

    /** Activa el monitoreo de varios activos a la vez. */
    public function activar(\Illuminate\Http\Request $request)
    {
        $u = auth()->user();
        if (! ($u->puedeResponderTickets() || $u->puedeVerTodos())) {
            abort(403);
        }
        $ids = collect($request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer'])['ids']);
        $n = Asset::whereIn('id', $ids)->whereNotNull('ip_address')->update(['monitorear' => true]);

        return back()->with('success', "{$n} equipo(s) agregados al monitoreo. El primer ping corre en menos de 5 minutos.");
    }
}
