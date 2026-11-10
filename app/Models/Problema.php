<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Problema (Fase 5): la causa comun detras de varios tickets. Documenta
 * solucion temporal, causa raiz y solucion definitiva.
 */
class Problema extends Model
{
    use BelongsToHotel;

    public const ESTADOS = [
        'abierto'            => 'Abierto',
        'en_analisis'        => 'En análisis',
        'causa_identificada' => 'Causa identificada',
        'resuelto'           => 'Resuelto',
        'cerrado'            => 'Cerrado',
    ];

    protected $fillable = [
        'hotel_code', 'titulo', 'descripcion', 'estado', 'prioridad', 'workaround', 'causa_raiz',
        'solucion_definitiva', 'asset_id', 'ubicacion_id', 'responsable_id', 'creado_por', 'resuelto_at',
    ];

    protected $casts = ['resuelto_at' => 'datetime'];

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'problema_ticket');
    }

    public function cambios()
    {
        return $this->hasMany(Cambio::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function abierto(): bool
    {
        return ! in_array($this->estado, ['resuelto', 'cerrado'], true);
    }
}
