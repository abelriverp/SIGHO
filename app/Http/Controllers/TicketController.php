<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Ticket::with('user')->latest();

        // Tickets que el usuario reportó personalmente (siempre los ve, sin importar su rol)
        $misReportesAbiertos  = (clone $query)->where('status', 'Abierto')->where('user_id', $user->id)->get();
        $misReportesResueltos = (clone $query)->where('status', 'Resuelto')->where('user_id', $user->id)->get();

        if ($user->isAdmin() || $user->isGerente()) {
            // Admin y Gerente ven TODOS los tickets a resolver (incluye los propios, por eso luego se excluyen abajo)
            $ticketsAbiertos  = (clone $query)->where('status', 'Abierto')->where('user_id', '!=', $user->id)->get();
            $ticketsResueltos = (clone $query)->where('status', 'Resuelto')->where('user_id', '!=', $user->id)->get();

        } elseif ($user->isTecnico()) {
            // Técnico ve tickets de SU departamento (a resolver), excluyendo los que él mismo reportó
            $ticketsAbiertos = (clone $query)->where('status', 'Abierto')
                ->where('departamento_destino', $user->departamento)
                ->where('user_id', '!=', $user->id)
                ->get();

            $ticketsResueltos = (clone $query)->where('status', 'Resuelto')
                ->where('departamento_destino', $user->departamento)
                ->where('user_id', '!=', $user->id)
                ->get();
        } else {
            // Usuario normal no tiene tickets "a resolver"
            $ticketsAbiertos  = collect();
            $ticketsResueltos = collect();
        }

        return view('tickets.index', compact(
            'ticketsAbiertos', 'ticketsResueltos',
            'misReportesAbiertos', 'misReportesResueltos'
        ));
    }

    public function create()
    {
        return view('tickets.create');
    }

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

        $data['user_id'] = auth()->id();
        $data['status']  = 'Abierto';

        $ticket = Ticket::create($data);

        $destinatarios = User::where(function($q) use ($ticket) {
            $q->where('role', 'admin')
              ->orWhere(function($q2) use ($ticket) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $ticket->departamento_destino);
              });
        })->get();

        Notification::send($destinatarios, new NuevoTicketNotification($ticket));

        return redirect()->route('tickets.index')->with('success', 'Reporte creado con éxito.');
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);
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

    public function resolve(Request $request, $id)
    {
        if (!auth()->user()->puedeResponderTickets()) {
            abort(403, 'No tienes permisos para resolver tickets.');
        }

        $request->validate(['notas_admin' => 'required|string']);

        $ticket = Ticket::findOrFail($id);

        if (auth()->user()->isTecnico() && $ticket->departamento_destino !== auth()->user()->departamento) {
            abort(403, 'Este ticket no pertenece a tu departamento.');
        }

        $ticket->update([
            'status'           => 'Resuelto',
            'notas_admin'      => $request->notas_admin,
            'fecha_resolucion' => now(),
        ]);

        return redirect()->route('tickets.index')->with('success', 'Ticket marcado como resuelto.');
    }

    public function downloadPDF($id)
    {
        $ticket = Ticket::with('user')->findOrFail($id);
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
            $type = pathinfo($path, PATHINFO_EXTENSION);

            if (in_array(strtolower($type), ['jpg', 'jpeg', 'png', 'webp'])) {
                $data   = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $pdf = Pdf::loadView('tickets.pdf', compact('ticket', 'base64'))
                  ->setPaper('letter', 'portrait');

        return $pdf->download('Ticket_#' . $ticket->id . '.pdf');
    }
}