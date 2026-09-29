<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\PerfilCorreoValidacion;
use App\Models\PerfilCorreoValidacionItem;
use App\Services\Recepcion\PerfilesCorreoValidatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Sube y analiza el reporte "Profiles with e-mails" del sistema de
 * reservas: a diferencia de EmailValidacionController (que solo junta
 * correos únicos), aquí cada resultado queda ligado a su número de
 * reserva y huésped — para que el resumen diga "la reserva X con el
 * correo Y tiene tal problema", que es lo que pidió Recepción/Calidad.
 */
class PerfilCorreoValidacionController extends Controller
{
    use ResuelveHotelActivo;

    public function index()
    {
        $validaciones = PerfilCorreoValidacion::where('hotel_code', $this->hotelActivo())
            ->with('usuario')
            ->latest()
            ->paginate(15);

        return view('recepcion.perfiles.index', compact('validaciones'));
    }

    public function create()
    {
        return view('recepcion.perfiles.create');
    }

    /**
     * El PDF subido NUNCA se guarda en disco — se lee de su ruta temporal
     * y se descarta al terminar la petición, igual que
     * EmailValidacionController::store().
     *
     * La verificación SMTP (ver PerfilesCorreoValidatorService) es
     * best-effort: intenta conectarse por la red al servidor de correo de
     * cada dominio propio/corporativo (nunca a Gmail/Hotmail/Yahoo y
     * similares, que no confirman buzones). Si la red de este servidor
     * bloquea el puerto 25 saliente —algo común en muchos proveedores de
     * internet— esos correos simplemente quedarán "No concluyente" en vez
     * de fallar la subida.
     */
    public function store(Request $request, PerfilesCorreoValidatorService $servicio)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ], [
            'pdf.required' => 'Selecciona el PDF de "Profiles with e-mails" a analizar.',
            'pdf.mimes'    => 'El archivo debe ser un PDF.',
            'pdf.max'      => 'El PDF no debe superar 20 MB.',
        ]);

        try {
            $filas = $servicio->procesarPdf($request->file('pdf')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['pdf' => 'No se pudo leer el PDF: ' . $e->getMessage()]);
        }

        if (empty($filas)) {
            return back()->withErrors(['pdf' => 'No se encontraron reservas en este PDF. Verifica que sea el reporte "Profiles with e-mails".']);
        }

        $nombreArchivo = $request->file('pdf')->getClientOriginalName();

        $validacion = DB::transaction(function () use ($filas, $nombreArchivo) {
            // Mismos grupos que alimentan el filtro del detalle — un solo
            // mapa de verdad (ver comentario en PerfilCorreoValidacionItem::GRUPOS_FILTRO).
            $contadores = array_fill_keys(array_keys(PerfilCorreoValidacionItem::GRUPOS_FILTRO), 0);
            foreach ($filas as $f) {
                foreach (PerfilCorreoValidacionItem::GRUPOS_FILTRO as $clave => $grupo) {
                    if (in_array($f['estado'], $grupo['estados'], true)) {
                        $contadores[$clave]++;
                        break;
                    }
                }
            }

            $validacion = PerfilCorreoValidacion::create([
                'user_id'          => auth()->id(),
                'archivo_nombre'   => $nombreArchivo,
                'total'            => count($filas),
                'ok'               => $contadores['OK'],
                'sin_correo'       => $contadores['SIN_CORREO'],
                'sufijo_invalido'  => $contadores['SUFIJO_INVALIDO'],
                'dominio_invalido' => $contadores['DOMINIO_INVALIDO'],
                'rechazado_smtp'   => $contadores['RECHAZADO_SMTP'],
                'otros'            => $contadores['OTROS'],
            ]);

            foreach ($filas as $f) {
                $validacion->items()->create([
                    'numero_reserva'    => $f['numero_reserva'],
                    'huesped'           => $f['huesped'],
                    'fecha_llegada'     => $f['fecha_llegada'] ?: null,
                    'fecha_salida'      => $f['fecha_salida'] ?: null,
                    'email'             => $f['email'],
                    'estado'            => $f['estado'],
                    'motivo'            => $f['motivo'],
                    'sugerencia'        => $f['sugerencia'],
                    'verificacion_smtp' => $f['verificacion_smtp'],
                ]);
            }

            return $validacion;
        });

        AuditLog::registrar(
            'recepcion.perfil_correo_validacion.creada',
            "Analizó \"{$validacion->archivo_nombre}\" ({$validacion->total} reservas).",
            $validacion
        );

        return redirect()->route('recepcion.perfiles.show', $validacion)
            ->with('success', "Análisis completo: {$validacion->total} reservas revisadas.");
    }

    public function show(Request $request, PerfilCorreoValidacion $validacion)
    {
        $query = $validacion->items();

        if ($request->filled('estado')) {
            $grupo = PerfilCorreoValidacionItem::GRUPOS_FILTRO[$request->estado] ?? null;
            if ($grupo) {
                $query->whereIn('estado', $grupo['estados']);
            }
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('email', 'like', "%{$buscar}%")
                    ->orWhere('huesped', 'like', "%{$buscar}%")
                    ->orWhere('numero_reserva', 'like', "%{$buscar}%");
            });
        }

        $items = $query->orderBy('numero_reserva')->paginate(50)->withQueryString();

        return view('recepcion.perfiles.show', [
            'validacion' => $validacion->load('usuario'),
            'items'      => $items,
            'filtros'    => $request->only(['estado', 'buscar']),
        ]);
    }

    /**
     * Mismo filtro que show() (respeta ?estado= y ?buscar= si el usuario
     * los tenía aplicados al pedir la descarga), pero SIN paginar: el PDF
     * es para archivar/imprimir/compartir con Calidad fuera de SIGHO, así
     * que debe llevar todas las filas que cumplan el filtro, no solo la
     * página que se estaba viendo en pantalla.
     */
    public function pdf(Request $request, PerfilCorreoValidacion $validacion)
    {
        $query = $validacion->items();

        if ($request->filled('estado')) {
            $grupo = PerfilCorreoValidacionItem::GRUPOS_FILTRO[$request->estado] ?? null;
            if ($grupo) {
                $query->whereIn('estado', $grupo['estados']);
            }
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('email', 'like', "%{$buscar}%")
                    ->orWhere('huesped', 'like', "%{$buscar}%")
                    ->orWhere('numero_reserva', 'like', "%{$buscar}%");
            });
        }

        $items = $query->orderBy('numero_reserva')->get();

        $propiedad = Hotel::where('hotel_code', $validacion->hotel_code)->value('nombre')
            ?? $validacion->hotel_code;

        $pdf = Pdf::loadView('recepcion.perfiles.pdf', [
            'validacion' => $validacion->load('usuario'),
            'items'      => $items,
            'filtros'    => $request->only(['estado', 'buscar']),
            'propiedad'  => $propiedad,
        ])->setPaper('letter', 'landscape');

        $nombreBase    = pathinfo($validacion->archivo_nombre, PATHINFO_FILENAME);
        $nombreArchivo = 'Correos_Llegada_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $nombreBase) . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    public function destroy(PerfilCorreoValidacion $validacion)
    {
        Gate::authorize('delete', $validacion);

        $nombre = $validacion->archivo_nombre;
        $validacion->delete(); // cascada borra los items (ver migración)

        AuditLog::registrar(
            'recepcion.perfil_correo_validacion.eliminada',
            "Eliminó el análisis \"{$nombre}\".",
            null,
            ['archivo_nombre' => $nombre]
        );

        return redirect()->route('recepcion.perfiles.index')->with('success', 'Análisis eliminado.');
    }
}
