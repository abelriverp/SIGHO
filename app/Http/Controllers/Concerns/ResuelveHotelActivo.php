<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Hotel;

/**
 * Reemplaza el patrón repetido `session('active_hotel_code', env('HOTEL_CODE', ...))`
 * que existía en varios controladores.
 *
 * Cada usuario tiene su propia propiedad asignada (users.hotel_code); ese
 * sigue siendo el caso para usuario/técnico/gerente. El Administrador es
 * distinto: supervisa varias propiedades, así que además puede elegir cuál
 * está viendo desde el selector del menú (ver
 * AdminSettingsController::propiedadActivaUpdate), que ahora sí guarda la
 * selección en session('active_hotel_code') — antes esa llave nunca se
 * llegaba a establecer en ningún lado, así que un Admin quedaba fijo al
 * HOTEL_CODE del .env sin poder cambiarlo desde estas pantallas
 * (Dashboard/Condominios/Inventario/Habitaciones), aunque hubiera dado de
 * alta más propiedades en Admin > Propiedades.
 *
 * La regla real vive en Hotel::activoParaUsuario() para que el selector del
 * layout (AppServiceProvider) use exactamente la misma lógica que estos
 * controladores, que arman su propio ->where('hotel_code', ...) o usan
 * DB::table() en vez del Eloquent scope. Los modelos con BelongsToHotel ya
 * resuelven la visibilidad del Admin de otra forma (HotelScope se salta el
 * filtro para su rol) y no dependen de este trait.
 */
trait ResuelveHotelActivo
{
    protected function hotelActivo(): string
    {
        return Hotel::activoParaUsuario(auth()->user());
    }
}
