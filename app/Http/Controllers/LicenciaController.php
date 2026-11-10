<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PermisosActivos;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Licencia;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Licencias de software: compradas vs. instaladas (Fase 3). */
class LicenciaController extends Controller
{
    use PermisosActivos;

    private function reglas(): array
    {
        return [
            'software'          => 'required|string|max:150',
            'edicion'           => 'nullable|string|max:100',
            'tipo'              => ['required', Rule::in(array_keys(Licencia::TIPOS))],
            'proveedor_id'      => 'nullable|integer',
            'cantidad'          => 'required|integer|min:1|max:100000',
            'referencia'        => 'nullable|string|max:150',
            'fecha_compra'      => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'costo'             => 'nullable|numeric|min:0',
            'notas'             => 'nullable|string|max:3000',
            'activos'           => 'nullable|array',
            'activos.*'         => 'integer',
        ];
    }

    private function guardar(Request $request, Licencia $licencia): Licencia
    {
        $data = $request->validate($this->reglas());
        $data['proveedor_id'] = $this->proveedorValido($data['proveedor_id'] ?? null);
        unset($data['activos']);
        $licencia->fill($data)->save();
        $licencia->activos()->sync($this->idsActivosValidos($request->input('activos', [])));

        return $licencia;
    }

    private function datosForm(): array
    {
        return [
            'proveedores' => Proveedor::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'activos'     => Asset::vigentes()->whereIn('categoria', ['Cómputo', 'Punto de venta'])->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo']),
            'tipos'       => Licencia::TIPOS,
        ];
    }

    public function index()
    {
        $licencias = Licencia::with('proveedor:id,nombre')->withCount('activos')
            ->orderByRaw('fecha_vencimiento IS NULL')->orderBy('fecha_vencimiento')->orderBy('software')->get();

        return view('licencias.index', ['licencias' => $licencias, 'tipos' => Licencia::TIPOS]);
    }

    public function create()
    {
        $this->exigeEditorActivos();
        return view('licencias.form', array_merge(['licencia' => new Licencia(['tipo' => 'suscripcion', 'cantidad' => 1])], $this->datosForm()));
    }

    public function store(Request $request)
    {
        $this->exigeEditorActivos();
        $l = $this->guardar($request, new Licencia());
        AuditLog::registrar('licencia.creada', "Registró la licencia \"{$l->software}\" ({$l->cantidad}).", $l);

        return redirect()->route('licencias.index')->with('success', 'Licencia registrada.');
    }

    public function edit(Licencia $licencia)
    {
        $this->exigeEditorActivos();
        $licencia->load('activos:id');
        return view('licencias.form', array_merge(['licencia' => $licencia], $this->datosForm()));
    }

    public function update(Request $request, Licencia $licencia)
    {
        $this->exigeEditorActivos();
        $this->guardar($request, $licencia);

        return redirect()->route('licencias.index')->with('success', 'Licencia actualizada.');
    }

    public function destroy(Licencia $licencia)
    {
        $this->exigeSupervisor();
        AuditLog::registrar('licencia.eliminada', "Eliminó la licencia \"{$licencia->software}\".", $licencia, $licencia->only(['software', 'cantidad', 'fecha_vencimiento']), null);
        $licencia->delete();

        return redirect()->route('licencias.index')->with('success', 'Licencia eliminada.');
    }
}
