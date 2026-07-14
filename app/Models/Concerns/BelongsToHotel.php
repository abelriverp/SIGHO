<?php

namespace App\Models\Concerns;

use App\Models\Scopes\HotelScope;

/**
 * Aplica a cualquier modelo con columna hotel_code:
 *  - Filtra automáticamente toda consulta por la propiedad del usuario
 *    autenticado (Admin ve todas). Ver HotelScope.
 *  - Al crear un registro sin hotel_code explícito, lo asigna solo con
 *    la propiedad del usuario autenticado.
 */
trait BelongsToHotel
{
    public static function bootBelongsToHotel(): void
    {
        static::addGlobalScope(new HotelScope);

        static::creating(function ($model) {
            if (empty($model->hotel_code)) {
                $user = auth()->user();
                if ($user && $user->hotel_code) {
                    $model->hotel_code = $user->hotel_code;
                }
            }
        });
    }

    /**
     * Consulta explícitamente una propiedad distinta a la del usuario
     * autenticado (por ejemplo, reportes cruzados que solo usa Admin).
     */
    public function scopeDeHotel($query, string $hotelCode)
    {
        return $query->withoutGlobalScope(HotelScope::class)->where('hotel_code', $hotelCode);
    }

    /**
     * Ignora por completo el filtro de propiedad. Úsalo con criterio:
     * pensado para pantallas de Administrador que consolidan varias
     * propiedades a la vez.
     */
    public function scopeTodasLasPropiedades($query)
    {
        return $query->withoutGlobalScope(HotelScope::class);
    }
}
