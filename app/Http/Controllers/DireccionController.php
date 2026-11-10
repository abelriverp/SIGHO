<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\Hotel;
use App\Services\TableroDireccionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Tablero de TI para direccion (Fase 5). Solo gerente y admin. */
class DireccionController extends Controller
{
    use ResuelveHotelActivo;

    private function periodo(Request $request): array
    {
        $mes = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('mes')) ? $request->query('mes') : now()->format('Y-m');
        $desde = Carbon::createFromFormat('Y-m-d', $mes . '-01')->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();
        if ($hasta->isFuture()) {
            $hasta = now();
        }
        return [$mes, $desde, $hasta];
    }

    public function index(Request $request, TableroDireccionService $servicio)
    {
        [$mes, $desde, $hasta] = $this->periodo($request);
        $t = $servicio->generar(auth()->user(), $this->hotelActivo(), $desde, $hasta);

        return view('direccion.index', ['t' => $t, 'mes' => $mes, 'hotelNombre' => Hotel::nombrePara($this->hotelActivo())]);
    }

    public function pdf(Request $request, TableroDireccionService $servicio)
    {
        [$mes, $desde, $hasta] = $this->periodo($request);
        $hotel = $this->hotelActivo();
        $t = $servicio->generar(auth()->user(), $hotel, $desde, $hasta);

        return Pdf::loadView('direccion.pdf', ['t' => $t, 'mes' => $mes, 'hotelNombre' => Hotel::nombrePara($hotel), 'generadoPor' => auth()->user()->name])
            ->setPaper('letter')
            ->download("Reporte_TI_{$hotel}_{$mes}.pdf");
    }
}
