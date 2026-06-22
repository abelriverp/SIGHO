<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
        public function index()
    {
        $items = Item::latest()->get();
        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required',
            'categoria'   => 'required',
            'cantidad'    => 'required|integer',
            'status'      => 'required',
            'descripcion' => 'nullable',
            'imagen'      => 'nullable|image|max:5120',
            'pdf_baja'    => 'nullable|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }

        if ($request->hasFile('pdf_baja')) {
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        Item::create($data);

        // ESTO ES LO QUE TE REDIRIGE AL INDEX AL CREAR
        return redirect()->route('inventory.index');
    }

    public function show($id)
    {
        $item = Item::findOrFail($id);
        return view('inventory.show', compact('item'));
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        return view('inventory.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre'      => 'required',
            'categoria'   => 'required',
            'cantidad'    => 'required|integer',
            'status'      => 'required',
            'descripcion' => 'nullable',
            'imagen'      => 'nullable|image|max:5120',
            'pdf_baja'    => 'nullable|mimes:pdf|max:5120',
        ]);

        $item = Item::findOrFail($id);

        // 1. Eliminar PDF si se marcó la casilla
        if ($request->has('delete_pdf') && $item->pdf_path) {
            Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = null;
        }

        // 2. Actualizar Imagen
        if ($request->hasFile('imagen')) {
            if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }

        // 3. Subir/Reemplazar PDF
        if ($request->hasFile('pdf_baja')) {
            if ($item->pdf_path) Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        $item->update($data);

        // ESTO ES LO QUE TE REDIRIGE AL INDEX AL EDITAR
        // Asegúrate de que esta línea esté aquí y no falte un punto y coma
        return redirect()->route('inventory.index');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
        if ($item->pdf_path) Storage::disk('public')->delete($item->pdf_path);
        
        $item->delete();
        return redirect()->route('inventory.index');
    }
}