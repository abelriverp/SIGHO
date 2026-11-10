<?php

namespace App\Http\Controllers;

use App\Services\VencimientosService;
use Illuminate\Http\Request;

/** Tablero unico de vencimientos: contratos, licencias y garantias (Fase 3). */
class VencimientoController extends Controller
{
    public function index(Request $request, VencimientosService $servicio)
    {
        $dias = in_array((int) $request->query('dias'), [30, 60, 90, 180, 365], true) ? (int) $request->query('dias') : 90;
        $items = $servicio->proximos(null, $dias);

        return view('vencimientos.index', [
            'vencidos' => $items->where('dias', '<', 0)->sortByDesc('fecha')->values(),
            'proximos' => $items->where('dias', '>=', 0)->values(),
            'dias'     => $dias,
        ]);
    }
}
