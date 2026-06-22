<?php

namespace App\Http\Controllers;

use App\Models\Asset; 
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
        if($asset->pdf_path) Storage::disk('public')->delete($asset->pdf_path);
        if($asset->imagen_path) Storage::disk('public')->delete($asset->imagen_path);
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Activo eliminado.'); 
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
        return view('peripherals.index', compact('groupedAssets'));
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