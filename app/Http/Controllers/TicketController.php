<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // ==========================================
    // HELPER: Generar token con formato HEMZT-YYYY-NNNNN-TXXXX
    // Configurable desde .env → HOTEL_CODE=HEMZT
    // T = tipo (R=Resolución, C=Confirmación, y a futuro más)
    // ==========================================
    private function generarToken(int $ticketId, string $tipo): string
    {
        $hotelCode = strtoupper(env('HOTEL_CODE', 'EMP'));
        $año       = now()->year;
        $folio     = str_pad($ticketId, 5, '0', STR_PAD_LEFT);
        $rand      = strtoupper(Str::random(4));
        return "{$hotelCode}-{$año}-{$folio}-{$tipo}{$rand}";
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

        $base = Ticket::with('user')->latest();

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
        $data = $request->validate([
            'area'                  => 'required|string|max:255',
            'departamento_destino'  => 'required|string',
            'descripcion_ubicacion' => 'nullable|string|max:255',
            'descripcion'           => 'required|string',
            'prioridad'             => 'required|string|in:Baja,Media,Alta',
            'archivo'               => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov|max:20480',
        ]);

        if ($request->hasFile('archivo')) {
            $data['archivo_path'] = $request->file('archivo')->store('evidencias_tickets', 'public');
        }

        $data['user_id']    = auth()->id();
        $data['status']     = 'Abierto';
        $data['hotel_code'] = strtoupper(env('HOTEL_CODE', 'EMP'));

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
        ]);

        $rutas = [];
        if ($request->hasFile('fotos_resolucion')) {
            foreach ($request->file('fotos_resolucion') as $foto) {
                $rutas[] = $foto->store('tickets/fotos', 'public');
            }
        }

        $token = $this->generarToken($ticket->id, 'R');

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

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', "Ticket marcado como resuelto. Token: {$token}");
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

        $token = $this->generarToken($ticket->id, 'C');

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

        return redirect()->route('tickets.show', $ticket->id)
            ->with('warning', 'Ticket reabierto. El equipo técnico ha sido notificado.');
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

        $base64 = null;
        if ($ticket->archivo_path && Storage::disk('public')->exists($ticket->archivo_path)) {
            $path = storage_path('app/public/' . $ticket->archivo_path);
            $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $data   = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
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

        $pdf = Pdf::loadView('tickets.pdf', compact('ticket', 'base64', 'incluirBitacora', 'notasPDF', 'fotosBase64'))
                  ->setPaper('letter', 'portrait');

        return $pdf->download('Ticket_#' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }
}