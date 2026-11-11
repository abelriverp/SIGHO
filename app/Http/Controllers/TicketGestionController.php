<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\TicketNota;
use Illuminate\Http\Request;

/**
 * Fase 6 (nov 2026): agrupar tickets (padre/hijo) y escalarlos a un
 * proveedor. Va aparte de TicketController (que ya pasa de 1,300 líneas)
 * para no seguir creciéndolo.
 */
class TicketGestionController extends Controller
{
    private function ticketEditable($id): Ticket
    {
        $u = auth()->user();
        if (! $u->puedeResponderTickets()) {
            abort(403, 'Solo técnicos y administradores agrupan o escalan tickets.');
        }
        $t = Ticket::findOrFail($id);
        if (! $u->can('view', $t)) {
            abort(403);
        }
        return $t;
    }

    private function nota(Ticket $t, string $texto): void
    {
        TicketNota::create(['ticket_id' => $t->id, 'user_id' => auth()->id(), 'nota' => $texto, 'tipo' => 'publica', 'es_automatica' => true]);
    }

    /** Pone este ticket como hijo de otro (mismo incidente). */
    public function agrupar(Request $request, $id)
    {
        $hijo = $this->ticketEditable($id);
        $padreId = (int) $request->validate(['ticket_padre_id' => 'required|integer'])['ticket_padre_id'];
        $padre = Ticket::find($padreId);

        $error = match (true) {
            ! $padre || ! auth()->user()->can('view', $padre) => 'No existe ese ticket o no tienes acceso a él.',
            $padre->id === $hijo->id                           => 'Un ticket no puede agruparse bajo sí mismo.',
            $padre->ticket_padre_id !== null                   => "El #{$padre->id} ya está agrupado bajo el #{$padre->ticket_padre_id}; agrúpalo bajo ese.",
            $hijo->hijos()->exists()                           => 'Este ticket ya agrupa a otros: agrupa los demás bajo él, no al revés.',
            ! $padre->abierto()                                => "El #{$padre->id} ya no está abierto.",
            default                                            => null,
        };
        if ($error) {
            return back()->with('error', $error);
        }

        $hijo->update(['ticket_padre_id' => $padre->id]);
        $this->nota($hijo, "Agrupado bajo el ticket #{$padre->id}: se atiende como el mismo incidente y se resolverá junto con él.");
        $this->nota($padre, "Se agrupó el ticket #{$hijo->id} (" . $hijo->user?->name . ') como parte de este incidente.');
        AuditLog::registrar('ticket.agrupado', "Agrupó el ticket #{$hijo->id} bajo el #{$padre->id}.", $hijo);

        return back()->with('success', "Ticket agrupado bajo el #{$padre->id}.");
    }

    public function desagrupar($id)
    {
        $hijo = $this->ticketEditable($id);
        if (! $hijo->ticket_padre_id) {
            return back();
        }
        $padreId = $hijo->ticket_padre_id;
        $hijo->update(['ticket_padre_id' => null]);
        $this->nota($hijo, "Se separó del ticket #{$padreId}; se atiende por su cuenta.");
        AuditLog::registrar('ticket.desagrupado', "Separó el ticket #{$hijo->id} del #{$padreId}.", $hijo);

        return back()->with('success', 'Ticket separado.');
    }

    /** Marca que el ticket depende de un proveedor externo (con su folio). */
    public function escalar(Request $request, $id)
    {
        $t = $this->ticketEditable($id);
        $data = $request->validate([
            'proveedor_id'    => 'required|integer',
            'folio_proveedor' => 'nullable|string|max:100',
        ]);
        $prov = Proveedor::find($data['proveedor_id']);
        if (! $prov) {
            return back()->with('error', 'Proveedor no válido.');
        }

        $t->update(['proveedor_id' => $prov->id, 'escalado_proveedor_at' => $t->escalado_proveedor_at ?? now(), 'folio_proveedor' => $data['folio_proveedor'] ?? null]);
        $this->nota($t, "Escalado al proveedor {$prov->nombre}" . (filled($data['folio_proveedor'] ?? null) ? " (folio {$data['folio_proveedor']})" : '') . '.');
        AuditLog::registrar('ticket.escalado_proveedor', "Escaló el ticket #{$t->id} a {$prov->nombre}.", $t);

        return back()->with('success', "Ticket escalado a {$prov->nombre}.");
    }

    public function quitarEscalamiento($id)
    {
        $t = $this->ticketEditable($id);
        if (! $t->proveedor_id) {
            return back();
        }
        $nombre = $t->proveedor?->nombre;
        $t->update(['proveedor_id' => null, 'escalado_proveedor_at' => null, 'folio_proveedor' => null]);
        $this->nota($t, "Se quitó el escalamiento a {$nombre}.");

        return back()->with('success', 'Escalamiento quitado.');
    }
}
