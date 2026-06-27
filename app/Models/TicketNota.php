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
}