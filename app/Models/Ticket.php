<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class Ticket extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_code',
        'user_id',
        'resolved_by',
        'area',
        'departamento_destino',
        'veces_reasignado',
        'descripcion_ubicacion',
        'descripcion',
        'campos_extra',
        'prioridad',
        'status',
        'archivo_path',
        'archivos_evidencia',
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
        'archivos_evidencia' => 'array',
        'campos_extra'       => 'array',
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