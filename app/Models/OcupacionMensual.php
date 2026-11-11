<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Cuartos-noche ocupados por mes (Fase 6), capturados a mano desde el
 * tablero de dirección (sale del reporte de ocupación de Opera). Sirve
 * para el KPI costo de TI por habitación ocupada.
 */
class OcupacionMensual extends Model
{
    use BelongsToHotel;

    protected $table = 'ocupacion_mensual';

    protected $fillable = ['hotel_code', 'mes', 'cuartos_noche', 'capturado_por'];
}
