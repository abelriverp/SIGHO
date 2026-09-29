<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Encabezado de un análisis del reporte "Profiles with e-mails" (PMS) que
 * Recepción sube todos los días después de las llegadas. A diferencia de
 * EmailValidacion (que solo junta el conjunto de correos únicos de
 * cualquier PDF de reservas), aquí cada resultado queda ligado a su
 * reserva — ver App\Services\Recepcion\PerfilesCorreoValidatorService y
 * App\Models\PerfilCorreoValidacionItem.
 */
class PerfilCorreoValidacion extends Model
{
    use BelongsToHotel;

    protected $table = 'recepcion_perfil_correo_validaciones';

    protected $fillable = [
        'hotel_code',
        'user_id',
        'archivo_nombre',
        'total',
        'ok',
        'sin_correo',
        'sufijo_invalido',
        'dominio_invalido',
        'rechazado_smtp',
        'otros',
    ];

    public function items()
    {
        return $this->hasMany(PerfilCorreoValidacionItem::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Reservas que requieren seguimiento de Calidad: cualquier cosa que no
     * sea "OK" (sin correo, typo de sufijo/dominio, formato inválido, o
     * rechazado por el propio servidor de correo).
     */
    public function totalConProblema(): int
    {
        return $this->total - $this->ok;
    }
}
