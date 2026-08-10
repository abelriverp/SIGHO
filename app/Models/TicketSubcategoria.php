<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * "Sistema" (Facto, Simphony, Opera, GestHotel, WinTB, Avance, ...) anidado
 * bajo una TicketCategoria cuyo requiere_subcategoria = true. Hereda la
 * propiedad (hotel_code) de su categoría — no lleva la suya propia para no
 * duplicar el scope en cada nivel del árbol.
 */
class TicketSubcategoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_categoria_id',
        'label',
        'nota',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(TicketCategoria::class, 'ticket_categoria_id');
    }

    public function campos()
    {
        return $this->hasMany(TicketCampo::class)->orderBy('orden');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
