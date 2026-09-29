<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\EmailValidacion;
use App\Models\EmailValidacionItem;
use App\Models\Hotel;
use App\Services\Recepcion\EmailValidatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmailValidacionController extends Controller
{
    use ResuelveHotelActivo;

    public function index()
    {
        $validaciones = EmailValidacion::where('hotel_code', $this->hotelActivo())
            ->with('usuario')
            ->latest()
            ->paginate(15);

        return view('recepcion.email.index', compact('validaciones'));
    }

    public function create()
    {
        return view('recepcion.email.create');
    }

    /**
     * Sube y procesa el PDF de reservas: extrae correos, los analiza (ver
     * EmailValidatorService) y guarda encabezado + detalle. El PDF en sí
     * NUNCA se guarda en disco — se lee de su ruta temporal de subida y se
     * descarta al terminar la petición, para no acumular archivos con
     * datos de huéspedes sin necesidad.
     */
    public function store(Request $request, EmailValidatorService $servicio)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ], [
            'pdf.required' => 'Selecciona el PDF de reservas a analizar.',
            'pdf.mimes'    => 'El archivo debe ser un PDF.',
            'pdf.max'      => 'El PDF no debe superar 20 MB.',
        ]);

        try {
            $filas = $servicio->procesarPdf($request->file('pdf')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['pdf' => 'No se pudo leer el PDF: ' . $e->getMessage()]);
        }

        if (empty($filas)) {
            return back()->withErrors(['pdf' => 'No se encontraron correos electrónicos en este PDF.']);
        }

        $nombreArchivo = $request->file('pdf')->getClientOriginalName();

        $validacion = DB::transaction(function () use ($filas, $nombreArchivo) {
            $resumen = ['ok' => 0, 'typos' => 0, 'genericos' => 0, 'falsos' => 0];
            foreach ($filas as $f) {
                match ($f['estado']) {
                    'OK' => $resumen['ok']++,
                    'ERROR TIPOGRAFICO' => $resumen['typos']++,
                    'GENERICO' => $resumen['genericos']++,
                    default => $resumen['falsos']++, // FALSO, DOMINIO INVALIDO, FORMATO INVALIDO
                };
            }

            $validacion = EmailValidacion::create([
                'user_id'        => auth()->id(),
                'archivo_nombre' => $nombreArchivo,
                'total'          => count($filas),
                'ok'             => $resumen['ok'],
                'typos'          => $resumen['typos'],
                'genericos'      => $resumen['genericos'],
                'falsos'         => $resumen['falsos'],
            ]);

            foreach ($filas as $f) {
                $validacion->items()->create([
                    'email'      => $f['email'],
                    'estado'     => $f['estado'],
                    'score'      => $f['score'],
                    'sugerencia' => $f['sugerencia'] !== '' ? $f['sugerencia'] : null,
                ]);
            }

            return $validacion;
        });

        AuditLog::registrar(
            'recepcion.email_validacion.creada',
            "Analizó \"{$validacion->archivo_nombre}\" ({$validacion->total} correos).",
            $validacion
        );

        return redirect()->route('recepcion.email.show', $validacion)
            ->with('success', "Análisis completo: {$validacion->total} correos encontrados.");
    }

    public function show(Request $request, EmailValidacion $validacion)
    {
        $query = $validacion->items();

        if ($request->filled('estado')) {
            // Se filtra por grupo (ver EmailValidacionItem::GRUPOS_FILTRO),
            // no por el valor crudo de "estado", para que coincida con lo
            // que cuentan las tarjetas resumen (p.ej. "Falso / inválido"
            // incluye FALSO, DOMINIO INVALIDO y FORMATO INVALIDO).
            $grupo = EmailValidacionItem::GRUPOS_FILTRO[$request->estado] ?? null;
            if ($grupo) {
                $query->whereIn('estado', $grupo['estados']);
            }
        }
        if ($request->filled('buscar')) {
            $query->where('email', 'like', '%' . $request->buscar . '%');
        }

        $items = $query->orderBy('email')->paginate(50)->withQueryString();

        return view('recepcion.email.show', [
            'validacion' => $validacion->load('usuario'),
            'items'      => $items,
            'filtros'    => $request->only(['estado', 'buscar']),
        ]);
    }

    /**
     * Mismo filtro que show() (respeta ?estado= y ?buscar= si el usuario
     * los tenía aplicados al pedir la descarga), pero SIN paginar: el PDF
     * es para archivar/imprimir/compartir fuera de SIGHO, así que debe
     * llevar todos los correos que cumplan el filtro, no solo la página
     * que se estaba viendo en pantalla.
     */
    public function pdf(Request $request, EmailValidacion $validacion)
    {
        $query = $validacion->items();

        if ($request->filled('estado')) {
            $grupo = EmailValidacionItem::GRUPOS_FILTRO[$request->estado] ?? null;
            if ($grupo) {
                $query->whereIn('estado', $grupo['estados']);
            }
        }
        if ($request->filled('buscar')) {
            $query->where('email', 'like', '%' . $request->buscar . '%');
        }

        $items = $query->orderBy('email')->get();

        $propiedad = Hotel::where('hotel_code', $validacion->hotel_code)->value('nombre')
            ?? $validacion->hotel_code;

        $pdf = Pdf::loadView('recepcion.email.pdf', [
            'validacion' => $validacion->load('usuario'),
            'items'      => $items,
            'filtros'    => $request->only(['estado', 'buscar']),
            'propiedad'  => $propiedad,
        ])->setPaper('letter', 'portrait');

        $nombreBase    = pathinfo($validacion->archivo_nombre, PATHINFO_FILENAME);
        $nombreArchivo = 'Validador_Emails_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $nombreBase) . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    public function destroy(EmailValidacion $validacion)
    {
        Gate::authorize('delete', $validacion);

        $nombre = $validacion->archivo_nombre;
        $validacion->delete(); // cascada borra los items (ver migración)

        AuditLog::registrar(
            'recepcion.email_validacion.eliminada',
            "Eliminó el análisis \"{$nombre}\".",
            null,
            ['archivo_nombre' => $nombre]
        );

        return redirect()->route('recepcion.email.index')->with('success', 'Análisis eliminado.');
    }
}
