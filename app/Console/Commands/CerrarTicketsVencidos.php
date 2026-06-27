<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketNota;
use Illuminate\Console\Command;

class CerrarTicketsVencidos extends Command
{
    protected $signature   = 'tickets:cerrar-vencidos';
    protected $description = 'Cierra automáticamente tickets Resueltos sin confirmar después de 72 horas';

    public function handle(): void
    {
        // Tickets resueltos hace más de 72 horas sin confirmar
        $tickets = Ticket::where('status', 'Resuelto')
            ->where('fecha_resolucion', '<=', now()->subHours(72))
            ->whereNull('confirmado_usuario')
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->update([
                'status'             => 'Cerrado',
                'confirmado_usuario' => false,   // false = cierre automático (no confirmado manualmente)
                'fecha_confirmacion' => now(),
            ]);

            TicketNota::create([
                'ticket_id' => $ticket->id,
                'user_id'   => null,   // sistema
                'nota'      => 'Ticket cerrado automáticamente por el sistema tras 72 horas sin respuesta del usuario.',
                'tipo'      => 'publica',
            ]);
        }

        $this->info("✅ {$tickets->count()} ticket(s) cerrado(s) automáticamente.");
    }
}