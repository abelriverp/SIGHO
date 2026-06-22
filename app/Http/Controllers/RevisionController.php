<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Revision;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function create(Asset $asset)
    {
        return view('revisions.create', compact('asset'));
    }

    public function store(Request $request, Asset $asset)
    {
        // 🔒 Validamos estrictamente todos los campos que se van a guardar
        $data = $request->validate([
            'problema'   => 'required|string',
            'solucion'   => 'nullable|string', // Si guardas la solución al mismo tiempo
            'tecnico'    => 'nullable|string|max:255', // En caso de que lleves registro de quién lo hizo
            'notas'      => 'nullable|string',
            // Agrega aquí cualquier otro campo que pertenezca a tu formulario de revisión
        ]);

        // Guardamos usando únicamente los datos que pasaron la validación
        $asset->revisions()->create($data);

        return redirect()->route('assets.show', $asset->id)
                         ->with('success', 'Revisión registrada en la bitácora con éxito.');
    }

    public function edit(Revision $revision)
    {
        return view('revisions.edit', compact('revision'));
    }

    public function update(Request $request, Revision $revision)
    {
        // 🔒 Validamos también al actualizar para evitar inyecciones o campos vacíos ilegales
        $data = $request->validate([
            'problema'   => 'required|string',
            'solucion'   => 'nullable|string',
            'tecnico'    => 'nullable|string|max:255',
            'notas'      => 'nullable|string',
        ]);

        $revision->update($data);

        return redirect()->route('assets.show', $revision->asset_id)
                         ->with('success', 'Revisión actualizada correctamente.');
    }

    public function destroy(Revision $revision)
    {
        $assetId = $revision->asset_id;
        $revision->delete();

        return redirect()->route('assets.show', $assetId)
                         ->with('success', 'Entrada de la bitácora eliminada.');
    }
}