<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Encabezado de una comparación "salidas vs llegadas" para detectar
 * posibles extensiones de estancia (ver App\Services\Recepcion\
 * ExtensionDetectorService). Las coincidencias individuales están en
 * ExtensionDeteccionItem.
 */
class ExtensionDeteccion extends Model
{
    use BelongsToHotel;

    protected $table = 'recepcion_extension_detecciones';

    protected $fillable = [
        'hotel_code',
        'user_id',
        'archivo_salidas',
        'archivo_llegadas',
        'total_coincidencias',
    ];

    public function items()
    {
        return $this->hasMany(ExtensionDeteccionItem::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
