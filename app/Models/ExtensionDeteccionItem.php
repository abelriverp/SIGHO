<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una posible extensión de estancia detectada (mismo huésped en la lista de
 * salidas y en la de llegadas, por similitud de nombre). No usa
 * BelongsToHotel a propósito: siempre se consulta vía la relación con el
 * encabezado (ExtensionDeteccion), que ya está filtrado por propiedad.
 */
class ExtensionDeteccionItem extends Model
{
    protected $table = 'recepcion_extension_deteccion_items';

    public $timestamps = false;

    protected $fillable = [
        'extension_deteccion_id',
        'huesped_salida',
        'huesped_llegada',
        'similitud',
    ];

    protected $casts = [
        'similitud' => 'float',
    ];

    // Confianza (%) a partir de la cual una coincidencia se resalta en
    // verde como "alta confianza" en vez de amarillo — un solo lugar en
    // vez de repetir el número en cada vista que liste coincidencias.
    public const UMBRAL_CONFIANZA_ALTA = 75.0;

    public function deteccion()
    {
        return $this->belongsTo(ExtensionDeteccion::class, 'extension_deteccion_id');
    }

    public function confianzaAlta(): bool
    {
        return $this->similitud >= self::UMBRAL_CONFIANZA_ALTA;
    }
}
