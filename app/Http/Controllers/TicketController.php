<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Operador;
use App\Models\AuditLog;
use App\Notifications\NuevoTicketNotification;
use App\Notifications\TicketReasignadoNotification;
use App\Notifications\TicketResueltoNotification;
use App\Notifications\NuevoComentarioPublicoNotification;
use App\Notifications\TicketReabiertoNotification;
use App\Notifications\TicketRecordatorioNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Throwable;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;

class TicketController extends Controller
{
    use ResuelveHotelActivo;

    // Máximo de veces que un ticket puede reasignarse entre departamentos
    // antes de requerir que un Administrador lo resuelva manualmente
    // (evita que el ticket "rebote" indefinidamente sin atenderse).
    private const MAX_REASIGNACIONES = 2;

    // ==========================================
    // HELPER: Departamentos válidos como destino de un ticket. Antes era una
    // lista fija en código; ahora vive en la tabla `departamentos` para que un
    // Administrador pueda agregar/editar/eliminar (ej. "Prevención") desde el
    // panel sin tocar código. Debe coincidir con las opciones que se muestran
    // en resources/views/tickets/create.blade.php (que lee del mismo modelo).
    // ==========================================
    private function departamentosValidos(): array
    {
        return Departamento::activos()->pluck('nombre')->all();
    }

    // ==========================================
    // HELPER: Generar token con formato CODIGOHOTEL-YYYY-NNNNN-TXXXX
    // (ej. HEMZT-2026-00042-R9KF). El código sale de la propiedad del ticket
    // o, en su defecto, de config('app.hotel_code') (.env → HOTEL_CODE)
    // T = tipo (R=Resolución, C=Confirmación, y a futuro más)
    // ==========================================
    private function generarToken(int $ticketId, string $tipo, ?string $hotelCode = null): string
    {
        // Usa la propiedad real del ticket cuando está disponible, para que
        // el token quede correctamente identificado incluso si el ticket
        // pertenece a otra propiedad distinta a la de la instalación actual.
        $hotelCode = strtoupper($hotelCode ?? $this->hotelActivo());
        $año       = now()->year;
        $folio     = str_pad($ticketId, 5, '0', STR_PAD_LEFT);
        $rand      = strtoupper(Str::random(4));
        return "{$hotelCode}-{$año}-{$folio}-{$tipo}{$rand}";
    }

