<?php

namespace App\Http\Controllers;

use App\Models\Condomino;
use App\Models\Estancia;
use App\Models\Unidad;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class EstanciaController extends Controller
{
    use ResuelveHotelActivo;

    private function queryBase()
    {
        return Estancia::where('hotel_code', $this->hotelActivo());
    }

    // ─────────────────────────────────────────────────────────
    // INDEX: tablero de estancias (reservas, en curso, historial)
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->queryBase()->with('unidad', 'condomino');

        if ($request->filled('estado'))  $query->where('estado', $request->estado);
        if ($request->filled('unidad'))  $query->where('unidad_id', $request->unidad);
        if ($request->filled('buscar'))  $query->where('nombre_visitante', 'like', '%' . $request->buscar . '%');

        $estancias = $query->orderByDesc('fecha_llegada_prevista')->paginate(25)->withQueryString();

        $enCurso = $this->queryBase()->where('estado', 'checkin')->count();
        $reservasHoy = $this->queryBase()
            ->whereIn('estado', ['reservada', 'confirmada'])
            ->whereDate('fecha_llegada_prevista', now()->toDateString())
            ->count();

        $unidades = Unidad::where('hotel_code', $this->hotelActivo())->orderBy('identificador')->get();

        return view('condominios.estancias.index', [
            'estancias'   => $estancias,
            'estados'     => Estancia::ESTADOS,
            'unidades'    => $unidades,
            'enCurso'     => $enCurso,
            'reservasHoy' => $reservasHoy,
            'filtros'     => $request->only(['estado', 'unidad', 'buscar']),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // CREATE / STORE: nueva reserva o check-in directo
    // ─────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $hotelCode = $this->hotelActivo();

        $unidades   = Unidad::where('hotel_code', $hotelCode)->orderBy('identificador')->get();
        $condominos = Condomino::where('hotel_code', $hotelCode)->orderBy('nombre')->get();

        return view('condominios.estancias.form', [
            'unidades'       => $unidades,
            'condominos'     => $condominos,
            'tiposVisitante' => Estancia::TIPOS_VISITANTE,
            'unidadPreseleccionada' => $request->integer('unidad_id'),
        ]);
    }

    public function store(Request $request)
    {
        $hotelCode = $this->hotelActivo();

        $data = $request->validate([
            'unidad_id'              => 'required|exists:unidades,id',
            'condomino_id'           => 'nullable|exists:condominos,id',
            'tipo_visitante'         => 'required|in:' . implode(',', array_keys(Estancia::TIPOS_VISITANTE)),
            'nombre_visitante'       => 'required|string|max:255',
            'identificacion_placas'  => 'nullable|string|max:100',
            'num_acompanantes'       => 'nullable|integer|min:0',
            'fecha_llegada_prevista' => 'required|date',
            'fecha_salida_prevista'  => 'nullable|date|after_or_equal:fecha_llegada_prevista',
            'tarifa_noche'           => 'nullable|numeric|min:0',
            'notas'                  => 'nullable|string',
            'hacer_checkin_ahora'    => 'nullable|boolean',
        ]);

        $data['hotel_code']     = $hotelCode;
        $data['registrado_por'] = auth()->id();
        $data['estado']         = 'reservada';

        if ($request->boolean('hacer_checkin_ahora')) {
            $data['estado']      = 'checkin';
            $data['checkin_real'] = now();
        }

        $estancia = Estancia::create($data);

        if ($estancia->estado === 'checkin') {
            $this->actualizarEstatusUnidad($estancia);
        }

        return redirect()->route('condominios.estancias.index')
            ->with('success', "Estancia de \"{$estancia->nombre_visitante}\" registrada correctamente.");
    }

    public function show($id)
    {
        $estancia = $this->queryBase()->with('unidad', 'condomino', 'usuario')->findOrFail($id);
        return view('condominios.estancias.show', compact('estancia'));
    }

    // ─────────────────────────────────────────────────────────
    // CHECK-IN / CHECK-OUT / CANCELAR
    // ─────────────────────────────────────────────────────────
    public function checkin($id)
    {
        $estancia = $this->queryBase()->findOrFail($id);

        if (!$estancia->puedeHacerCheckin()) {
            return back()->with('error', 'Esta estancia ya no se puede pasar a check-in.');
        }

        $estancia->update([
            'estado'       => 'checkin',
            'checkin_real' => now(),
        ]);

        $this->actualizarEstatusUnidad($estancia);

        return back()->with('success', "Check-in registrado para \"{$estancia->nombre_visitante}\".");
    }

    public function checkout($id)
    {
        $estancia = $this->queryBase()->findOrFail($id);

        if (!$estancia->puedeHacerCheckout()) {
            return back()->with('error', 'Esta estancia no está en curso.');
        }

        $estancia->update([
            'estado'        => 'checkout',
            'checkout_real' => now(),
        ]);

        $estancia->unidad()->update(['estatus' => 'Vacia']);

        return back()->with('success', "Check-out registrado para \"{$estancia->nombre_visitante}\".");
    }

    public function cancelar($id)
    {
        $estancia = $this->queryBase()->findOrFail($id);

        if (in_array($estancia->estado, ['checkout', 'cancelada'])) {
            return back()->with('error', 'Esta estancia ya no se puede cancelar.');
        }

        $eraCheckin = $estancia->estado === 'checkin';
        $estancia->update(['estado' => 'cancelada']);

        if ($eraCheckin) {
            $estancia->unidad()->update(['estatus' => 'Vacia']);
        }

        return back()->with('success', 'Estancia cancelada.');
    }

    private function actualizarEstatusUnidad(Estancia $estancia): void
    {
        $nuevoEstatus = $estancia->tipo_visitante === 'inquilino_renta' ? 'Rentada' : 'Ocupada';
        $estancia->unidad()->update(['estatus' => $nuevoEstatus]);
    }

    // ─────────────────────────────────────────────────────────
    // REPORTES: ocupación, tendencia de check-ins, tipos de visitante
    // ─────────────────────────────────────────────────────────
    public function reportes(Request $request)
    {
        $hotelCode = $this->hotelActivo();
        $desde = $request->filled('desde') ? $request->date('desde') : now()->subDays(90);
        $hasta = $request->filled('hasta') ? $request->date('hasta') : now();

        $totalUnidades = Unidad::where('hotel_code', $hotelCode)->count();
        $unidadesOcupadas = Unidad::where('hotel_code', $hotelCode)
            ->whereIn('estatus', ['Ocupada', 'Rentada'])
            ->count();

        $porTipoVisitante = $this->queryBase()
            ->whereBetween('fecha_llegada_prevista', [$desde->startOfDay(), $hasta->endOfDay()])
            ->selectRaw('tipo_visitante, COUNT(*) as total')
            ->groupBy('tipo_visitante')
            ->get();

        $checkinsPorMes = $this->queryBase()
            ->whereNotNull('checkin_real')
            ->whereBetween('checkin_real', [$desde->startOfDay(), $hasta->endOfDay()])
            ->selectRaw("DATE_FORMAT(checkin_real, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $topUnidades = Estancia::query()
            ->join('unidades', 'unidades.id', '=', 'estancias.unidad_id')
            ->where('estancias.hotel_code', $hotelCode)
            ->whereBetween('estancias.fecha_llegada_prevista', [$desde->startOfDay(), $hasta->endOfDay()])
            ->selectRaw('unidades.id, unidades.identificador, unidades.torre, COUNT(*) as total_estancias')
            ->groupBy('unidades.id', 'unidades.identificador', 'unidades.torre')
            ->orderByDesc('total_estancias')
            ->limit(10)
            ->get();

        $estanciasFinalizadas = $this->queryBase()
            ->whereNotNull('checkin_real')->whereNotNull('checkout_real')
            ->whereBetween('checkout_real', [$desde->startOfDay(), $hasta->endOfDay()])
            ->get();

        $promedioNoches = $estanciasFinalizadas->isNotEmpty()
            ? round($estanciasFinalizadas->avg(fn($e) => $e->nochesEstancia()), 1)
            : 0;

        return view('condominios.reportes', [
            'totalUnidades'     => $totalUnidades,
            'unidadesOcupadas'  => $unidadesOcupadas,
            'porTipoVisitante'  => $porTipoVisitante,
            'checkinsPorMes'    => $checkinsPorMes,
            'topUnidades'       => $topUnidades,
            'promedioNoches'    => $promedioNoches,
            'desde'             => $desde->format('Y-m-d'),
            'hasta'             => $hasta->format('Y-m-d'),
        ]);
    }
}
