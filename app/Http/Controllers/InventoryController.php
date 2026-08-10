<?php

namespace App\Http\Controllers;

use App\Models\InventarioItem;
use App\Models\InventarioMovimiento;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class InventoryController extends Controller
{
    use ResuelveHotelActivo;

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
        return InventarioItem::where('hotel_code', $this->hotelActivo());
    }

    // Nota: el "status" (Disponible/Agotado/Baja) es independiente del stock
    // mínimo. El stock mínimo NUNCA cambia el status del artículo — solo
    // dispara la alerta visual (badge rojo, contador de "Stock Bajo") a
    // través de InventarioItem::bajoPorStock(). Ver dashboard y navbar.

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
            'disponible_huesped' => 'nullable|boolean',
        ]);

        $data['disponible_huesped'] = $request->boolean('disponible_huesped');

        $hotelCode     = $this->hotelActivo();
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
            'tipo'           => 'required|in:entrada,salida',
            'cantidad'       => 'required|numeric|min:0.01',
            'motivo'         => 'required|string|max:255',
            'tipo_salida'    => 'required_if:tipo,salida|nullable|in:' . implode(',', array_keys(InventarioMovimiento::TIPOS_SALIDA)),
            'habitacion'     => 'required_if:tipo_salida,solicitud_huesped|nullable|string|max:20',
            'huesped_nombre' => 'nullable|string|max:150',
        ], [
            'tipo_salida.required_if'    => 'Selecciona el motivo de la salida.',
            'habitacion.required_if'     => 'La habitación es obligatoria cuando la salida es por solicitud de huésped.',
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
            'tipo_salida'        => $data['tipo'] === 'salida' ? $data['tipo_salida'] : null,
            'habitacion'         => $data['habitacion'] ?? null,
            'huesped_nombre'     => $data['huesped_nombre'] ?? null,
            'cantidad'           => $delta,
            'cantidad_anterior'  => $cantidadAnterior,
            'cantidad_nueva'     => $cantidadNueva,
            'motivo'             => $data['motivo'],
        ]);

        $tipoLabel = $data['tipo'] === 'entrada' ? 'Entrada' : 'Salida';
        return back()->with('success', "{$tipoLabel} de {$delta} {$item->unidad} registrada. Stock actual: {$cantidadNueva}.");
    }

    // ─────────────────────────────────────────────────────────
    // REPORTES: productos más usados (salidas)
    // ─────────────────────────────────────────────────────────
    public function reportes(Request $request)
    {
        $desde = $request->filled('desde') ? $request->date('desde') : now()->subDays(30);
        $hasta = $request->filled('hasta') ? $request->date('hasta') : now();

        $hotelCode = $this->hotelActivo();

        $topProductos = InventarioMovimiento::query()
            ->join('inventario_items', 'inventario_items.id', '=', 'inventario_movimientos.inventario_item_id')
            ->where('inventario_movimientos.hotel_code', $hotelCode)
            ->where('inventario_movimientos.tipo', 'salida')
            ->whereBetween('inventario_movimientos.created_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->when($request->filled('area'), fn($q) => $q->where('inventario_items.area', $request->area))
            ->selectRaw('inventario_items.id, inventario_items.nombre, inventario_items.unidad, inventario_items.area,
                         SUM(inventario_movimientos.cantidad) as total_salidas,
                         COUNT(*) as veces_solicitado')
            ->groupBy('inventario_items.id', 'inventario_items.nombre', 'inventario_items.unidad', 'inventario_items.area')
            ->orderByDesc('total_salidas')
            ->limit(10)
            ->get();

        $porTipoSalida = InventarioMovimiento::query()
            ->where('hotel_code', $hotelCode)
            ->where('tipo', 'salida')
            ->whereBetween('created_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->selectRaw('COALESCE(tipo_salida, "sin_clasificar") as tipo_salida, SUM(cantidad) as total')
            ->groupBy('tipo_salida')
            ->get();

        return view('inventory.reportes', [
            'topProductos'   => $topProductos,
            'porTipoSalida'  => $porTipoSalida,
            'areas'          => self::AREAS,
            'desde'          => $desde->format('Y-m-d'),
            'hasta'          => $hasta->format('Y-m-d'),
            'filtroArea'     => $request->area,
        ]);
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
            'disponible_huesped' => 'nullable|boolean',
        ]);

        $data['disponible_huesped'] = $request->boolean('disponible_huesped');

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

        if (!auth()->user()->can('delete', $item)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar artículos del inventario.');
        }

        // Snapshot antes de borrar: el registro (y sus movimientos, por cascade)
        // desaparecen de la BD, así que este es el único rastro que queda.
        $snapshot = $item->only(['id', 'hotel_code', 'area', 'nombre', 'categoria', 'cantidad', 'status']);

        AuditLog::registrar(
            'inventario.eliminado',
            "Eliminó del inventario el artículo \"{$item->nombre}\" ({$item->area}), con {$item->cantidad} {$item->unidad} en existencia.",
            $item,
            $snapshot,
            null
        );

        if ($item->imagen_path) Storage::disk('public')->delete($item->imagen_path);
        if ($item->pdf_path)    Storage::disk('public')->delete($item->pdf_path);

        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Artículo eliminado del inventario.');
    }
}