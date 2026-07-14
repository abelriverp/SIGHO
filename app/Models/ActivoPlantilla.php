<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class ActivoPlantilla extends Model
{
    use BelongsToHotel;

    protected $table = 'activo_plantillas';

    protected $fillable = [
        'hotel_code',
        'area_nombre',
        'descripcion',
        'campos',
        'activa',
    ];

    protected $casts = [
        'campos' => 'array',
        'activa' => 'boolean',
    ];

    /** Ítems registrados bajo esta plantilla */
    public function items()
    {
        return $this->hasMany(ActivoItem::class, 'plantilla_id');
    }

    /** Tipos de campo disponibles en el builder */
    public static function tiposCampo(): array
    {
        return [
            'text'   => 'Texto libre',
            'number' => 'Número',
            'select' => 'Lista de opciones',
            'date'   => 'Fecha',
            'email'  => 'Correo electrónico',
        ];
    }
}
