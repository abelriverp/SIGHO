<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Catalogo unico de activos (Fase 1, nov 2026). Ver App\Models\Asset.
 */
class AssetController extends Controller
{
    /** Columnas del CSV de importacion/exportacion, en orden. */
    private const COLUMNAS_CSV = [
        'nomenclatura'      => 'Nomenclatura',
        'nombre'            => 'Nombre',
        'categoria'         => 'Categoria',
        'tipo_equipo'       => 'Tipo',
        'marca'             => 'Marca',
        'modelo'            => 'Modelo',
        'numero_serie'      => 'Serie',
        'status'            => 'Estado',
        'ubicacion'         => 'Ubicacion (codigo)',
        'area'              => 'Area / Puesto',
        'division'          => 'Division',
        'encargado'         => 'Encargado',
        'padre'             => 'Ligado a (nomenclatura)',
        'ip_address'        => 'IP',
        'mac_address'       => 'MAC',
        'procesador'        => 'Procesador',
        'ram'               => 'RAM (GB)',
        'tipo_ram'          => 'Tipo RAM',
        'almacenamiento'    => 'Almacenamiento',
        'sistema_operativo' => 'Sistema operativo',
        'paqueteria_office' => 'Office',
        'proveedor'         => 'Proveedor',
        'factura'           => 'Factura',
        'costo'             => 'Costo',
        'fecha_adquisicion' => 'Fecha de compra (AAAA-MM-DD)',
        'garantia_hasta'    => 'Garantia hasta (AAAA-MM-DD)',
        'notas'             => 'Notas',
    ];

