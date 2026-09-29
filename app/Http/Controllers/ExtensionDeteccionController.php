<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\ExtensionDeteccion;
use App\Services\Recepcion\ExtensionDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExtensionDeteccionController extends Controller
{
    use ResuelveHotelActivo;

    public function index()
    {
        $detecciones = ExtensionDeteccion::where('hotel_code', $this->hotelActivo())
            ->with('usuario')
            ->latest()
            ->paginate(15);

        return view('recepcion.extensiones.index', compact('detecciones'));
    }

    public function create()
    {
        return view('recepcion.extensiones.create');
    }

    /**
     * Sube y compara los dos PDF (salidas / llegadas). Igual que en
     * EmailValidacionController: los PDF nunca se guardan en disco, solo
     * el resultado ya procesado.
     */
    public function store(Request $request, ExtensionDetectorService $servicio)
    {
        $request->validate([
            'salidas'  => 'required|file|mimes:pdf|max:20480',
            'llegadas' => 'required|file|mimes:pdf|max:20480',
        ], [
            'salidas.required'  => 'Selecciona el PDF de salidas (departures).',
            'llegadas.required' => 'Selecciona el PDF de llegadas (arrivals).',
            'salidas.mimes'     => 'El archivo de salidas debe ser un PDF.',
            'llegadas.mimes'    => 'El archivo de llegadas debe ser un PDF.',
            'salidas.max'       => 'El PDF de salidas no debe superar 20 MB.',
            'llegadas.max'      => 'El PDF de llegadas no debe superar 20 MB.',
        ]);

        try {
            $salidasDict  = $servicio->extraerNombresPdf($request->file('salidas')->getRealPath());
            $llegadasDict = $servicio->extraerNombresPdf($request->file('llegadas')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['salidas' => 'No se pudieron leer los PDF: ' . $e->getMessage()]);
        }

        // Red de seguridad: comparar cada nombre de salidas contra cada
        // nombre de llegadas es O(salidas × llegadas). Con listas muy
        // grandes eso puede tardar varios segundos y arriesgar el límite de
        // tiempo de ejecución de PHP — mejor avisarle al usuario de una vez
        // que dividir el PDF, que dejar la petición colgada o que truene a
        // medias.
        $maxHuespedes = ExtensionDetectorService::MAX_HUESPEDES_POR_LISTA;
        if (count($salidasDict) > $maxHuespedes || count($llegadasDict) > $maxHuespedes) {
            return back()->withErrors([
                'salidas' => sprintf(
                    'Cada lista debe tener como máximo %d huéspedes para comparar de una sola vez (salidas: %d, llegadas: %d). Divide el PDF en partes más pequeñas e inténtalo de nuevo.',
                    $maxHuespedes,
                    count($salidasDict),
                    count($llegadasDict)
                ),
            ]);
        }

        try {
            $coincidencias = $servicio->compararListasHuespedes($salidasDict, $llegadasDict);
        } catch (\Throwable $e) {
            return back()->withErrors(['salidas' => 'No se pudieron comparar las listas: ' . $e->getMessage()]);
        }

        $nombreSalidas  = $request->file('salidas')->getClientOriginalName();
        $nombreLlegadas = $request->file('llegadas')->getClientOriginalName();

        $deteccion = DB::transaction(function () use ($coincidencias, $nombreSalidas, $nombreLlegadas) {
            $deteccion = ExtensionDeteccion::create([
                'user_id'             => auth()->id(),
                'archivo_salidas'     => $nombreSalidas,
                'archivo_llegadas'    => $nombreLlegadas,
                'total_coincidencias' => count($coincidencias),
            ]);

            foreach ($coincidencias as $c) {
                $deteccion->items()->create([
                    'huesped_salida'  => $c['salida'],
                    'huesped_llegada' => $c['llegada'],
                    'similitud'       => $c['similitud'],
                ]);
            }

            return $deteccion;
        });

        AuditLog::registrar(
            'recepcion.extension_deteccion.creada',
            "Comparó \"{$deteccion->archivo_salidas}\" vs \"{$deteccion->archivo_llegadas}\" ({$deteccion->total_coincidencias} posibles extensiones).",
            $deteccion
        );

        $mensaje = $deteccion->total_coincidencias > 0
            ? "Análisis completo: {$deteccion->total_coincidencias} posibles extensiones encontradas."
            : 'Análisis completo: no se encontraron posibles extensiones de estancia.';

        return redirect()->route('recepcion.extensiones.show', $deteccion)->with('success', $mensaje);
    }

    public function show(Request $request, ExtensionDeteccion $deteccion)
    {
        $query = $deteccion->items();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('huesped_salida', 'like', "%{$buscar}%")
                  ->orWhere('huesped_llegada', 'like', "%{$buscar}%");
            });
        }

        $items = $query->orderByDesc('similitud')->paginate(50)->withQueryString();

        return view('recepcion.extensiones.show', [
            'deteccion' => $deteccion->load('usuario'),
            'items'     => $items,
            'filtros'   => $request->only(['buscar']),
        ]);
    }

    public function destroy(ExtensionDeteccion $deteccion)
    {
        Gate::authorize('delete', $deteccion);

        $salidas  = $deteccion->archivo_salidas;
        $llegadas = $deteccion->archivo_llegadas;
        $deteccion->delete();

        AuditLog::registrar(
            'recepcion.extension_deteccion.eliminada',
            "Eliminó la comparación \"{$salidas}\" vs \"{$llegadas}\".",
            null,
            ['archivo_salidas' => $salidas, 'archivo_llegadas' => $llegadas]
        );

        return redirect()->route('recepcion.extensiones.index')->with('success', 'Comparación eliminada.');
    }
}
