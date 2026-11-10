<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PermisosActivos;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Contrato;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Contratos de soporte, mantenimiento, enlaces y polizas (Fase 3). */
class ContratoController extends Controller
{
    use PermisosActivos;

    private function reglas(): array
    {
        return [
            'nombre'                => 'required|string|max:200',
            'tipo'                  => ['required', Rule::in(array_keys(Contrato::TIPOS))],
            'ancho_banda'           => 'nullable|string|max:50',
            'ip_publica'            => 'nullable|string|max:100',
            'proveedor_id'          => 'nullable|integer',
            'numero'                => 'nullable|string|max:100',
            'fecha_inicio'          => 'nullable|date',
            'fecha_fin'             => 'nullable|date|after_or_equal:fecha_inicio',
            'costo'                 => 'nullable|numeric|min:0',
            'moneda'                => 'required|in:MXN,USD',
            'periodicidad_pago'     => ['nullable', Rule::in(array_keys(Contrato::PERIODICIDAD))],
            'renovacion_automatica' => 'nullable|boolean',
            'dias_aviso'            => 'required|integer|min:1|max:365',
            'sla'                   => 'nullable|string|max:3000',
            'contacto_soporte'      => 'nullable|string|max:255',
            'notas'                 => 'nullable|string|max:5000',
            'activo'                => 'nullable|boolean',
            'documento'             => 'nullable|file|mimes:pdf|max:10240',
            'activos'               => 'nullable|array',
            'activos.*'             => 'integer',
        ];
    }

    private function guardar(Request $request, Contrato $contrato): Contrato
    {
        $data = $request->validate($this->reglas());
        $data['proveedor_id'] = $this->proveedorValido($data['proveedor_id'] ?? null);
        $data['renovacion_automatica'] = $request->boolean('renovacion_automatica');
        $data['activo'] = $request->boolean('activo', true);

        if ($request->hasFile('documento')) {
            if ($contrato->documento_path) Storage::disk('public')->delete($contrato->documento_path);
            $data['documento_path'] = $request->file('documento')->store('contratos', 'public');
        }
        unset($data['documento'], $data['activos']);

        $contrato->fill($data)->save();
        $contrato->activos()->sync($this->idsActivosValidos($request->input('activos', [])));

        return $contrato;
    }

    private function datosForm(): array
    {
        return [
            'proveedores' => Proveedor::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'activos'     => Asset::vigentes()->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo']),
            'tipos'       => Contrato::TIPOS,
            'periodos'    => Contrato::PERIODICIDAD,
        ];
    }

    public function index(Request $request)
    {
        $filtro = $request->query('vigencia');
        $tipo = array_key_exists((string) $request->query('tipo'), Contrato::TIPOS) ? $request->query('tipo') : null;
        $contratos = Contrato::with('proveedor:id,nombre')->withCount('activos')
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->when($filtro === 'por_vencer', fn ($q) => $q->whereNotNull('fecha_fin')->whereDate('fecha_fin', '>=', now()->toDateString())->whereDate('fecha_fin', '<=', now()->addDays(90)->toDateString()))
            ->when($filtro === 'vencidos', fn ($q) => $q->whereDate('fecha_fin', '<', now()->toDateString()))
            ->orderByDesc('activo')->orderByRaw('fecha_fin IS NULL')->orderBy('fecha_fin')->get();

        $costoAnual = Contrato::where('activo', true)->get()->sum(function ($c) {
            if ($c->costo === null || $c->moneda !== 'MXN') return 0;
            return match ($c->periodicidad_pago) {
                'mensual'    => $c->costo * 12,
                'trimestral' => $c->costo * 4,
                default      => $c->costo,
            };
        });

        return view('contratos.index', ['contratos' => $contratos, 'filtro' => $filtro, 'tipo' => $tipo, 'tipos' => Contrato::TIPOS, 'costoAnual' => $costoAnual]);
    }

    public function create()
    {
        $this->exigeEditorActivos();
        return view('contratos.form', array_merge(['contrato' => new Contrato(['tipo' => 'soporte', 'moneda' => 'MXN', 'dias_aviso' => 30, 'activo' => true])], $this->datosForm()));
    }

    public function store(Request $request)
    {
        $this->exigeEditorActivos();
        $contrato = $this->guardar($request, new Contrato());
        AuditLog::registrar('contrato.creado', "Registró el contrato \"{$contrato->nombre}\".", $contrato);

        return redirect()->route('contratos.show', $contrato)->with('success', 'Contrato registrado.');
    }

    public function show(Contrato $contrato)
    {
        $contrato->load(['proveedor', 'activos.ubicacion']);
        return view('contratos.show', ['contrato' => $contrato, 'tipos' => Contrato::TIPOS, 'periodos' => Contrato::PERIODICIDAD]);
    }

    public function edit(Contrato $contrato)
    {
        $this->exigeEditorActivos();
        $contrato->load('activos:id');
        return view('contratos.form', array_merge(['contrato' => $contrato], $this->datosForm()));
    }

    public function update(Request $request, Contrato $contrato)
    {
        $this->exigeEditorActivos();
        $this->guardar($request, $contrato);

        return redirect()->route('contratos.show', $contrato)->with('success', 'Contrato actualizado.');
    }

    public function destroy(Contrato $contrato)
    {
        $this->exigeSupervisor();
        AuditLog::registrar('contrato.eliminado', "Eliminó el contrato \"{$contrato->nombre}\".", $contrato, $contrato->only(['nombre', 'tipo', 'fecha_fin', 'costo']), null);
        if ($contrato->documento_path) Storage::disk('public')->delete($contrato->documento_path);
        $contrato->delete();

        return redirect()->route('contratos.index')->with('success', 'Contrato eliminado.');
    }
}
