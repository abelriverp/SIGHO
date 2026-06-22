<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Añadido para testing/seeds
use Illuminate\Database\Eloquent\Model;

class HabitacionActivo extends Model
{
    use HasFactory;

    // 🛠️ Fuerza el nombre exacto de tu tabla en la base de datos 
    // (Cambia 'habitaciones_activos' por el nombre real de tu tabla si es diferente)
    protected $table = 'habitaciones_activos'; 

    protected $fillable = [
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
        'notas_tecnicas'
    ];
}