<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Encabezado de un análisis de correos de huéspedes hecho a partir de un
 * PDF de reservas subido por Recepción (ver App\Services\Recepcion\
 * EmailValidatorService, port a PHP del validador de correos que antes
 * vivía como app de escritorio independiente). Los resultados por correo
 * están en EmailValidacionItem.
 */
class EmailValidacion extends Model
{
    use BelongsToHotel;

    protected $table = 'recepcion_email_validaciones';

    protected $fillable = [
        'hotel_code',
        'user_id',
        'archivo_nombre',
        'total',
        'ok',
        'typos',
        'genericos',
        'falsos',
    ];

    public function items()
    {
        return $this->hasMany(EmailValidacionItem::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
