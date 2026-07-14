<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Añadido para testing/seeds
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class HabitacionActivo extends Model
{
    use HasFactory, BelongsToHotel;

    // 🛠️ Nombre exacto de la tabla en la base de datos (creada por la migración)
    protected $table = 'habitacion_activos'; 

    protected $fillable = [
        'hotel_code',
        'numero_habitacion',
        'piso',
        'ap_modelo',
        'ap_serie',
        'ap_mac',
        'ap_ip',
        'tv_marca',
        'tv_modelo',
        'tv_serie',
        'tv_pulgadas',
        'control_remoto_estado',
        'control_serie',
        'caja_fuerte_modelo',
        'caja_fuerte_serie',
        'caja_fuerte_bateria',
        'notas_tecnicas',
    ];
}