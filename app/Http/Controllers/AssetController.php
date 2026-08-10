<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /**
     * Helper privado para evitar repetir el enorme arreglo de validación
     */
    private function validateAsset(Request $request, $id = null)
    {
        return $request->validate([
            'nombre'            => 'nullable|string|max:255', 
            // Si hay un ID (update), ignora ese registro para la validación unique
            'nomenclatura'      => 'required|unique:assets,nomenclatura,' . ($id ?? 'NULL'),
            'area'              => 'required|string',
            'encargado'         => 'nullable|string',
            'ip_address'        => 'nullable|ip', // Valida que tenga formato IP real si se introduce
            'status'            => 'nullable|string',
            'notas'             => 'nullable|string',
            'pdf_protocolo'     => 'nullable|mimes:pdf|max:5120',
            'imagen'            => 'nullable|image|max:5120',
            'division'          => 'nullable|string',
            'tipo_equipo'       => 'nullable|string',
            'marca'             => 'nullable|string',
            'modelo'            => 'nullable|string',
            'numero_serie'      => 'nullable|string',
            'fecha_adquisicion' => 'nullable|date',
            'factura'           => 'nullable|string',
            'procesador'        => 'nullable|string',
            'ram'               => 'nullable|string',
            'tipo_ram'          => 'nullable|string',
            'almacenamiento'    => 'nullable|string',
            'sistema_operativo' => 'nullable|string',
            'paqueteria_office' => 'nullable|string',
            'monitor_marca'     => 'nullable|string',
            'monitor_modelo'    => 'nullable|string',
            'monitor_ns'        => 'nullable|string',
            'mouse_marca'       => 'nullable|string',
            'mouse_modelo'      => 'nullable|string',
            'mouse_ns'          => 'nullable|string',
            'teclado_marca'     => 'nullable|string',
            'teclado_modelo'    => 'nullable|string',
            'teclado_ns'        => 'nullable|string',
        ]);
    }

    public function index()
    {
        $assets = Asset::latest()->get(); 
        return view('assets.index', compact('assets'));
    }

    public function create()
    {
        return view('assets.create');
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

        Asset::create($data);
        return redirect()->route('assets.index')->with('success', 'Activo creado con éxito.');
    }

    public function show($id)
    {
        $asset = Asset::findOrFail($id);
        return view('assets.show', compact('asset'));
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        
        // Pasamos el ID para permitir que mantenga su propia nomenclatura
        $data = $this->validateAsset($request, $id);

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

        $asset->update($data);

        // 🛠️ Redirección lógica e inteligente al detalle del Asset editado
        return redirect()->route('assets.show', $id)->with('success', 'Activo actualizado correctamente.'); 
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);

        if (!auth()->user()->can('delete', $asset)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar activos.');
        }

        // Snapshot antes de borrar: una vez eliminado el activo no queda
        // ningún otro registro de que existió ni de quién lo dio de baja.
        $snapshot = $asset->only(['id', 'nomenclatura', 'nombre', 'area', 'division', 'encargado', 'status']);

        AuditLog::registrar(
            'asset.eliminado',
            "Eliminó el activo \"{$asset->nomenclatura}\" ({$asset->area}).",
            $asset,
            $snapshot,
            null
        );

        if($asset->pdf_path) Storage::disk('public')->delete($asset->pdf_path);
        if($asset->imagen_path) Storage::disk('public')->delete($asset->imagen_path);
        $asset->delete();
        return redirect()->back()->with('success', 'Activo eliminado.');
    }

    public function inventory()
    {
        $assets = Asset::all();
        $total = $assets->count();
        $activos = $assets->where('status', 'Activo')->count();
        $reparacion = $assets->where('status', 'En Reparación')->count();
        $baja = $assets->where('status', 'Baja')->count();

        return view('inventory.index', compact('assets', 'total', 'activos', 'reparacion', 'baja'));
    }
    
    public function perifericos()
    {
        $groupedAssets = Asset::orderBy('division')->get()->groupBy('division');
        $divisiones    = Asset::select('division')->distinct()->whereNotNull('division')->where('division', '!=', '')->orderBy('division')->pluck('division');
        $areas         = Asset::select('area')->distinct()->whereNotNull('area')->where('area', '!=', '')->orderBy('area')->pluck('area');
        return view('peripherals.index', compact('groupedAssets', 'divisiones', 'areas'));
    }

    public function cambiarDivision(Request $request, $id)
    {
        $request->validate(['division' => 'nullable|string|max:255']);
        Asset::findOrFail($id)->update(['division' => $request->input('division')]);
        return response()->json(['ok' => true]);
    }

    /**
     * Mueve varios equipos a la vez a una nueva División y/o Área.
     * Pensado para reubicaciones masivas (evita editar equipo por equipo).
     */
    public function moverLote(Request $request)
    {
        $data = $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer|exists:assets,id',
            'division' => 'nullable|string|max:255',
            'area'     => 'nullable|string|max:255',
        ]);

        $update = array_filter([
            'division' => $data['division'] ?? null,
            'area'     => $data['area'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        if (empty($update)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Indica una nueva División o un nuevo Puesto/Área.',
            ], 422);
        }

        $actualizados = Asset::whereIn('id', $data['ids'])->update($update);

        return response()->json(['ok' => true, 'actualizados' => $actualizados]);
    }

    public function importExcel(Request $request)
    {
        $rows  = $request->input('rows', []);
        $importados = 0;
        $errores    = 0;
        $mensajes   = [];

        foreach ($rows as $row) {
            $nomenclatura = trim($row['nomenclatura'] ?? '');
            if ($nomenclatura === '') {
                $errores++;
                continue;
            }

            try {
                // 'nombre' es NOT NULL en la BD; usamos nomenclatura como fallback
                $nombre = trim($row['nombre'] ?? '') ?: $nomenclatura;
                // 'area' es NOT NULL; usamos encargado o un placeholder si viene vacío
                $area   = trim($row['area'] ?? '') ?: 'Sin asignar';

                $campos = [
                    'nombre'            => $nombre,
                    'area'              => $area,
                    'division'          => $row['division']          ?? null,
                    'encargado'         => $row['encargado']         ?? null,
                    'tipo_equipo'       => $row['tipo_equipo']       ?? null,
                    'marca'             => $row['marca']             ?? null,
                    'modelo'            => $row['modelo']            ?? null,
                    'numero_serie'      => $row['numero_serie']      ?? null,
                    'procesador'        => $row['procesador']        ?? null,
                    'ram'               => $row['ram']               ?? null,
                    'tipo_ram'          => $row['tipo_ram']          ?? null,
                    'almacenamiento'    => $row['almacenamiento']    ?? null,
                    'ip_address'        => $row['ip_address']        ?? null,
                    'sistema_operativo' => $row['sistema_operativo'] ?? null,
                    'paqueteria_office' => $row['paqueteria_office'] ?? null,
                    'monitor_marca'     => $row['monitor_marca']     ?? null,
                    'monitor_modelo'    => $row['monitor_modelo']    ?? null,
                    'monitor_ns'        => $row['monitor_ns']        ?? null,
                    'mouse_marca'       => $row['mouse_marca']       ?? null,
                    'mouse_modelo'      => $row['mouse_modelo']      ?? null,
                    'mouse_ns'          => $row['mouse_ns']          ?? null,
                    'teclado_marca'     => $row['teclado_marca']     ?? null,
                    'teclado_modelo'    => $row['teclado_modelo']    ?? null,
                    'teclado_ns'        => $row['teclado_ns']        ?? null,
                ];

                // Eliminar campos vacíos para no sobreescribir datos existentes con ""
                $camposLimpios = array_filter($campos, fn($v) => $v !== null && $v !== '');

                Asset::updateOrCreate(
                    ['nomenclatura' => $nomenclatura],
                    $camposLimpios
                );
                $importados++;
            } catch (\Exception $e) {
                $errores++;
                $mensajes[] = $nomenclatura . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'importados' => $importados,
            'errores'    => $errores,
            'mensajes'   => $mensajes,
        ]);
    }

    public function exportExcel()
    {
        $fileName = 'Inventario_TI_Emporio.csv';
        $assets = Asset::orderBy('division')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Division', 'Nombre PC', 'Puesto', 'Nombre Completo Asignacion',
            'Tipo', 'Marca', 'Modelo', 'Serie', 'Procesador', 'Ram (Gb)', 'Tipo Ram', 'Almacenamiento', 'IP',
            'OS', 'Office', 'Monitor Marca', 'Monitor Modelo', 'Monitor N/S',
            'Mouse Marca', 'Mouse Modelo', 'Mouse N/S', 'Teclado Marca', 'Teclado Modelo', 'Teclado N/S'
        ];

        $callback = function() use($assets, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($assets as $asset) {
                fputcsv($file, [
                    $asset->division, $asset->nomenclatura, $asset->area, $asset->encargado,
                    $asset->tipo_equipo, $asset->marca, $asset->modelo, $asset->numero_serie, 
                    $asset->procesador, $asset->ram, $asset->tipo_ram, $asset->almacenamiento, $asset->ip_address,
                    $asset->sistema_operativo, $asset->paqueteria_office, 
                    $asset->monitor_marca, $asset->monitor_modelo, $asset->monitor_ns,
                    $asset->mouse_marca, $asset->mouse_modelo, $asset->mouse_ns, 
                    $asset->teclado_marca, $asset->teclado_modelo, $asset->teclado_ns
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}