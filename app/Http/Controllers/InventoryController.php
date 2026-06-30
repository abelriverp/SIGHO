<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Models\InventarioMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    // ─── Áreas disponibles (centralizado para todas las vistas) ───
    const AREAS = [
        'Sistemas',
        'Mantenimiento',
        'Seguridad',
        'Ama de Llaves',
        'Recepción',
        'Alimentos y Bebidas',
        'Administración',
        'General',
    ];

    // ─── Helper: filtro base siempre por hotel_code ───────────────
    private function queryBase()
    {
        $hotelCode = env('HOTEL_CODE', 'MAZ');
        return InventarioItem::where('hotel_code', $hotelCode);
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->queryBase();

        // Filtro por área
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Búsqueda por nombre
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        $items = $query->latest()->get();

        // Alerta de stock bajo (para badge)
        $bajoStock = $this->queryBase()
            ->where('stock_minimo', '>', 0)
            ->whereColumn('cantidad', '<=', 'stock_minimo')
            ->count();

        return view('inventory.index', [
            'items'     => $items,
            'areas'     => self::AREAS,
            'bajoStock' => $bajoStock,
            'filtros'   => $request->only(['area', 'categoria', 'status', 'buscar']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // CREATE / STORE
    // ─────────────────────────────────────────────────────────────
    public function create()
    {
        return view('inventory.create', ['areas' => self::AREAS]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'categoria'    => 'required|string',
            'area'         => 'required|string',
            'cantidad'     => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'unidad'       => 'nullable|string|max:20',
            'status'       => 'required|string',
            'descripcion'  => 'nullable|string',
            'imagen'       => 'nullable|image|max:5120',
            'pdf_baja'     => 'nullable|mimes:pdf|max:5120',
        ]);

        // Asignar hotel y usuario
        $data['hotel_code'] = env('HOTEL_CODE', 'MAZ');
        $data['user_id']    = auth()->id();

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }

        if ($request->hasFile('pdf_baja')) {
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        $item = InventarioItem::create($data);

        // Registrar movimiento inicial si hay cantidad
        if ($data['cantidad'] > 0) {
            InventarioMovimiento::create([
                'hotel_code'          => $data['hotel_code'],
                'inventario_item_id'  => $item->id,
                'user_id'             => auth()->id(),
                'tipo'                => 'entrada',
                'cantidad'            => $data['cantidad'],
                'cantidad_anterior'   => 0,
                'cantidad_nueva'      => $data['cantidad'],
                'motivo'              => 'Stock inicial al registrar artículo',
            ]);
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Artículo registrado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────
    public function show($id)
    {
        $item = $this->queryBase()->with('movimientos.usuario')->findOrFail($id);
        return view('inventory.show', compact('item'));
    }

    // ─────────────────────────────────────────────────────────────
    // EDIT / UPDATE
    // ─────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $item = $this->queryBase()->findOrFail($id);
        return view('inventory.edit', ['item' => $item, 'areas' => self::AREAS]);
    }

    public function update(Request $request, $id)
    {
        $item = $this->queryBase()->findOrFail($id);

        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'categoria'    => 'required|string',
            'area'         => 'required|string',
            'cantidad'     => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'unidad'       => 'nullable|string|max:20',
            'status'       => 'required|string',
            'descripcion'  => 'nullable|string',
            'imagen'       => 'nullable|image|max:5120',
            'pdf_baja'     => 'nullable|mimes:pdf|max:5120',
        ]);

        // Registrar movimiento si cambió la cantidad
        $cantidadAnterior = $item->cantidad;
        $cantidadNueva    = (float) $data['cantidad'];

        if ($cantidadAnterior != $cantidadNueva) {
            InventarioMovimiento::create([
                'hotel_code'         => $item->hotel_code,
                'inventario_item_id' => $item->id,
                'user_id'            => auth()->id(),
                'tipo'               => $cantidadNueva > $cantidadAnterior ? 'entrada' : 'salida',
                'cantidad'           => abs($cantidadNueva - $cantidadAnterior),
                'cantidad_anterior'  => $cantidadAnterior,
                'cantidad_nueva'     => $cantidadNueva,
                'motivo'             => $request->motivo_movimiento ?? 'Ajuste manual desde edición',
            ]);
        }

        // Eliminar PDF si se marcó
        if ($request->has('delete_pdf') && $item->pdf_path) {
            Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = null;
        }

        // Actualizar imagen
        if ($request->hasFile('imagen')) {
            if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }

        // Actualizar PDF
        if ($request->hasFile('pdf_baja')) {
            if ($item->pdf_path) Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        $item->update($data);

        return redirect()->route('inventory.index')
            ->with('success', 'Artículo actualizado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $item = $this->queryBase()->findOrFail($id);

        if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
        if ($item->pdf_path)    Storage::disk('public')->delete($item->pdf_path);

        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Artículo eliminado.');
    }
}