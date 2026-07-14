<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\User;
use App\Models\Departamento;
use App\Models\AuditLog;
use App\Notifications\NuevoTicketNotification;
use App\Notifications\TicketReasignadoNotification;
use App\Notifications\TicketResueltoNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Throwable;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

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
    // HELPER: Generar token con formato HEMZT-YYYY-NNNNN-TXXXX
    // Configurable desde .env → HOTEL_CODE=HEMZT
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

            // Control de acceso: cada rol solo puede ver lo suyo
            if ($ticketEncontrado) {
                $acceso = false;

                if ($user->isAdmin() || $user->isGerente()) {
                    $acceso = true;
                } elseif ($user->isTecnico()) {
                    $acceso = $ticketEncontrado->departamento_destino === $user->departamento
                              || $ticketEncontrado->user_id === $user->id;
                } else {
                    // usuario normal: solo puede ver tokens de sus propios tickets
                    $acceso = $ticketEncontrado->user_id === $user->id;
                }

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
        $filtros = $request->only(['departamento_destino', 'status', 'fecha_desde', 'fecha_hasta', 'prioridad']);

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
        return view('tickets.create');
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

        $data = $request->validate([
            'area'                  => 'required|string|max:255',
            'departamento_destino'  => ['required', 'string', Rule::in($this->departamentosValidos())],
            'descripcion_ubicacion' => 'nullable|string|max:255',
            'descripcion'           => 'required|string',
            'prioridad'             => 'required|string|in:Baja,Media,Alta',
            'archivos'              => 'nullable|array|max:5',
            'archivos.*'            => 'file|mimes:jpeg,png,jpg,mp4,mov|max:6144',

            // Sección dinámica exclusiva de "Sistemas"
            'tipo_solicitud_sistemas' => 'nullable|required_if:departamento_destino,Sistemas|in:alta_colaborador,modificacion_sistema,otro',
            'otro_detalle'            => 'nullable|required_if:tipo_solicitud_sistemas,otro|string|max:1000',

            'aplicativo'            => 'nullable|required_if:tipo_solicitud_sistemas,alta_colaborador|in:symphony,opera,mail,sap,wintb,guesthotel',
            'no_colaborador'        => 'nullable|required_if:tipo_solicitud_sistemas,alta_colaborador|string|max:50',
            'nombre_completo'       => 'nullable|required_if:tipo_solicitud_sistemas,alta_colaborador|string|max:255',
            'puesto'                => 'nullable|required_if:tipo_solicitud_sistemas,alta_colaborador|string|max:255',
            'perfil_replicar'       => 'nullable|string|max:255',
            'correo_institucional'  => ['nullable', 'required_if:aplicativo,sap', 'email', 'regex:/@hotelesemporio\.com$/i'],
            'jefe_directo'          => 'nullable|required_if:aplicativo,mail|string|max:255',

            'aplicativo_mod'        => 'nullable|required_if:tipo_solicitud_sistemas,modificacion_sistema|in:symphony,opera,mail,sap,wintb,guesthotel',
            'detalle_modificacion'  => 'nullable|required_if:tipo_solicitud_sistemas,modificacion_sistema|string|max:1000',
        ], [
            'correo_institucional.regex' => 'El correo debe ser institucional (@hotelesemporio.com).',
            'archivos.max' => 'Puedes adjuntar máximo 5 archivos.',
            'archivos.*.uploaded' => 'Uno de los archivos no se pudo subir porque es demasiado grande para la configuración actual del servidor. Intenta con un archivo más pequeño o contacta a sistemas.',
            'archivos.*.max' => 'Cada archivo no puede pesar más de 6 MB.',
            'archivos.*.mimes' => 'Cada archivo debe ser una imagen (jpg, png) o un video (mp4, mov).',
        ]);

        // Construir campos_extra según lo que se seleccionó (solo aplica a Sistemas)
        $camposExtra = null;
        if ($data['departamento_destino'] === 'Sistemas' && !empty($data['tipo_solicitud_sistemas'])) {
            $tipo = $data['tipo_solicitud_sistemas'];
            $camposExtra = ['tipo_solicitud' => $tipo];

            if ($tipo === 'otro') {
                $camposExtra['otro_detalle'] = $data['otro_detalle'] ?? null;
            } elseif ($tipo === 'alta_colaborador') {
                $camposExtra['aplicativo']      = $data['aplicativo'] ?? null;
                $camposExtra['no_colaborador']  = $data['no_colaborador'] ?? null;
                $camposExtra['nombre_completo'] = $data['nombre_completo'] ?? null;
                $camposExtra['puesto']          = $data['puesto'] ?? null;

                if (($data['aplicativo'] ?? null) === 'opera') {
                    $camposExtra['perfil_replicar'] = $data['perfil_replicar'] ?? null;
                } elseif (($data['aplicativo'] ?? null) === 'sap') {
                    $camposExtra['correo_institucional'] = $data['correo_institucional'] ?? null;
                } elseif (($data['aplicativo'] ?? null) === 'mail') {
                    $camposExtra['jefe_directo'] = $data['jefe_directo'] ?? null;
                }
            } elseif ($tipo === 'modificacion_sistema') {
                $camposExtra['aplicativo']           = $data['aplicativo_mod'] ?? null;
                $camposExtra['detalle_modificacion'] = $data['detalle_modificacion'] ?? null;
            }
        }

        // Solo conservar las columnas reales de la tabla + campos_extra
        $data = collect($data)->only([
            'area', 'departamento_destino', 'descripcion_ubicacion', 'descripcion', 'prioridad',
        ])->toArray();
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
        $ticket = Ticket::with(['user', 'notas.user'])->findOrFail($id);
        $user   = auth()->user();

        if ($user->isAdmin() || $user->isGerente()) {
            // permitido
        } elseif ($user->isTecnico()) {
            if ($ticket->departamento_destino !== $user->departamento && $ticket->user_id !== $user->id) {
                abort(403, 'No tienes permiso para ver este ticket.');
            }
        } else {
            if ($ticket->user_id !== $user->id) {
                abort(403, 'No tienes permiso para ver este ticket.');
            }
        }

        return view('tickets.show', compact('ticket'));
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

        if ($ticket->user_id === $user->id) {
            abort(403, 'No puedes resolver un ticket que tú mismo creaste.');
        }

        if ($user->isTecnico() && $ticket->departamento_destino !== $user->departamento) {
            abort(403, 'Este ticket no pertenece a tu departamento.');
        }

        $request->validate([
            'notas_admin'        => 'required|string',
            'fotos_resolucion'   => 'nullable|array|max:4',
            'fotos_resolucion.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'fotos_resolucion.*.uploaded' => 'Una de las fotos no se pudo subir porque es demasiado grande para la configuración actual del servidor.',
            'fotos_resolucion.*.max' => 'Cada foto no puede pesar más de 4 MB.',
            'fotos_resolucion.*.image' => 'Cada archivo debe ser una imagen (jpg, png, webp).',
        ]);

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
        ]);

        TicketNota::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user->id,
            'nota'          => 'Ticket marcado como resuelto. Solución: ' . $request->notas_admin . ' | Token de resolución: ' . $token,
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
            $ticket->fresh()->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by'])
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

        if ($ticket->user_id === $user->id) {
            abort(403, 'No puedes reasignar un ticket que tú mismo reportaste.');
        }

        // Un técnico solo puede reasignar tickets que le llegaron a él
        // (es decir, que el ticket está actualmente en su departamento).
        // Un Administrador puede reasignar cualquier ticket.
        if ($user->isTecnico() && $ticket->departamento_destino !== $user->departamento) {
            abort(403, 'Este ticket no pertenece a tu departamento.');
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

        if ($ticket->user_id !== $user->id) {
            abort(403, 'Solo el usuario que reportó puede confirmar este ticket.');
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

        if ($ticket->user_id !== $user->id) {
            abort(403, 'Solo el usuario que reportó puede reabrir este ticket.');
        }

        if ($ticket->status !== 'Resuelto') {
            return back()->with('error', 'Este ticket no puede ser reabierto en su estado actual.');
        }

        $antes = $ticket->only(['status']);

        $ticket->update([
            'status'            => 'Reabierto',
            'motivo_reapertura' => $request->motivo_reapertura,
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

        return redirect()->route('tickets.show', $ticket->id)
            ->with('warning', 'Ticket reabierto. El equipo técnico ha sido notificado.');
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

        $esQuienResolvio = $ticket->resolved_by === $user->id;

        if (!$esQuienResolvio && !$user->isAdmin()) {
            abort(403, 'Solo quien resolvió este ticket o un Administrador puede deshacer la resolución.');
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

        $antes = $ticket->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by']);

        $ticket->update([
            'status'           => 'Abierto',
            'notas_admin'      => null,
            'fecha_resolucion' => null,
            'fotos_resolucion' => null,
            'token_resolucion' => null,
            'ip_resolucion'    => null,
            'resolved_by'      => null,
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
            $ticket->fresh()->only(['status', 'notas_admin', 'fecha_resolucion', 'token_resolucion', 'resolved_by'])
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

        if ($user->isUsuario()) {
            abort(403, 'No tienes permisos para agregar notas.');
        }

        TicketNota::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'nota'      => $request->nota,
            'tipo'      => $request->tipo,
        ]);

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

        if ($user->isAdmin() || $user->isGerente()) {
            // permitido
        } elseif ($user->isTecnico()) {
            if ($ticket->departamento_destino !== $user->departamento && $ticket->user_id !== $user->id) {
                abort(403, 'No tienes permiso para descargar este ticket.');
            }
        } else {
            if ($ticket->user_id !== $user->id) {
                abort(403, 'No tienes permiso para descargar este ticket.');
            }
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

        if (!$user->isAdmin()) {
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

        Au