    private function validateAsset(Request $request, ?Asset $asset = null): array
    {
        $data = $request->validate([
            'nombre'            => 'nullable|string|max:255',
            'nomenclatura'      => ['required', 'string', 'max:255', Rule::unique('assets', 'nomenclatura')->ignore($asset?->id)],
            'categoria'         => ['required', Rule::in(Asset::CATEGORIAS)],
            'tipo_equipo'       => 'nullable|string|max:100',
            'status'            => ['required', Rule::in(Asset::ESTADOS)],
            'ubicacion_id'      => 'nullable|integer|exists:ubicaciones,id',
            'area'              => 'nullable|string|max:255',
            'division'          => 'nullable|string|max:255',
            'encargado'         => 'nullable|string|max:255',
            'asignado_a'        => 'nullable|integer|exists:users,id',
            'parent_id'         => 'nullable|integer|exists:assets,id',
            'ip_address'        => 'nullable|ip',
            'mac_address'       => ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
            'marca'             => 'nullable|string|max:255',
            'modelo'            => 'nullable|string|max:255',
            'numero_serie'      => 'nullable|string|max:255',
            'procesador'        => 'nullable|string|max:255',
            'ram'               => 'nullable|string|max:50',
            'tipo_ram'          => 'nullable|string|max:50',
            'almacenamiento'    => 'nullable|string|max:100',
            'sistema_operativo' => 'nullable|string|max:100',
            'paqueteria_office' => 'nullable|string|max:100',
            'proveedor'         => 'nullable|string|max:150',
            'factura'           => 'nullable|string|max:100',
            'costo'             => 'nullable|numeric|min:0|max:9999999999',
            'fecha_adquisicion' => 'nullable|date',
            'garantia_hasta'    => 'nullable|date',
            'fecha_baja'        => 'nullable|date',
            'motivo_baja'       => 'nullable|string|max:2000',
            'notas'             => 'nullable|string|max:5000',
            'pdf_protocolo'     => 'nullable|mimes:pdf|max:5120',
            'imagen'            => 'nullable|image|max:5120',
        ], [
            'mac_address.regex' => 'La MAC debe tener el formato AA:BB:CC:DD:EE:FF.',
        ]);

        // La ubicacion, el usuario y el activo padre deben ser de esta
        // propiedad (la regla exists: no aplica HotelScope).
        $errores = [];
        if (! empty($data['ubicacion_id']) && ! Ubicacion::whereKey($data['ubicacion_id'])->exists()) {
            $errores['ubicacion_id'] = 'Ubicación no válida.';
        }
        if (! empty($data['parent_id'])) {
            if ($asset && (int) $data['parent_id'] === $asset->id) {
                $errores['parent_id'] = 'Un activo no puede estar ligado a sí mismo.';
            } elseif (! Asset::whereKey($data['parent_id'])->exists()) {
                $errores['parent_id'] = 'Activo padre no válido.';
            }
        }
        if (! empty($data['asignado_a'])) {
            $usuario = User::find($data['asignado_a']);
            if (! $usuario || ($usuario->hotel_code !== auth()->user()->hotel_code && ! auth()->user()->isAdmin())) {
                $errores['asignado_a'] = 'Usuario no válido.';
            }
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        // 'nombre' es NOT NULL: si no se captura, se arma con tipo/marca/modelo.
        $data['nombre'] = trim((string) ($data['nombre'] ?? '')) ?: (
            trim(implode(' ', array_filter([$data['tipo_equipo'] ?? null, $data['marca'] ?? null, $data['modelo'] ?? null]))) ?: $data['nomenclatura']
        );

        // El area libre se conserva como respaldo legible.
        $data['area'] = trim((string) ($data['area'] ?? '')) ?: (
            ! empty($data['ubicacion_id']) ? Ubicacion::find($data['ubicacion_id'])?->etiqueta : 'Sin asignar'
        );

        if ($data['status'] === 'Baja') {
            $data['fecha_baja'] = $data['fecha_baja'] ?? now()->toDateString();
        } else {
            $data['fecha_baja'] = null;
            $data['motivo_baja'] = null;
        }

        return $data;
    }

    private function datosFormulario(?Asset $asset = null): array
    {
        return [
            'ubicaciones' => Ubicacion::where('activa', true)->orderBy('tipo')->orderBy('codigo')->get(),
            'usuarios'    => User::where('hotel_code', auth()->user()->hotel_code)->orderBy('name')->get(['id', 'name', 'departamento']),
            'padres'      => Asset::whereNull('parent_id')
                ->when($asset, fn ($q) => $q->whereKeyNot($asset->id))
                ->whereIn('categoria', ['Cómputo', 'Punto de venta', 'TV y entretenimiento', 'Red', 'Seguridad'])
                ->orderBy('nomenclatura')
                ->get(['id', 'nomenclatura', 'nombre', 'categoria']),
            'categorias'  => Asset::CATEGORIAS,
            'estados'     => Asset::ESTADOS,
            'tipos'       => Asset::TIPOS_SUGERIDOS,
        ];
    }

    public function index(Request $request)
    {
        $filtros = $request->only(['buscar', 'categoria', 'status', 'ubicacion_id', 'garantia']);

        $query = Asset::with(['ubicacion', 'asignado:id,name', 'padre:id,nomenclatura'])
            ->withCount('componentes')
            ->when($filtros['categoria'] ?? null, fn ($q, $v) => $q->where('categoria', $v))
            ->when($filtros['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filtros['ubicacion_id'] ?? null, fn ($q, $v) => $q->where('ubicacion_id', $v))
            ->when(($filtros['garantia'] ?? null) === 'por_vencer', fn ($q) => $q->garantiaPorVencer(30))
            ->when(($filtros['garantia'] ?? null) === 'vencida', fn ($q) => $q->whereDate('garantia_hasta', '<', now()->toDateString()))
            ->when($filtros['buscar'] ?? null, function ($q, $v) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $v) . '%';
                $q->where(fn ($w) => $w->where('nomenclatura', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('marca', 'like', $like)
                    ->orWhere('modelo', 'like', $like)
                    ->orWhere('numero_serie', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhere('mac_address', 'like', $like)
                    ->orWhere('encargado', 'like', $like)
                    ->orWhere('area', 'like', $like));
            });

        $assets = $query->orderBy('categoria')->orderBy('nomenclatura')->paginate(50)->withQueryString();

        $resumen = [
            'total'      => Asset::count(),
            'activos'    => Asset::where('status', 'Activo')->count(),
            'reparacion' => Asset::where('status', 'En Reparación')->count(),
            'almacen'    => Asset::where('status', 'En almacén')->count(),
            'baja'       => Asset::where('status', 'Baja')->count(),
            'garantia'   => Asset::vigentes()->garantiaPorVencer(30)->count(),
        ];

        return view('assets.index', [
            'assets'      => $assets,
            'filtros'     => $filtros,
            'resumen'     => $resumen,
            'categorias'  => Asset::CATEGORIAS,
            'estados'     => Asset::ESTADOS,
            'ubicaciones' => Ubicacion::orderBy('tipo')->orderBy('codigo')->get(['id', 'tipo', 'codigo', 'nombre']),
        ]);
    }

    public function create(Request $request)
    {
        $asset = new Asset([
            'categoria'    => $request->query('categoria', 'Cómputo'),
            'status'       => 'Activo',
            'ubicacion_id' => $request->query('ubicacion_id'),
            'parent_id'    => $request->query('parent_id'),
        ]);

        return view('assets.create', array_merge(['asset' => $asset], $this->datosFormulario()));
    }

    public function store(Request $request)
    {
        $data = $this->validateAsset($request);

        if ($request->hasFile('pdf_protocolo')) {
            $data['pdf_path'] = $request->file('pdf_protocolo')->store('protocolos', 'public');
        }
        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('assets_fotos', 'public');
        }
        unset($data['pdf_protocolo'], $data['imagen']);

        $asset = Asset::create($data);

        AuditLog::registrar('asset.creado', "Registró el activo \"{$asset->nomenclatura}\".", $asset, null, $asset->only(['nomenclatura', 'categoria', 'status', 'ubicacion_id']));

        return redirect()->route('assets.show', $asset->id)->with('success', 'Activo registrado.');
    }

    public function show($id)
    {
        $asset = Asset::with(['ubicacion', 'asignado', 'padre', 'componentes.ubicacion', 'revisions' => fn ($q) => $q->latest()])
            ->findOrFail($id);

        return view('assets.show', compact('asset'));
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        return view('assets.edit', array_merge(['asset' => $asset], $this->datosFormulario($asset)));
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $this->validateAsset($request, $asset);

        if ($request->has('delete_pdf') && $asset->pdf_path) {
            Storage::disk('public')->delete($asset->pdf_path);
            $data['pdf_path'] = null;
        }
        if ($request->hasFile('pdf_protocolo')) {
            if ($asset->pdf_path) Storage::disk('public')->delete($asset->pdf_path);
            $data['pdf_path'] = $request->file('pdf_protocolo')->store('protocolos', 'public');
        }
        if ($request->hasFile('imagen')) {
            if ($asset->imagen_path) Storage::disk('public')->delete($asset->imagen_path);
            $data['imagen_path'] = $request->file('imagen')->store('assets_fotos', 'public');
        }
        unset($data['pdf_protocolo'], $data['imagen']);

        $antes = $asset->only(['status', 'ubicacion_id', 'asignado_a', 'parent_id']);
        $asset->update($data);
        $despues = $asset->only(['status', 'ubicacion_id', 'asignado_a', 'parent_id']);

        if ($antes != $despues) {
            AuditLog::registrar('asset.movido', "Cambió estado, ubicación, resguardo o liga del activo \"{$asset->nomenclatura}\".", $asset, $antes, $despues);
        }

        return redirect()->route('assets.show', $asset->id)->with('success', 'Activo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);

        if (! auth()->user()->can('delete', $asset)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar activos.');
        }

        $snapshot = $asset->only(['id', 'nomenclatura', 'nombre', 'categoria', 'area', 'ubicacion_id', 'encargado', 'status']);

        AuditLog::registrar(
            'asset.eliminado',
            "Eliminó el activo \"{$asset->nomenclatura}\" ({$asset->area}).",
            $asset,
            $snapshot,
            null
        );

        if ($asset->pdf_path) Storage::disk('public')->delete($asset->pdf_path);
        if ($asset->imagen_path) Storage::disk('public')->delete($asset->imagen_path);
        $asset->delete();

        return redirect()->route('assets.index')->with('success', 'Activo eliminado.');
    }

    /**
     * Mueve varios activos a la vez: nueva ubicacion, area, division y/o estado.
     */
    public function moverLote(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array|min:1',
            'ids.*'        => 'integer|exists:assets,id',
            'ubicacion_id' => 'nullable|integer|exists:ubicaciones,id',
            'division'     => 'nullable|string|max:255',
            'area'         => 'nullable|string|max:255',
            'status'       => ['nullable', Rule::in(Asset::ESTADOS)],
        ]);

        $update = array_filter([
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'division'     => $data['division'] ?? null,
            'area'         => $data['area'] ?? null,
            'status'       => $data['status'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (empty($update)) {
            $mensaje = 'Indica una nueva ubicación, área, división o estado.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $mensaje], 422)
                : back()->with('error', $mensaje);
        }

        if (isset($update['ubicacion_id']) && ! Ubicacion::whereKey($update['ubicacion_id'])->exists()) {
            throw ValidationException::withMessages(['ubicacion_id' => 'Ubicación no válida.']);
        }

        // whereIn sobre el modelo respeta HotelScope: solo mueve activos visibles.
        $actualizados = Asset::whereIn('id', $data['ids'])->update($update);

        AuditLog::registrar('asset.movido_lote', "Movió {$actualizados} activo(s) en lote.", null, null, $update + ['ids' => $data['ids']]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'actualizados' => $actualizados])
            : back()->with('success', "{$actualizados} activo(s) actualizados.");
    }

    /** Plantilla CSV vacia para captura masiva. */
    public function plantillaCsv()
    {
        return response()->streamDownload(function () {
            $f = fopen('php://output', 'w');
            fputs($f, "\xEF\xBB\xBF");
            fputcsv($f, array_values(self::COLUMNAS_CSV));
            fputcsv($f, ['CUN-PC-001', 'PC Recepción 1', 'Cómputo', 'CPU', 'Dell', 'OptiPlex 7010', 'ABC123', 'Activo', 'RECEPCION', 'Recepción', 'Front Office', 'Nombre Apellido', '', '10.7.64.50', '', 'Core i5', '16', 'DDR4', '512 GB SSD', 'Windows 11 Pro', 'Office 2021', 'Proveedor SA', 'F-001', '15000', '2026-01-15', '2029-01-15', '']);
            fputcsv($f, ['CUN-MON-001', 'Monitor Recepción 1', 'Periférico', 'Monitor', 'Dell', 'P2422H', 'MON123', 'Activo', 'RECEPCION', 'Recepción', 'Front Office', '', 'CUN-PC-001', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Ligado al CPU CUN-PC-001']);
            fclose($f);
        }, 'Plantilla_Activos_SIGHO.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Importa activos desde CSV (misma estructura que la plantilla).
     * Crea o actualiza por nomenclatura. Ubicaciones inexistentes se crean
     * como tipo "area". Las ligas a padre se resuelven en una segunda pasada.
     */
    public function importCsv(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:csv,txt|max:5120']);

        $handle = fopen($request->file('archivo')->getRealPath(), 'r');
        $primera = fgets($handle);
        $delimitador = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        rewind($handle);

        $encabezado = fgetcsv($handle, 0, $delimitador);
        if (! $encabezado) {
            return back()->with('error', 'El archivo está vacío.');
        }
        $encabezado[0] = preg_replace('/^\xEF\xBB\xBF/', '', $encabezado[0]);
        $mapa = array_flip(self::COLUMNAS_CSV);
        $columnas = array_map(fn ($h) => $mapa[trim($h)] ?? null, $encabezado);

        if (! in_array('nomenclatura', $columnas, true)) {
            return back()->with('error', 'El archivo no tiene la columna "Nomenclatura". Descarga la plantilla y úsala como base.');
        }

        $creados = $actualizados = 0;
        $errores = [];
        $ligas = [];
        $fila = 1;

        while (($valores = fgetcsv($handle, 0, $delimitador)) !== false) {
            $fila++;
            if (count(array_filter($valores, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($columnas as $i => $col) {
                if ($col) {
                    $row[$col] = trim((string) ($valores[$i] ?? ''));
                }
            }

            $nomenclatura = $row['nomenclatura'] ?? '';
            if ($nomenclatura === '') {
                $errores[] = "Fila {$fila}: sin nomenclatura.";
                continue;
            }

            $categoria = $row['categoria'] ?? '';
            if ($categoria !== '' && ! in_array($categoria, Asset::CATEGORIAS, true)) {
                $errores[] = "Fila {$fila} ({$nomenclatura}): categoría \"{$categoria}\" no válida.";
                continue;
            }
            $estado = $row['status'] ?? '';
            if ($estado !== '' && ! in_array($estado, Asset::ESTADOS, true)) {
                $errores[] = "Fila {$fila} ({$nomenclatura}): estado \"{$estado}\" no válido.";
                continue;
            }

            $ubicacionId = null;
            if (($row['ubicacion'] ?? '') !== '') {
                $ubicacionId = Ubicacion::firstOrCreate(
                    ['codigo' => mb_strtoupper($row['ubicacion']), 'hotel_code' => auth()->user()->hotel_code],
                    ['tipo' => ctype_digit($row['ubicacion']) ? 'habitacion' : 'area']
                )->id;
            }

            $campos = array_filter([
                'nombre'            => $row['nombre'] ?? null,
                'categoria'         => $categoria ?: null,
                'tipo_equipo'       => $row['tipo_equipo'] ?? null,
                'marca'             => $row['marca'] ?? null,
                'modelo'            => $row['modelo'] ?? null,
                'numero_serie'      => $row['numero_serie'] ?? null,
                'status'            => $estado ?: null,
                'ubicacion_id'      => $ubicacionId,
                'area'              => $row['area'] ?? null,
                'division'          => $row['division'] ?? null,
                'encargado'         => $row['encargado'] ?? null,
                'ip_address'        => filter_var($row['ip_address'] ?? '', FILTER_VALIDATE_IP) ?: null,
                'mac_address'       => $row['mac_address'] ?? null,
                'procesador'        => $row['procesador'] ?? null,
                'ram'               => $row['ram'] ?? null,
                'tipo_ram'          => $row['tipo_ram'] ?? null,
                'almacenamiento'    => $row['almacenamiento'] ?? null,
                'sistema_operativo' => $row['sistema_operativo'] ?? null,
                'paqueteria_office' => $row['paqueteria_office'] ?? null,
                'proveedor'         => $row['proveedor'] ?? null,
                'factura'           => $row['factura'] ?? null,
                'costo'             => is_numeric(str_replace(',', '', $row['costo'] ?? '')) ? str_replace(',', '', $row['costo']) : null,
                'fecha_adquisicion' => $this->fecha($row['fecha_adquisicion'] ?? ''),
                'garantia_hasta'    => $this->fecha($row['garantia_hasta'] ?? ''),
                'notas'             => $row['notas'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            try {
                $asset = Asset::where('nomenclatura', $nomenclatura)->first();
                if ($asset) {
                    $asset->update($campos);
                    $actualizados++;
                } else {
                    $asset = Asset::create($campos + [
                        'nomenclatura' => $nomenclatura,
                        'nombre'       => $campos['nombre'] ?? $nomenclatura,
                        'categoria'    => $campos['categoria'] ?? 'Cómputo',
                        'status'       => $campos['status'] ?? 'Activo',
                        'area'         => $campos['area'] ?? 'Sin asignar',
                    ]);
                    $creados++;
                }
                if (($row['padre'] ?? '') !== '') {
                    $ligas[$asset->id] = $row['padre'];
                }
            } catch (\Throwable $e) {
                $errores[] = "Fila {$fila} ({$nomenclatura}): " . $e->getMessage();
            }
        }
        fclose($handle);

        foreach ($ligas as $id => $nomPadre) {
            $padre = Asset::where('nomenclatura', $nomPadre)->first();
            if ($padre && $padre->id !== $id) {
                Asset::whereKey($id)->update(['parent_id' => $padre->id]);
            } else {
                $errores[] = "No se encontró el activo padre \"{$nomPadre}\".";
            }
        }

        AuditLog::registrar('asset.importacion', "Importó activos desde CSV: {$creados} nuevos, {$actualizados} actualizados.");

        $mensaje = "Importación terminada: {$creados} nuevos, {$actualizados} actualizados.";
        return redirect()->route('assets.index')
            ->with($errores ? 'error' : 'success', $errores ? $mensaje . ' Revisar: ' . implode(' ', array_slice($errores, 0, 10)) . (count($errores) > 10 ? ' …' : '') : $mensaje);
    }

    private function fecha(string $valor): ?string
    {
        if ($valor === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            $d = \DateTime::createFromFormat('!' . $formato, $valor);
            if ($d) {
                return $d->format('Y-m-d');
            }
        }
        return null;
    }

    public function exportCsv()
    {
        $assets = Asset::with(['ubicacion', 'padre'])->orderBy('categoria')->orderBy('nomenclatura')->get();
        $hotel = config('app.hotel_code');

        return response()->streamDownload(function () use ($assets) {
            $f = fopen('php://output', 'w');
            fputs($f, "\xEF\xBB\xBF");
            fputcsv($f, array_values(self::COLUMNAS_CSV));
            foreach ($assets as $a) {
                $fila = [];
                foreach (array_keys(self::COLUMNAS_CSV) as $col) {
                    $fila[] = match ($col) {
                        'ubicacion'                         => $a->ubicacion?->codigo,
                        'padre'                             => $a->padre?->nomenclatura,
                        'fecha_adquisicion', 'garantia_hasta' => $a->{$col}?->format('Y-m-d'),
                        default                             => $a->{$col},
                    };
                }
                fputcsv($f, $fila);
            }
            fclose($f);
        }, "Activos_{$hotel}_" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
