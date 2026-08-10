<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Regla de SLA (horas para resolver un ticket, y horas antes del
 * vencimiento para avisar "por vencer") por propiedad y/o departamento y
 * prioridad. hotel_code/departamento_id nulos = política "default" que
 * aplica cuando no hay una fila más específica (ver App\Services\SlaService).
 *
 * A propósito NO usa BelongsToHotel: una fila global (hotel_code = null)
 * debe verse sin importar qué propiedad tenga activa el usuario, así que el
 * filtrado por propiedad se hace a mano en el controlador/servicio, no vía
 * scope automático.
 */
class SlaPolitica extends Model
{
    use HasFactory;

    protected $table = 'sla_politicas';

    protected $fillable = [
        'hotel_code',
        'departamento_id',
        'prioridad',
        'horas_resolucion',
        'horas_alerta_temprana',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Políticas relevantes para una propiedad: las suyas propias más las
     * globales (hotel_code null), para que el panel de administración
     * pueda mostrar "qué aplica aquí" sin mezclar las de otras propiedades.
     */
    public function scopeParaPropiedad($query, string $hotelCode)
    {
        return $query->where(function ($q) use ($hotelCode) {
            $q->where('hotel_code', $hotelCode)->orWhereNull('hotel_code');
        });
    }
}
