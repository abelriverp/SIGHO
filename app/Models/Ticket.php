<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'area',
        'departamento_destino',
        'descripcion_ubicacion',
        'descripcion',
        'prioridad',
        'status',
        'archivo_path',
        'fotos_resolucion',
        'notas_admin',
        'fecha_resolucion',
        'confirmado_usuario',
        'fecha_confirmacion',
        'motivo_reapertura',
        'token_resolucion',
        'token_confirmacion',
        'ip_resolucion',
        'ip_confirmacion',
    ];

    protected $casts = [
        'fotos_resolucion'   => 'array',
        'confirmado_usuario' => 'boolean',
        'fecha_confirmacion' => 'datetime',
        'fecha_resolucion'   => 'datetime',
    ];

    // Relación con el usuario que creó el ticket
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con las notas de la bitácora
    public function notas()
    {
        return $this->hasMany(TicketNota::class);
    }
}