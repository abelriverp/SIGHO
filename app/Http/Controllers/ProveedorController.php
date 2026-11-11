<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PermisosActivos;
use App\Models\AuditLog;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Catalogo de proveedores de TI (Fase 3). */
class ProveedorController extends Controller
{
    use PermisosActivos;

    private function reglas(?Proveedor $p = null): array
    {
        return [
            'nombre'   => ['required', 'string', 'max:150', Rule::unique('proveedores', 'nombre')->where('hotel_code', $p?->hotel_code ?? auth()->user()->hotel_code)->ignore($p?->id)],
            'rfc'      => 'nullable|string|max:20',
            'contacto' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:50',
            'email'    => 'nullable|email|max:150',
            'soporte'  => 'nullable|string|max:255',
            'notas'    => 'nullable|string|max:3000',
            'activo'   => 'nullable|boolean',
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $proveedores = Proveedor::withCount(['contratos', 'licencias', 'activos', 'tickets as tickets_abiertos_count' => fn ($t) => $t->whereIn('status', ['Abierto', 'Reabierto'])])
            ->when($q !== '', fn ($w) => $w->where('nombre', 'like', "%{$q}%"))
            ->orderByDesc('activo')->orderBy('nombre')->get();

        return view('proveedores.index', ['proveedores' => $proveedores, 'q' => $q]);
    }

    public function store(Request $request)
    {
        $this->exigeEditorActivos();
        $data = $request->validate($this->reglas());
        $data['activo'] = true;
        Proveedor::create($data);

        return back()->with('success', 'Proveedor agregado.');
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $this->exigeEditorActivos();
        $data = $request->validate($this->reglas($proveedor));
        $data['activo'] = $request->boolean('activo');
        $proveedor->update($data);

        return back()->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor)
    {
        $this->exigeSupervisor();
        AuditLog::registrar('proveedor.eliminado', "Eliminó el proveedor \"{$proveedor->nombre}\".", $proveedor, $proveedor->only(['nombre', 'rfc']), null);
        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado. Sus contratos, licencias y activos se conservan sin proveedor.');
    }
}
