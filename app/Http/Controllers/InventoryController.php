<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Models\InventarioMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    // ─── Áreas disponibles ────────────────────────────────────
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

    // ─── Categorías para suministros de hotel ─────────────────
    const CATEGORIAS = [
        'Limpieza',
        'Amenidades',
        'Papelería',
        'Herramientas',
        'Alimentos y Bebidas',
        'Uniformes',
        'Electrónico',
        'Refacciones',
        'Otro',
    ];

    // ─── Helper: filtro base por hotel ─────────────────────────
    private function queryBase()
    {
        return InventarioItem::where('hotel_code', env('HOTEL_CODE', 'MAZ'));
    }

    // ─── Helper: recalcular status según cantidad ──────────────
    private function calcularStatus(InventarioItem $item): string
    {
        if ($item->cantidad <= 0) return 'Agotado';
        if ($item->stock_minimo > 0 && $item->cantidad <= $item->stock_minimo) return 'Disponible'; // sigue disponible pero bajoPorStock alerta
        return 'Disponible';
    }

    // ─────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->queryBase();

        if ($request->filled('area'))      $query->where('area', $request->area);
        if ($request->filled('categoria')) $query->where('categoria', $request->categoria);
        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('buscar'))    $query->where('nombre', 'like', '%' . $request->buscar . '%');

        // Filtro especial: solo stock bajo
        if ($request->filled('bajo_stock')) {
            $query->where('stock_minimo', '>', 0)->whereColumn('cantidad', '<=', 'stock_minimo');
        }

        $items = $query->latest()->paginate(25)->withQueryString();

        $bajoStock = $this->queryBase()
            ->where('stock_minimo', '>', 0)
            ->whereColumn('cantidad', '<=', 'stock_minimo')
            ->count();

        return view('inventory.index', [
            'items'      => $items,
            'areas'      => self::AREAS,
            'categorias' => self::CATEGORIAS,
            'bajoStock'  => $bajoStock,
            'filtros'    => $request->only(['area', 'categoria', 'status', 'buscar', 'bajo_stock']),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // CREATE / STORE
    // ─────────────────────────────────────────────────────────
    public function create()
    {
        return view('inventory.create', [
            'areas'      => self::AREAS,
            'categorias' => self::CATEGORIAS,
        ]);
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
            'descripcion'  => 'nullable|string',
            'imagen'       => 'nullable|image|max:5120',
            'pdf_baja'     => 'nullable|mimes:pdf|max:5120',
        ]);

        $hotelCode     = env('HOTEL_CODE', 'MAZ');
        $data['hotel_code'] = $hotelCode;
        $data['user_id']    = auth()->id();

        // Status automático según cantidad inicial
        $data['status'] = (float)$data['cantidad'] <= 0 ? 'Agotado' : 'Disponible';

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }
        if ($request->hasFile('pdf_baja')) {
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        $item = InventarioItem::create($data);

        if ((float)$data['cantidad'] > 0) {
            InventarioMovimiento::create([
                'hotel_code'         => $hotelCode,
                'inventario_item_id' => $item->id,
                'user_id'            => auth()->id(),
                'tipo'               => 'entrada',
                'cantidad'           => $data['cantidad'],
                'cantidad_anterior'  => 0,
                'cantidad_nueva'     => $data['cantidad'],
                'motivo'             => 'Stock inicial al registrar artículo',
            ]);
        }

        return redirect()->route('inventory.index')
            ->with('success', "Artículo \"{$item->nombre}\" registrado correctamente.");
    }

    // ─────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────
    public function show($id)
    {
        $item = $this->queryBase()->with('movimientos.usuario')->findOrFail($id);
        return view('inventory.show', compact('item'));
    }

    // ─────────────────────────────────────────────────────────
    // REGISTRAR MOVIMIENTO DIRECTO (entrada / salida)
    // ─────────────────────────────────────────────────────────
    public function registrarMovimiento(Request $request, $id)
    {
        $item = $this->queryBase()->findOrFail($id);

        $data = $request->validate([
            'tipo'     => 'required|in:entrada,salida',
            'cantidad' => 'required|numeric|min:0.01',
            'motivo'   => 'required|string|max:255',
        ]);

        $cantidadAnterior = (float) $item->cantidad;
        $delta            = (float) $data['cantidad'];

        if ($data['tipo'] === 'salida') {
            if ($delta > $cantidadAnterior) {
                return back()->withErrors(['cantidad' => "No hay suficiente stock. Disponible: {$cantidadAnterior} {$item->unidad}."])->withInput();
            }
            $cantidadNueva = $cantidadAnterior - $delta;
        } else {
            $cantidadNueva = $cantidadAnterior + $delta;
        }

        // Actualizar item
        $nuevoStatus = $cantidadNueva <= 0 ? 'Agotado' : 'Disponible';
        $item->update([
            'cantidad' => $cantidadNueva,
            'status'   => $nuevoStatus,
        ]);

        // Registrar movimiento
        InventarioMovimiento::create([
            'hotel_code'         => $item->hotel_code,
            'inventario_item_id' => $item->id,
            'user_id'            => auth()->id(),
            'tipo'               => $data['tipo'],
            'cantidad'           => $delta,
            'cantidad_anterior'  => $cantidadAnterior,
            'cantidad_nueva'     => $cantidadNueva,
            'motivo'             => $data['motivo'],
        ]);

        $tipoLabel = $data['tipo'] === 'entrada' ? 'Entrada' : 'Salida';
        return back()->with('success', "{$tipoLabel} de {$delta} {$item->unidad} registrada. Stock actual: {$cantidadNueva}.");
    }

    // ─────────────────────────────────────────────────────────
    // EDIT / UPDATE
    // ─────────────────────────────────────────────────────────
    public function edit($id)
    {
        $item = $this->queryBase()->findOrFail($id);
        return view('inventory.edit', [
            'item'       => $item,
            'areas'      => self::AREAS,
            'categorias' => self::CATEGORIAS,
        ]);
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
            'descripcion'  => 'nullable|string',
            'imagen'       => 'nullable|image|max:5120',
            'pdf_baja'     => 'nullable|mimes:pdf|max:5120',
        ]);

        $cantidadAnterior = (float) $item->cantidad;
        $cantidadNueva    = (float) $data['cantidad'];

        // Auto-status según cantidad
        $data['status'] = $cantidadNueva <= 0 ? 'Agotado' : 'Disponible';

        // Registrar movimiento si cambió la cantidad
        if ($cantidadAnterior !== $cantidadNueva) {
            InventarioMovimiento::create([
                'hotel_code'         => $item->hotel_code,
                'inventario_item_id' => $item->id,
                'user_id'            => auth()->id(),
                'tipo'               => $cantidadNueva > $cantidadAnterior ? 'entrada' : 'salida',
                'cantidad'           => abs($cantidadNueva - $cantidadAnterior),
                'cantidad_anterior'  => $cantidadAnterior,
                'cantidad_nueva'     => $cantidadNueva,
                'motivo'             => $request->filled('motivo_movimiento')
                    ? $request->motivo_movimiento
                    : 'Ajuste manual desde edición',
            ]);
        }

        // Archivos
        if ($request->has('delete_pdf') && $item->pdf_path) {
            Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = null;
        }
        if ($request->hasFile('imagen')) {
            if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
            $data['imagen_path'] = $request->file('imagen')->store('inventario_fotos', 'public');
        }
        if ($request->hasFile('pdf_baja')) {
            if ($item->pdf_path) Storage::disk('public')->delete($item->pdf_path);
            $data['pdf_path'] = $request->file('pdf_baja')->store('inventario_reportes', 'public');
        }

        $item->update($data);

        return redirect()->route('inventory.index')
            ->with('success', "Artículo \"{$item->nombre}\" actualizado correctamente.");
    }

    // ─────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $item = $this->queryBase()->findOrFail($id);

        if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
        if ($item->pdf_path)    Storage::disk('public')->delete($item->pdf_path);

        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Artículo eliminado del inventario.');
    }
}