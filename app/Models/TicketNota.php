<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketNota extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'nota',
        'tipo', // 'publica' o 'interna'
        'es_automatica', // true = generada por el sistema (resolución, confirmación, reapertura): nunca editable
        'nota_original',
        'editado_at',
        'editado_por',
    ];

    protected $casts = [
        'es_automatica' => 'boolean',
        'editado_at'    => 'datetime',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function esPublica(): bool
    {
        return $this->tipo === 'publica';
    }

    public function esInterna(): bool
    {
        return $this->tipo === 'interna';
    }

    // Las notas automáticas documentan una acción oficial del sistema
    // (resolución, confirmación, reapertura) y son parte del historial
    // de auditoría del ticket: nunca deben poder editarse, sin importar
    // quién sea el autor ni cuánto tiempo haya pasado.
    public function esEditable(): bool
    {
        return ! $this->es_automatica;
    }
}