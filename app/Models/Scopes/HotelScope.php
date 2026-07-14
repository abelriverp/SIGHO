<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restringe automáticamente cualquier consulta Eloquent sobre un modelo
 * multipropiedad al hotel_code del usuario autenticado.
 *
 * Por qué existe: antes de esto, cada controlador tenía que acordarse de
 * escribir ->where('hotel_code', ...) a mano. AssetController, por ejemplo,
 * no lo hacía y cualquier usuario veía el inventario de todas las propiedades.
 * Aplicando este scope a nivel de modelo (vía el trait BelongsToHotel), un
 * desarrollador tendría que hacer un esfuerzo explícito para SALTARSE el
 * filtro (withoutGlobalScope), no para aplicarlo.
 */
class HotelScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // No filtramos si no hay usuario autenticado: cubre migraciones,
        // seeders, artisan tinker y jobs en cola, que no tienen sesión.
        //
        // Ojo: aquí ANTES había además un `if (app()->runningInConsole())
        // return;`. Se quitó a propósito porque `php artisan test` también
        // corre en consola, así que ese guard apagaba el filtro durante TODA
        // la suite de pruebas sin importar qué usuario estuviera autenticado
        // vía actingAs() — dejaba pasar datos de otras propiedades y ninguna
        // prueba lo notaba salvo la de aislamiento multipropiedad. El check
        // de "$user" ya cubre el caso real que se quería proteger.
        $user = auth()->user();

        if (!$user) {
            return;
        }

        // El Administrador tiene acceso total a todas las propiedades.
        if ($user->isAdmin()) {
            return;
        }

        $builder->where($model->qualifyColumn('hotel_code'), $user->hotel_code);
    }
}
