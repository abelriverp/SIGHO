<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class ActivoItem extends Model
{
    use BelongsToHotel;

    protected $table = 'activo_items';

    protected $fillable = [
        'hotel_code',
        'plantilla_id',
        'area_nombre',
        'ubicacion',
        'nombre_item',
        'valores',
        'notas',
    ];

    protected $casts = [
        'valores' => 'array',
    ];

    /** Plantilla (área) a la que pertenece este ítem */
    public function plantilla()
    {
        return $this->belongsTo(ActivoPlantilla::class, 'plantilla_id');
    }

    /**
     * Devuelve el valor de un campo por su nombre.
     * Útil en Blade: $item->valor('Modelo')
     */
    public function valor(string $campo): mixed
    {
        return $this->valores[$campo] ?? null;
    }
}