    // ==========================================
    // HELPER: Traduce el código de error de subida de PHP (UPLOAD_ERR_*)
    // a un mensaje entendible. Sirve para diagnosticar fallos de subida
    // que no son de validación (tamaño/tipo) sino del servidor (permisos,
    // carpeta temporal, conexión interrumpida, etc.).
    // ==========================================
    private function descripcionErrorSubida(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'el archivo excede el tamaño máximo que permite el servidor.',
            UPLOAD_ERR_PARTIAL =>
                'la subida se interrumpió a la mitad (conexión inestable). Intenta de nuevo.',
            UPLOAD_ERR_NO_FILE =>
                'no se recibió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR =>
                'el servidor no tiene una carpeta temporal configurada. Contacta a sistemas.',
            UPLOAD_ERR_CANT_WRITE =>
                'el servidor no pudo escribir el archivo en disco. Contacta a sistemas (revisar permisos de la carpeta temporal de PHP).',
            UPLOAD_ERR_EXTENSION =>
                'una extensión de PHP bloqueó la subida. Contacta a sistemas.',
            default =>
                "ocurrió un error desconocido al subir el archivo (código {$codigo}).",
        };
    }

    // ==========================================
    // HELPER: Resuelve el rango de fechas para "Reportes" (corte de
    // tickets) a partir del request, con default de "lo que va del mes"
    // si no se especifica. Compartido entre la pantalla y la descarga en
    // PDF para que ambas usen exactamente el mismo rango.
    // ==========================================
    private function rangoReportes(Request $request): array
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : now();

        // Si por error meten el rango al revés, lo corregimos en vez de
        // regresar un reporte vacío sin explicación.
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    // ==========================================
    // REPORTES — "corte" de tickets para un rango de fechas, pensado para
    // que dirección/gerencia pida sus KPIs de un periodo específico
    // (a diferencia del Dashboard, que siempre mira la última semana).
    // Solo para quien supervisa tickets: admin/gerente ven toda la
    // propiedad, técnico ve solo su propio departamento. Un usuario normal
    // no genera este corte (solo reporta tickets, no le corresponde este
    // nivel de información).
    // ==========================================
    public function reportes(Request $request)
    {
        $user = auth()->user();

        if (!$user->can('viewReportes', Ticket::class)) {
            abort(403, 'No tienes permiso para generar reportes de tickets.');
        }

        [$desde, $hasta] = $this->rangoReportes($request);

        $metricas = app(DashboardMetricsService::class)
            ->metricasPeriodo($user, $this->hotelActivo(), $desde, $hasta);

        return view('tickets.reportes', [
            'metricas'    => $metricas,
            'hotelActivo' => $this->hotelActivo(),
        ]);
    }

    // ==========================================
    // REPORTES PDF — mismo corte que reportes(), pero descargable para que
    // dirección lo pueda archivar/imprimir/compartir fuera del sistema.
    // ==========================================
    public function reportesPdf(Request $request)
    {
        $user = auth()->user();

        if (!$user->can('viewReportes', Ticket::class)) {
            abort(403, 'No tienes permiso para generar reportes de tickets.');
        }

        [$desde, $hasta] = $this->rangoReportes($request);

        $metricas    = app(DashboardMetricsService::class)
            ->metricasPeriodo($user, $this->hotelActivo(), $desde, $hasta);
        $hotelActivo = $this->hotelActivo();

        try {
            $pdf = Pdf::loadView('tickets.reportes_pdf', compact('metricas', 'hotelActivo', 'user'))
                      ->setPaper('letter', 'portrait');

            $nombreArchivo = 'Corte_Tickets_' . $metricas['desde'] . '_a_' . $metricas['hasta'] . '.pdf';

            return $pdf->download($nombreArchivo);
        } catch (Throwable $e) {
            Log::error('Error generando PDF de reporte de tickets: ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'desde'     => $metricas['desde'],
                'hasta'     => $metricas['hasta'],
                'exception' => get_class($e),
            ]);

            return back()->with('error', 'Ocurrió un problema al generar el PDF del reporte. Intenta de nuevo o contacta a sistemas si el error persiste.');
        }
    }

    // ==========================================
    // INDEX — con filtros y buscador de token
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();

        // ── Buscador de token ──────────────────────────────────────
        if ($request->filled('token')) {
            $tokenBuscado = strtoupper(trim($request->token));

            $ticketEncontrado = Ticket::with('user')
                ->where(function ($q) use ($tokenBuscado) {
                    $q->where('token_resolucion', $tokenBuscado)
                      ->orWhere('token_confirmacion', $tokenBuscado);
                })
                ->first();

            // Control de acceso: cada rol solo puede ver lo suyo (misma
            // regla que TicketPolicy::view, usada también en show() y
            // downloadPDF()).
            if ($ticketEncontrado) {
                $acceso = $user->can('view', $ticketEncontrado);

                return view('tickets.index', [
                    'ticketEncontrado'    => $acceso ? $ticketEncontrado : null,
                    'tokenBuscado'        => $tokenBuscado,
                    'tokenSinAcceso'      => !$acceso,
                    'ticketsAbiertos'     => collect(),
                    'ticketsResueltos'    => collect(),
                    'misReportesAbiertos' => collect(),
                    'misReportesResueltos'=> collect(),
                    'filtros'             => [],
                ]);
            }

            // Token no encontrado
            return view('tickets.index', [
                'ticketEncontrado'    => null,
                'tokenBuscado'        => $tokenBuscado,
                'tokenNoEncontrado'   => true,
                'ticketsAbiertos'     => collect(),
                'ticketsResueltos'    => collect(),
                'misReportesAbiertos' => collect(),
                'misReportesResueltos'=> collect(),
                'filtros'             => [],
            ]);
        }

        // ── Filtros ────────────────────────────────────────────────
        $filtros = $request->only(['departamento_destino', 'status', 'fecha_desde', 'fecha_hasta', 'prioridad', 'usuario', 'area']);

        // Orden por fecha de creación: 'antiguos' invierte el orden por defecto (recientes primero).
        $filtros['orden'] = $request->input('orden') === 'antiguos' ? 'antiguos' : 'recientes';

        $aplicarFiltros = function ($query) use ($filtros) {
            if (!empty($filtros['departamento_destino'])) {
                $query->where('departamento_destino', $filtros['departamento_destino']);
            }
            if (!empty($filtros['status'])) {
                $query->where('status', $filtros['status']);
            }
            if (!empty($filtros['fecha_desde'])) {
                $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
            }
            if (!empty($filtros['fecha_hasta'])) {
                $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
            }
            if (!empty($filtros['prioridad'])) {
                $query->where('prioridad', $filtros['prioridad']);
            }
            // Usuario que reportó el ticket: búsqueda parcial por nombre o
            // correo, para cuando solo se recuerda parte del nombre.
            if (!empty($filtros['usuario'])) {
                $busqueda = $filtros['usuario'];
                $query->whereHas('user', function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                      ->orWhere('email', 'like', "%{$busqueda}%");
                });
            }
            // Área / ubicación del ticket: búsqueda parcial en ambos campos
            // libres (area y descripcion_ubicacion) porque el usuario no
            // siempre recuerda en cuál de los dos se capturó el dato.
            if (!empty($filtros['area'])) {
                $busqueda = $filtros['area'];
                $query->where(function ($q) use ($busqueda) {
                    $q->where('area', 'like', "%{$busqueda}%")
                      ->orWhere('descripcion_ubicacion', 'like', "%{$busqueda}%");
                });
            }
        };

        $base = Ticket::with('user');
        $filtros['orden'] === 'antiguos' ? $base->oldest() : $base->latest();

        $misReportesAbiertos  = (clone $base)
            ->whereIn('status', ['Abierto', 'Reabierto', 'Resuelto'])
            ->where('user_id', $user->id);
        $aplicarFiltros($misReportesAbiertos);
        $misReportesAbiertos = $misReportesAbiertos->get();

        $misReportesResueltos = (clone $base)
            ->where('status', 'Cerrado')
            ->where('user_id', $user->id);
        $aplicarFiltros($misReportesResueltos);
        $misReportesResueltos = $misReportesResueltos->get();

        if ($user->isAdmin() || $user->isGerente()) {
            $ticketsAbiertos = (clone $base)
                ->whereIn('status', ['Abierto', 'Reabierto'])
                ->where('user_id', '!=', $user->id);
            $aplicarFiltros($ticketsAbiertos);
            $ticketsAbiertos = $ticketsAbiertos->get();

            $ticketsResueltos = (clone $base)
                ->whereIn('status', ['Resuelto', 'Cerrado'])
                ->where('user_id', '!=', $user->id);
            $aplicarFiltros($ticketsResueltos);
            $ticketsResueltos = $ticketsResueltos->get();

        } elseif ($user->isTecnico()) {
            $ticketsAbiertos = (clone $base)
                ->whereIn('status', ['Abierto', 'Reabierto'])
                ->where('departamento_destino', $user->departamento)
                ->where('user_id', '!=', $user->id);
            $aplicarFiltros($ticketsAbiertos);
            $ticketsAbiertos = $ticketsAbiertos->get();

            $ticketsResueltos = (clone $base)
                ->whereIn('status', ['Resuelto', 'Cerrado'])
                ->where('departamento_destino', $user->departamento)
                ->where('user_id', '!=', $user->id);
            $aplicarFiltros($ticketsResueltos);
            $ticketsResueltos = $ticketsResueltos->get();

        } else {
            $ticketsAbiertos  = collect();
            $ticketsResueltos = collect();
        }

        return view('tickets.index', compact(
            'ticketsAbiertos', 'ticketsResueltos',
            'misReportesAbiertos', 'misReportesResueltos',
            'filtros'
        ));
    }

    // ==========================================
    // CREATE
    // ==========================================
    public function create()
    {
        $catalogoPorDepartamento = \App\Models\TicketCategoria::catalogoAgrupadoPorDepartamento($this->hotelActivo());

        return view('tickets.create', compact('catalogoPorDepartamento'));
    }

    // ==========================================
    // STORE
    // ==========================================
    public function store(Request $request)
    {
        // Si algún archivo llegó dañado a nivel de PHP (no de validación de Laravel),
        // lo detectamos aquí para loguear el código real y decirle al usuario
        // algo más útil que "failed to upload".
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                if (!$archivo->isValid()) {
                    $codigo = $archivo->getError();

                    Log::warning('Fallo de subida de archivo al crear ticket', [
                        'user_id'      => auth()->id(),
                        'codigo_error' => $codigo,
                        'detalle'      => $this->descripcionErrorSubida($codigo),
                    ]);

                    return back()->withInput()->withErrors([
                        'archivos' => 'No se pudo subir un archivo: ' . $this->descripcionErrorSubida($codigo),
                    ]);
                }
            }
        }

        // Catálogo dinámico de tipo de solicitud/sistema (App\Models\
        // TicketCategoria), administrable por admin/gerente desde
        // /tickets/categorias. Ya no vive en config/tickets_sistemas.php ni
        // es exclusivo de "Sistemas": aplica a cualquier departamento que
        // tenga categorías activas para la propiedad actual; si no tiene
        // ninguna (ej. Mantenimiento sin catálogo propio), el ticket se
        // levanta solo con la descripción general, como cualquier otro.
        $departamentoDestino = $request->input('departamento_destino');
        $categorias = $departamentoDestino
            ? \App\Models\TicketCategoria::catalogoParaDepartamento($departamentoDestino, $this->hotelActivo())
            : collect();
        $tieneCategorias = $categorias->isNotEmpty();

        // El área normalmente ya viene fija en la cuenta del usuario (la
        // asigna un administrador en Usuarios) y el formulario la manda de
        // solo lectura. Solo se exige en el request como respaldo para
        // cuentas viejas que todavía no tienen área asignada (ver fallback
        // en tickets/create.blade.php).
        $areaCuenta = $request->user()->area;

        $rules = [
            'area'                  => $areaCuenta ? 'nullable|string|max:255' : 'required|string|max:255',
            'departamento_destino'  => ['required', 'string', Rule::in($this->departamentosValidos())],
            'descripcion_ubicacion' => 'nullable|string|max:255',
            'descripcion'           => 'required|string',
            'prioridad'             => 'required|string|in:Baja,Media,Alta',
            'archivos'              => 'nullable|array|max:6',
            'archivos.*'            => 'file|mimes:jpeg,png,jpg,mp4,mov,pdf,txt|max:6144',
        ];

        $categoriaSel    = $tieneCategorias ? $categorias->firstWhere('id', (int) $request->input('tipo_solicitud')) : null;
        $requiereSistema = $categoriaSel && $categoriaSel->requiere_subcategoria;
        $subSel          = null;
        $camposDef       = collect();

        if ($tieneCategorias) {
            $rules['tipo_solicitud'] = ['required', Rule::in($categorias->pluck('id')->map(fn ($id) => (string) $id)->all())];

            if ($requiereSistema) {
                $subOpciones = $categoriaSel->subcategorias;
                $rules['sistema'] = ['required', Rule::in($subOpciones->pluck('id')->map(fn ($id) => (string) $id)->all())];
                $subSel    = $subOpciones->firstWhere('id', (int) $request->input('sistema'));
                $camposDef = $subSel ? $subSel->campos : collect();
            } else {
                $rules['sistema'] = 'nullable';
                $camposDef        = $categoriaSel ? $categoriaSel->campos : collect();
            }

            foreach ($camposDef as $campo) {
                $reglasCampo = $campo->requerido ? 'required' : 'nullable';
                $reglasCampo .= '|string|max:1000';
                $rules['campos.' . $campo->name] = $reglasCampo;
            }
        } else {
            $rules['sistema']        = 'nullable';
            $rules['tipo_solicitud'] = 'nullable';
            $rules['campos']         = 'nullable|array';
        }

        $data = $request->validate($rules, [
            'sistema.required'        => 'Selecciona a qué sistema aplica la solicitud.',
            'tipo_solicitud.required' => 'Selecciona el tipo de solicitud.',
            'archivos.max' => 'Puedes adjuntar máximo 6 archivos.',
            'archivos.*.uploaded' => 'Uno de los archivos no se pudo subir porque es demasiado grande para la configuración actual del servidor. Intenta con un archivo más pequeño o contacta a sistemas.',
            'archivos.*.max' => 'Cada archivo no puede pesar más de 6 MB.',
            'archivos.*.mimes' => 'Cada archivo debe ser una imagen (jpg, png), un video (mp4, mov), o un documento (pdf, txt).',
        ]);

        // campos_extra guarda un snapshot legible (etiquetas de categoría,
        // sistema y cada campo, no solo sus IDs) para que el detalle del
        // ticket siga siendo legible aunque el catálogo se edite o borre
        // después — un ticket ya creado no depende de que esas filas sigan existiendo.
        $camposExtra = null;
        if ($tieneCategorias && $categoriaSel) {
            $camposExtra = [
                'categoria_label'    => $categoriaSel->label,
                'subcategoria_label' => ($requiereSistema && $subSel) ? $subSel->label : null,
                'campos'             => $camposDef->map(function ($campo) use ($data) {
                    return [
                        'label' => $campo->label,
                        'valor' => $data['campos'][$campo->name] ?? null,
                    ];
                })->filter(fn ($c) => !is_null($c['valor']) && $c['valor'] !== '')->values()->all(),
            ];
        }

        // Solo conservar las columnas reales de la tabla + campos_extra
        $data = collect($data)->only([
            'area', 'departamento_destino', 'descripcion_ubicacion', 'descripcion', 'prioridad',
        ])->toArray();

        // La cuenta manda sobre lo que haya llegado del formulario: así el
        // valor guardado en el ticket siempre coincide con el área real del
        // usuario, aunque el campo del formulario esté deshabilitado (o
        // alguien intente forzar otro valor por request manual).
        $data['area'] = $areaCuenta ?: ($data['area'] ?? null);

        $data['campos_extra'] = $camposExtra;

        if ($request->hasFile('archivos')) {
            $rutasEvidencia = [];
            foreach ($request->file('archivos') as $archivo) {
                $rutasEvidencia[] = $archivo->store('evidencias_tickets', 'public');
            }
            $data['archivos_evidencia'] = $rutasEvidencia;
        }

        $data['user_id']    = auth()->id();
        $data['status']     = 'Abierto';
        $data['hotel_code'] = $this->hotelActivo();

        $ticket = Ticket::create($data);

        $destinatarios = User::where(function ($q) use ($ticket) {
            $q->where('role', 'admin')
              ->orWhere(function ($q2) use ($ticket) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $ticket->departamento_destino);
              });
        })->get();

        Notification::send($destinatarios, new NuevoTicketNotification($ticket));

        return redirect()->route('tickets.index')->with('success', 'Reporte creado con éxito.');
    }

    // ==========================================
    // SHOW
    // ==========================================
    public function show($id)
    {
        $ticket = Ticket::with(['user', 'notas.user', 'resolvedBy', 'operador'])->findOrFail($id);
        $user   = auth()->user();

        if (!$user->can('view', $ticket)) {
            abort(403, 'No tienes permiso para ver este ticket.');
        }

        // "Volver al listado" (ver tickets/show.blade.php) debe respetar
        // los filtros/orden que el usuario tenía puestos en /tickets, no
        // regresarlo siempre a la lista en blanco. Guardamos en SESIÓN la
        // URL de origen SOLO cuando de verdad se llegó aquí navegando
        // desde /tickets (con o sin filtros) — nunca cuando se llega desde
        // otro lado (ej. un link del dashboard) ni cuando la "anterior" es
        // este mismo ticket (que es lo que pasa en el redirect de vuelta
        // después de resolver/reasignar/agregar nota: esas acciones no
        // deben pisar el valor ya guardado). Así sobrevive intacto aunque
        // el usuario haga varias acciones sobre el ticket antes de volver.
        $indexUrl = route('tickets.index');
        $previa   = url()->previous();
        if ($previa === $indexUrl || str_starts_with($previa, $indexUrl . '?')) {
            session(['tickets.volver' => $previa]);
        }
        $volverUrl = session('tickets.volver', $indexUrl);

        // Solo se usa dentro del bloque "Registrar solución" del técnico
        // (ver tickets/show.blade.php); es el catálogo COMPARTIDO de
        // operadores activos de su departamento (no solo los que él dio de
        // alta — ver Operador::scopeDeDepartamento), para dejar constancia
        // opcional de quién ejecutó el trabajo. Consulta barata y se
        // calcula siempre para no acoplar la vista a la máquina de estados
        // del ticket.
        $misOperadores = $user->isTecnico()
            ? Operador::deDepartamento($user->departamento)->activos()->orderBy('nombre')->get()
            : collect();

        return view('tickets.show', compact('ticket', 'misOperadores', 'volverUrl'));
    }

    // ==========================================
    // RESOLVER TICKET
    // ==========================================
    public function resolve(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->puedeResponderTickets()) {
            abort(403, 'No tienes permisos para resolver tickets.');
        }

        $ticket = Ticket::findOrFail($id);

        $autorizacion = Gate::inspect('resolve', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        // Si ya se envió un recordatorio y nadie de soporte ha dejado una
        // nota desde entonces, primero hay que explicar por qué el ticket
        // sigue "en pausa" (ej. falta de material) — no se puede resolver
        // en silencio después de una queja por inactividad. Admin puede
        // saltarse esto para desatorar el caso manualmente, igual que ya
        // hace con el límite de reasignaciones y la ventana de deshacer.
        if (!$user->isAdmin() && $ticket->requiereJustificacionRecordatorio()) {
            return back()->with('error', 'Este ticket tiene un recordatorio pendiente sin explicación. Antes de resolverlo, agrega una nota en la bitácora contando qué ha pasado (ej. "sin atender por falta de material").');
        }

        $request->validate([
            'notas_admin'        => 'required|string',
            'fotos_resolucion'   => 'nullable|array|max:4',
            'fotos_resolucion.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'operador_id'        => 'nullable|integer|exists:operadores,id',
        ], [
            'fotos_resolucion.*.uploaded' => 'Una de las fotos no se pudo subir porque es demasiado grande para la configuración actual del servidor.',
            'fotos_resolucion.*.max' => 'Cada foto no puede pesar más de 4 MB.',
            'fotos_resolucion.*.image' => 'Cada archivo debe ser una imagen (jpg, png, webp).',
        ]);

        // El operador (si se marcó) debe salir del catálogo del DEPARTAMENTO
        // del técnico que resuelve, activo — nunca del id crudo del
        // request. Evita que alguien mande el id de un operador de otro
        // departamento/propiedad o ya desactivado (BelongsToHotel +
        // deDepartamento + activos() lo garantizan; find() regresa null si
        // no cumple alguna).
        $operadorId = null;
        if ($request->filled('operador_id')) {
            $operador = Operador::activos()->deDepartamento($user->departamento)->find($request->operador_id);

            if (!$operador) {
                return back()
                    ->withErrors(['operador_id' => 'Ese operador no está disponible para tu departamento o ya no está activo.'])
                    ->withInput();
            }

            $operadorId = $operador->id;
        }

        $rutas = [];
        if ($request->hasFile('fotos_resolucion')) {
            foreach ($request->file('fotos_resolucion') as $foto) {
                $rutas[] = $foto->store('tickets/fotos', 'public');
            }
        }

        $token = $this->generarToken($ticket->id, 'R', $ticket->hotel_code);

        $antes = $ticket->only(['status']);

        $ticket->update([
            'status'           => 'Resuelto',
            'notas_admin'      => $request->notas_admin,
            'fecha_resolucion' => now(),
            'fotos_resolucion' => count($rutas) ? $rutas : null,
            'token_resolucion' => $token,
            'ip_resolucion'    => $request->ip(),
            'resolved_by'      => $user->id,
            'operador_id'      => $operadorId,
        ]);

        $notaResolucion = 'Ticket marcado como resuelto. Solución: ' . $request->notas_admin . ' | Token de resolución: ' . $token;
        if ($operadorId) {
            $notaResolucion .= ' | Operador: ' . $operador->nombre;
        }

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => $notaResolucion,
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        // Avisar a quien reportó el ticket que ya fue resuelto, para que
        // confirme la solución o lo reabra si no quedó atendido.
        Notification::send($ticket->user, new TicketResueltoNotification($ticket, $token));

        AuditLog::registrar(
            'ticket.resuelto',
            "Resolvió el ticket #{$ticket->id} (\"{$ticket->area}\", reportado por {$ticket->user->name}). Token: {$token}.",
            $ticket,
            $antes,
            $ticket->fresh()->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by', 'operador_id'])
        );

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', "Ticket marcado como resuelto. Token: {$token}");
    }

    // ==========================================
    // REASIGNAR TICKET A OTRO DEPARTAMENTO
    // Para cuando el ticket se enrutó al departamento equivocado.
    // Exige una justificación (motivo) para que quede registrada en la
    // bitácora y auditoría, y no se pueda reasignar "porque sí". Limita
    // el número de reasignaciones para evitar que el ticket rebote entre
    // departamentos sin que nadie lo atienda.
    //
    // A propósito NO se bloquea aquí por Ticket::requiereJustificacionRecordatorio()
    // (a diferencia de resolve()): reasignar YA exige motivo_reasignacion
    // (mín. 10 caracteres) y ese motivo queda como nota en la bitácora —
    // es en sí mismo la explicación de qué está pasando con el ticket. Pedir
    // una nota aparte ANTES de poder escribir esa nota sería fricción doble
    // sin beneficio real.
    // ==========================================
    public function reasignar(Request $request, $id)
    {
        $user   = auth()->user();
        $ticket = Ticket::findOrFail($id);

        if (!$user->puedeResponderTickets()) {
            abort(403, 'No tienes permisos para reasignar tickets.');
        }

        if (!in_array($ticket->status, ['Abierto', 'Reabierto'])) {
            return back()->with('error', 'Solo se pueden reasignar tickets que están "Abierto" o "Reabierto".');
        }

        // Un técnico solo puede reasignar tickets que le llegaron a él
        // (es decir, que el ticket está actualmente en su departamento).
        // Un Administrador puede reasignar cualquier ticket.
        $autorizacion = Gate::inspect('reasignar', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        // Límite de reasignaciones: si ya se alcanzó, solo un Administrador
        // puede seguir moviéndolo (para desatorar el caso manualmente).
        if ($ticket->veces_reasignado >= self::MAX_REASIGNACIONES && !$user->isAdmin()) {
            return back()->with('error', 'Este ticket ya alcanzó el máximo de reasignaciones permitidas (' . self::MAX_REASIGNACIONES . '). Pide a un Administrador que lo reasigne o lo atienda directamente.');
        }

        $data = $request->validate([
            'departamento_destino_nuevo' => ['required', 'string', Rule::in($this->departamentosValidos())],
            'motivo_reasignacion'        => 'required|string|min:10|max:500',
        ], [
            'departamento_destino_nuevo.required' => 'Selecciona el departamento correcto para este ticket.',
            'departamento_destino_nuevo.in'        => 'El departamento seleccionado no es válido.',
            'motivo_reasignacion.required'         => 'Explica por qué este ticket no le corresponde a tu departamento.',
            'motivo_reasignacion.min'              => 'Danos un poco más de detalle (mínimo 10 caracteres).',
        ]);

        $departamentoNuevo = $data['departamento_destino_nuevo'];

        if ($departamentoNuevo === $ticket->departamento_destino) {
            return back()->withInput()->with('error', 'Selecciona un departamento distinto al actual para reasignar el ticket.');
        }

        $departamentoAnterior = $ticket->departamento_destino;

        $antes = $ticket->only(['departamento_destino', 'veces_reasignado']);

        $ticket->update([
            'departamento_destino' => $departamentoNuevo,
            'veces_reasignado'     => $ticket->veces_reasignado + 1,
        ]);

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => "Ticket reasignado de \"{$departamentoAnterior}\" a \"{$departamentoNuevo}\". Motivo: {$data['motivo_reasignacion']}",
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        AuditLog::registrar(
            'ticket.reasignado',
            "Reasignó el ticket #{$ticket->id} (\"{$ticket->area}\") de \"{$departamentoAnterior}\" a \"{$departamentoNuevo}\". Motivo: {$data['motivo_reasignacion']}",
            $ticket,
            $antes,
            $ticket->fresh()->only(['departamento_destino', 'veces_reasignado'])
        );

        // Notificar al nuevo departamento (técnicos de ese depto + admins)
        $destinatarios = User::where(function ($q) use ($departamentoNuevo) {
            $q->where('role', 'admin')
              ->orWhere(function ($q2) use ($departamentoNuevo) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $departamentoNuevo);
              });
        })->get();

        Notification::send($destinatarios, new TicketReasignadoNotification($ticket->fresh(), $departamentoAnterior, $data['motivo_reasignacion']));

        return redirect()->route('tickets.index')
            ->with('success', "Ticket reasignado a \"{$departamentoNuevo}\" correctamente.");
    }

    // ==========================================
    // CONFIRMAR RESOLUCIÓN (usuario)
    // ==========================================
    public function confirmar(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user   = auth()->user();

        $autorizacion = Gate::inspect('confirmar', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        if ($ticket->status !== 'Resuelto') {
            return back()->with('error', 'Este ticket no está pendiente de confirmación.');
        }

        $token = $this->generarToken($ticket->id, 'C', $ticket->hotel_code);

        $antes = $ticket->only(['status']);

        $ticket->update([
            'status'             => 'Cerrado',
            'confirmado_usuario' => true,
            'fecha_confirmacion' => now(),
            'token_confirmacion' => $token,
            'ip_confirmacion'    => $request->ip(),
        ]);

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => 'El usuario confirmó que el problema fue solucionado satisfactoriamente. Ticket cerrado. | Token de confirmación: ' . $token,
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        AuditLog::registrar(
            'ticket.confirmado',
            "Confirmó la resolución y cerró el ticket #{$ticket->id} (\"{$ticket->area}\"). Token: {$token}.",
            $ticket,
            $antes,
            $ticket->fresh()->only(['status', 'confirmado_usuario', 'fecha_confirmacion', 'token_confirmacion'])
        );

        return redirect()->route('tickets.index')
            ->with('success', 'Gracias por confirmar. El ticket ha sido cerrado. Token: ' . $token);
    }

    // ==========================================
    // REABRIR TICKET (usuario no conforme)
    // ==========================================
    public function reabrir(Request $request, $id)
    {
        $request->validate([
            'motivo_reapertura' => 'required|string|min:10',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user   = auth()->user();

        $autorizacion = Gate::inspect('reabrir', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        if ($ticket->status !== 'Resuelto') {
            return back()->with('error', 'Este ticket no puede ser reabierto en su estado actual.');
        }

        $antes = $ticket->only(['status']);

        $ticket->update([
            'status'            => 'Reabierto',
            'motivo_reapertura' => $request->motivo_reapertura,
            // Si ya se había escalado por SLA en un ciclo anterior, se
            // limpia para que el motor de SLA (tickets:escalar-sla) pueda
            // volver a escalarlo si también incumple esta vez.
            'sla_escalado_at'   => null,
        ]);

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => 'El usuario indicó que el problema NO fue resuelto y reabrió el ticket. Motivo: ' . $request->motivo_reapertura,
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        AuditLog::registrar(
            'ticket.reabierto',
            "Reabrió el ticket #{$ticket->id} (\"{$ticket->area}\"). Motivo: {$request->motivo_reapertura}",
            $ticket,
            $antes,
            $ticket->fresh()->only(['status', 'motivo_reapertura'])
        );

        // Notificar al equipo técnico del departamento actual (+ admins)
        $destinatarios = User::where(function ($q) use ($ticket) {
            $q->where('role', 'admin')
              ->orWhere(function ($q2) use ($ticket) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $ticket->departamento_destino);
              });
        })->get();

        Notification::send($destinatarios, new TicketReabiertoNotification($ticket->fresh(), $request->motivo_reapertura));

        return redirect()->route('tickets.show', $ticket->id)
            ->with('warning', 'Ticket reabierto. El equipo técnico ha sido notificado.');
    }

    // ==========================================
    // RECORDATORIO (usuario reportante, sin ver seguimiento)
    // Distinto de la escalación automática por SLA (tickets:escalar-sla,
    // que avisa a admin/gerente cuando se incumple el tiempo formal): esto
    // lo dispara el propio reportante cuando ya pasaron varios días y no
    // ve movimiento en su ticket, y avisa de nuevo al departamento
    // responsable (mismo destinatario que al crear/reabrir el ticket).
    // UMBRAL_RECORDATORIO_DIAS/COOLDOWN son reglas de tiempo, no de
    // permiso — se quedan aquí, igual que el resto de la máquina de
    // estados, no en TicketPolicy.
    // ==========================================
    private const UMBRAL_RECORDATORIO_DIAS = 2;
    private const COOLDOWN_RECORDATORIO_HORAS = 24;

    public function recordatorio(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user   = auth()->user();

        $autorizacion = Gate::inspect('recordatorio', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        if (!in_array($ticket->status, ['Abierto', 'Reabierto'])) {
            return back()->with('error', 'Este ticket no está abierto, no aplica enviar un recordatorio.');
        }

        $diasAbierto = $ticket->created_at->diffInDays(now());

        if ($diasAbierto < self::UMBRAL_RECORDATORIO_DIAS) {
            return back()->with('error', 'Este ticket lleva menos de ' . self::UMBRAL_RECORDATORIO_DIAS . ' días abierto; todavía no puedes enviar un recordatorio.');
        }

        if ($ticket->ultimo_recordatorio_at && $ticket->ultimo_recordatorio_at->diffInHours(now()) < self::COOLDOWN_RECORDATORIO_HORAS) {
            $horasRestantes = self::COOLDOWN_RECORDATORIO_HORAS - $ticket->ultimo_recordatorio_at->diffInHours(now());
            return back()->with('error', "Ya enviaste un recordatorio para este ticket. Podrás enviar otro en {$horasRestantes} horas.");
        }

        $ticket->update(['ultimo_recordatorio_at' => now()]);

        // Texto neutral (no "el usuario envió...") porque ahora también lo
        // puede disparar Admin/Gerente sobre un ticket que no reportaron
        // — el autor real ya queda identificado por user_id/$nota->user.
        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => "Se envió un recordatorio: el ticket lleva {$diasAbierto} días abierto sin seguimiento visible.",
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        AuditLog::registrar(
            'ticket.recordatorio',
            "Envió un recordatorio del ticket #{$ticket->id} (\"{$ticket->area}\"), abierto desde hace {$diasAbierto} días.",
            $ticket,
            null,
            ['ultimo_recordatorio_at' => $ticket->fresh()->ultimo_recordatorio_at->toDateTimeString()]
        );

        // Mismo destinatario que al crear/reabrir el ticket: admin + los
        // técnicos del departamento al que está asignado ahora mismo.
        $destinatarios = User::where(function ($q) use ($ticket) {
            $q->where('role', 'admin')
              ->orWhere(function ($q2) use ($ticket) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $ticket->departamento_destino);
              });
        })->get();

        Notification::send($destinatarios, new TicketRecordatorioNotification($ticket->fresh(), $diasAbierto));

        return back()->with('success', 'Recordatorio enviado. El equipo responsable fue notificado de nuevo.');
    }

    // ==========================================
    // DESHACER RESOLUCIÓN (error de dedo del técnico)
    // Distinto de "reabrir": esto lo dispara quien lo resolvió (o un
    // admin) cuando el ticket se marcó como resuelto por equivocación,
    // no porque el usuario haya rechazado la solución. Por eso solo
    // aplica mientras sigue en "Resuelto" (el usuario aún no confirmó
    // ni rechazó) y dentro de una ventana corta de tiempo; un admin
    // puede corregirlo sin límite de tiempo.
    // ==========================================
    public function deshacerResolucion(Request $request, $id)
    {
        $user   = auth()->user();
        $ticket = Ticket::findOrFail($id);

        if (!$user->puedeResponderTickets()) {
            abort(403, 'No tienes permisos para deshacer la resolución de un ticket.');
        }

        if ($ticket->status !== 'Resuelto') {
            return back()->with('error', 'Este ticket ya no está en estado "Resuelto" (el usuario ya confirmó o reabrió el ticket), así que no se puede deshacer desde aquí.');
        }

        $autorizacion = Gate::inspect('deshacerResolucion', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        // Ventana de 30 minutos para autocorrección; los administradores
        // pueden corregir el estado sin límite de tiempo.
        if (!$user->isAdmin() && $ticket->fecha_resolucion && now()->diffInMinutes($ticket->fecha_resolucion) > 30) {
            return back()->with('error', 'Ya pasaron más de 30 minutos desde que se resolvió este ticket. Pide a un Administrador que corrija el estado.');
        }

        $request->validate([
            'motivo' => 'required|string|min:10',
        ], [
            'motivo.required' => 'Explica brevemente por qué se deshace esta resolución (ej. "se marcó por error").',
            'motivo.min'      => 'Danos un poco más de detalle (mínimo 10 caracteres).',
        ]);

        $antes = $ticket->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by', 'operador_id']);

        $ticket->update([
            'status'           => 'Abierto',
            'notas_admin'      => null,
            'fecha_resolucion' => null,
            'fotos_resolucion' => null,
            'token_resolucion' => null,
            'ip_resolucion'    => null,
            'resolved_by'      => null,
            'operador_id'      => null,
        ]);

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => "Se deshizo la resolución de este ticket porque se había marcado por error. Motivo: {$request->motivo}. El ticket vuelve a estado \"Abierto\".",
            'tipo'          => 'publica',
            'es_automatica' => true,
        ]);

        AuditLog::registrar(
            'ticket.resolucion_deshecha',
            "Deshizo la resolución del ticket #{$ticket->id} (\"{$ticket->area}\") por error de captura. Motivo: {$request->motivo}",
            $ticket,
            $antes,
            $ticket->fresh()->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by', 'operador_id'])
        );

        return redirect()->route('tickets.show', $ticket->id)
            ->with('warning', 'Se deshizo la resolución. El ticket vuelve a estado "Abierto".');
    }

    // ==========================================
    // AGREGAR NOTA A BITÁCORA
    // ==========================================
    public function agregarNota(Request $request, $id)
    {
        $request->validate([
            'nota' => 'required|string|min:5',
            'tipo' => 'required|in:publica,interna',
        ]);

        $user   = auth()->user();
        $ticket = Ticket::findOrFail($id);

        $autorizacion = Gate::inspect('agregarNota', $ticket);
        if ($autorizacion->denied()) {
            abort(403, $autorizacion->message());
        }

        $nota = TicketNota::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'nota'      => $request->nota,
            'tipo'      => $request->tipo,
        ]);

        // Si es un comentario público y quien lo escribió no es el propio
        // dueño del ticket, se notifica al usuario que reportó el problema.
        if ($nota->esPublica() && $ticket->user_id !== $user->id) {
            $nota->setRelation('user', $user);
            Notification::send($ticket->user, new NuevoComentarioPublicoNotification($ticket, $nota));
        }

        return back()->with('success', 'Nota agregada correctamente.');
    }

    // ==========================================
    // EDITAR NOTA (ventana 15 min, solo autor)
    // ==========================================
    public function editarNota(Request $request, $ticketId, $notaId)
    {
        $request->validate([
            'nota' => 'required|string|min:5',
        ]);

        $user = auth()->user();
        $nota = TicketNota::where('ticket_id', $ticketId)->findOrFail($notaId);

        if ($nota->es_automatica) {
            abort(403, 'Esta nota fue generada automáticamente por el sistema y forma parte del historial de auditoría del ticket; no puede editarse.');
        }

        if ($nota->user_id !== $user->id) {
            abort(403, 'Solo el autor puede editar esta nota.');
        }

        if (now()->diffInMinutes($nota->created_at) > 15) {
            return back()->with('error', 'El tiempo para editar esta nota ha expirado (15 minutos).');
        }

        $nota->update([
            'nota_original' => $nota->nota_original ?? $nota->nota,
            'nota'          => $request->nota,
            'editado_at'    => now(),
            'editado_por'   => $user->id,
        ]);

        return back()->with('success', 'Nota actualizada correctamente.');
    }

    // ==========================================
    // DESCARGAR PDF
    // ==========================================
    public function downloadPDF(Request $request, $id)
    {
        $ticket = Ticket::with(['user', 'notas.user'])->findOrFail($id);
        $user   = auth()->user();

        if (!$user->can('view', $ticket)) {
            abort(403, 'No tienes permiso para descargar este ticket.');
        }

        // Evidencia adjunta por el solicitante al abrir el ticket.
        // Los tickets nuevos guardan varias fotos en archivos_evidencia (JSON).
        // Los tickets antiguos solo tienen un archivo en archivo_path (columna legada).
        // Antes este bloque calculaba $base64 pero la vista nunca lo usaba, por lo
        // que ninguna foto adjunta al reportar se mostraba en el PDF.
        $fotosEvidenciaBase64 = [];
        $rutasEvidencia = $ticket->archivos_evidencia ?: ($ticket->archivo_path ? [$ticket->archivo_path] : []);
        foreach ($rutasEvidencia as $fotoPath) {
            if (Storage::disk('public')->exists($fotoPath)) {
                $path = storage_path('app/public/' . $fotoPath);
                $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $fotosEvidenciaBase64[] = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
                }
            }
        }

        $fotosBase64 = [];
        if ($ticket->fotos_resolucion) {
            foreach ($ticket->fotos_resolucion as $fotoPath) {
                if (Storage::disk('public')->exists($fotoPath)) {
                    $path = storage_path('app/public/' . $fotoPath);
                    $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $fotosBase64[] = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
                    }
                }
            }
        }

        $incluirBitacora = $request->boolean('bitacora') && $user->puedeResponderTickets();

        $notasPDF = collect();
        if ($incluirBitacora) {
            $notasPDF = $ticket->notas->filter(function ($nota) use ($user) {
                return $nota->esPublica() || $user->puedeResponderTickets();
            });
        }

        // Las fotos adjuntas/de resolucion requieren la extension GD de PHP
        // para poder incrustarse en el PDF. Si el servidor no la tiene
        // habilitada, evitamos la pantalla de error de Laravel y avisamos
        // al usuario con un mensaje claro.
        if ((! empty($fotosBase64) || ! empty($fotosEvidenciaBase64)) && ! extension_loaded('gd')) {
            Log::error('Descarga de PDF de ticket fallo: falta la extension GD de PHP.', [
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
            ]);

            return back()->with('error', 'No se pudo generar el PDF porque el servidor no tiene habilitada una extension requerida (GD). Contacta a sistemas para habilitarla.');
        }

        try {
            $pdf = Pdf::loadView('tickets.pdf', compact('ticket', 'fotosEvidenciaBase64', 'incluirBitacora', 'notasPDF', 'fotosBase64'))
                      ->setPaper('letter', 'portrait');

            return $pdf->download('Ticket_#' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . '.pdf');
        } catch (Throwable $e) {
            Log::error('Error generando PDF de ticket #' . $ticket->id . ': ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
                'exception' => get_class($e),
            ]);

            return back()->with('error', 'Ocurrio un problema al generar el PDF. Intenta de nuevo o contacta a sistemas si el error persiste.');
        }
    }

    // ==========================================
    // ELIMINAR TICKET (solo Administrador)
    // Pensado para limpiar tickets de prueba u otros que no
    // deben quedar en el sistema. Borra también sus archivos
    // adjuntos y deja rastro en la bitácora de auditoría.
    // ==========================================
    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user->can('delete', Ticket::class)) {
            abort(403, 'Solo un Administrador puede eliminar tickets.');
        }

        $ticket = Ticket::with('notas')->findOrFail($id);

        // Borrar archivos físicos asociados (evidencia original + fotos de resolución)
        if ($ticket->archivo_path && Storage::disk('public')->exists($ticket->archivo_path)) {
            Storage::disk('public')->delete($ticket->archivo_path);
        }
        if ($ticket->archivos_evidencia) {
            foreach ($ticket->archivos_evidencia as $foto) {
                if (Storage::disk('public')->exists($foto)) {
                    Storage::disk('public')->delete($foto);
                }
            }
        }
        if ($ticket->fotos_resolucion) {
            foreach ($ticket->fotos_resolucion as $foto) {
                if (Storage::disk('public')->exists($foto)) {
                    Storage::disk('public')->delete($foto);
                }
            }
        }

        $snapshot = $ticket->only(['id', 'area', 'departamento_destino', 'descripcion', 'status', 'user_id']);

        AuditLog::registrar(
            'ticket.eliminado',
            "Eliminó el ticket #{$ticket->id} (\"{$ticket->area}\", reportado por {$ticket->user->name}).",
            $ticket,
            $snapshot,
            null
        );

        // Las notas se eliminan en cascada a nivel de base de datos (FK onDelete cascade).
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', "Ticket #{$ticket->id} eliminado correctamente.");
    }
}
