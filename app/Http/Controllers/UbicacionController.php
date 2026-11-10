<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ubicaciones de la propiedad (Fase 1, nov 2026): habitaciones, areas,
 * oficinas, sites. Cada activo vive en una.
 */
class UbicacionController extends Controller
{
    private function reglas(?Ubicacion $ubicacion = null): array
    {
        $hotel = $ubicacion?->hotel_code ?? auth()->user()->hotel_code;

        return [
            'tipo'   => ['required', Rule::in(array_keys(Ubicacion::TIPOS))],
            'codigo' => [
                'required', 'string', 'max:50',
                Rule::unique('ubicaciones', 'codigo')->where('hotel_code', $hotel)->ignore($ubicacion?->id),
            ],
            'nombre' => 'nullable|string|max:150',
            'piso'   => 'nullable|integer|min:-5|max:200',
            'zona'   => 'nullable|string|max:100',
            'notas'  => 'nullable|string|max:2000',
            'activa' => 'nullable|boolean',
        ];
    }

    public function index(Request $request)
    {
        $filtros = $request->only(['tipo', 'piso', 'buscar']);

        $ubicacions = Ubicacion::withCount([
                'activos',
                'activos as activos_en_reparacion_count' => fn ($q) => $q->where('status', 'En Reparación'),
            ])
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when(($filtros['piso'] ?? '') !== '' ? $filtros['piso'] : null, fn ($q, $v) => $q->where('piso', $v))
            ->when($filtros['buscar'] ?? null, function ($q, $v) {
                $like = '%' . $v . '%';
                $q->where(fn ($w) => $w->where('codigo', 'like', $like)->orWhere('nombre', 'like', $like)->orWhere('zona', 'like', $like));
            })
            ->orderBy('tipo')
            ->orderBy('piso')
            ->orderBy('codigo')
            ->paginate(60)
            ->withQueryString();

        $pisos = Ubicacion::whereNotNull('piso')->distinct()->orderBy('piso')->pluck('piso');
        $conteoPorTipo = Ubicacion::selectRaw('tipo, count(*) as total')->groupBy('tipo')->pluck('total', 'tipo');

        return view('ubicaciones.index', [
            'ubicaciones'   => $ubicacions,
            'filtros'       => $filtros,
            'tipos'         => Ubicacion::TIPOS,
            'pisos'         => $pisos,
            'conteoPorTipo' => $conteoPorTipo,
        ]);
    }

    public function create()
    {
        return view('ubicaciones.form', ['ubicacion' => new Ubicacion(['tipo' => 'area', 'activa' => true]), 'tipos' => Ubicacion::TIPOS]);
    }

    public function store(Request $request)
    {
        $request->merge(['codigo' => mb_strtoupper(trim((string) $request->input('codigo')))]);
        $data = $request->validate($this->reglas());
        $data['activa'] = $request->boolean('activa', true);

        $ubicacion = Ubicacion::create($data);

        return redirect()->route('ubicaciones.show', $ubicacion)->with('success', "Ubicación {$ubicacion->etiqueta} creada.");
    }

    public function show(Ubicacion $ubicacion)
    {
        $ubicacion->load(['activos' => fn ($q) => $q->with(['padre:id,nomenclatura', 'asignado:id,name'])->orderBy('categoria')->orderBy('nomenclatura')]);

        $tickets = \App\Models\Ticket::where('ubicacion_id', $ubicacion->id)
            ->latest()->limit(15)->get(['id', 'status', 'descripcion', 'created_at', 'asset_id', 'prioridad']);

        return view('ubicaciones.show', compact('ubicacion', 'tickets'));
    }

    public function edit(Ubicacion $ubicacion)
    {
        return view('ubicaciones.form', ['ubicacion' => $ubicacion, 'tipos' => Ubicacion::TIPOS]);
    }

    public function update(Request $request, Ubicacion $ubicacion)
    {
        $request->merge(['codigo' => mb_strtoupper(trim((string) $request->input('codigo')))]);
        $data = $request->validate($this->reglas($ubicacion));
        $data['activa'] = $request->boolean('activa');

        $ubicacion->update($data);

        return redirect()->route('ubicaciones.show', $ubicacion)->with('success', 'Ubicación actualizada.');
    }

    public function destroy(Ubicacion $ubicacion)
    {
        if (! auth()->user()->can('delete', $ubicacion)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar ubicaciones.');
        }

        $activos = $ubicacion->activos()->count();
        if ($activos > 0) {
            return back()->with('error', "No se puede eliminar {$ubicacion->etiqueta}: tiene {$activos} activo(s). Muévelos primero o desactívala.");
        }

        AuditLog::registrar('ubicacion.eliminada', "Eliminó la ubicación {$ubicacion->etiqueta}.", $ubicacion, $ubicacion->only(['codigo', 'tipo', 'nombre', 'piso']), null);
        $ubicacion->delete();

        return redirect()->route('ubicaciones.index')->with('success', 'Ubicación eliminada.');
    }

    /**
     * Genera habitaciones en serie: piso 12, de la 01 a la 30 -> 1201..1230.
     * Las que ya existen se omiten.
     */
    public function generarHabitaciones(Request $request)
    {
        $data = $request->validate([
            'piso'   => 'required|integer|min:0|max:200',
            'desde'  => 'required|integer|min:0|max:999',
            'hasta'  => 'required|integer|gte:desde|max:999',
            'zona'   => 'nullable|string|max:100',
            'digitos'=> 'nullable|integer|in:2,3',
        ]);

        $digitos = (int) ($data['digitos'] ?? 2);
        if ($data['hasta'] - $data['desde'] > 300) {
            return back()->with('error', 'Máximo 300 habitaciones por generación.');
        }

        $existentes = Ubicacion::pluck('codigo')->flip();
        $creadas = 0;

        for ($n = $data['desde']; $n <= $data['hasta']; $n++) {
            $codigo = $data['piso'] . str_pad((string) $n, $digitos, '0', STR_PAD_LEFT);
            if (isset($existentes[$codigo])) {
                continue;
            }
            Ubicacion::create([
                'tipo'   => 'habitacion',
                'codigo' => $codigo,
                'piso'   => $data['piso'],
                'zona'   => $data['zona'] ?? null,
                'activa' => true,
            ]);
            $creadas++;
        }

        $omitidas = ($data['hasta'] - $data['desde'] + 1) - $creadas;

        return redirect()->route('ubicaciones.index', ['tipo' => 'habitacion', 'piso' => $data['piso']])
            ->with('success', "Se crearon {$creadas} habitaciones del piso {$data['piso']}" . ($omitidas ? " ({$omitidas} ya existían)." : '.'));
    }
}
