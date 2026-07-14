<?php

namespace App\Http\Controllers\Concerns;

/**
 * Reemplaza el patrón repetido `session('active_hotel_code', env('HOTEL_CODE', ...))`
 * que existía en varios controladores. Ese patrón nunca llegaba a establecerse
 * en ningún lado (no hay ninguna pantalla que haga `session(['active_hotel_code' => ...])`),
 * así que en la práctica siempre caía al valor fijo de .env — es decir, todos
 * los usuarios operaban sobre la misma propiedad sin importar quién iniciara sesión.
 *
 * Ahora que cada usuario tiene su propia propiedad asignada (users.hotel_code),
 * este trait centraliza de dónde sale el "hotel activo" para un controlador:
 *  - Usuario normal: su propiedad asignada.
 *  - Administrador: por ahora, la propiedad activa de la instalación (.env),
 *    ya que todavía no existe un selector de "ver todas las propiedades" en
 *    estas pantallas (Condominios/Inventario/Habitaciones/Dashboard). Los
 *    modelos Eloquent con BelongsToHotel ya lo resuelven distinto: el Admin
 *    SÍ ve todas las propiedades ahí porque HotelScope se salta el filtro
 *    para su rol. Aquí sólo cubrimos los controladores que arman su propio
 *    ->where('hotel_code', ...) o usan DB::table() en vez del Eloquent scope.
 */
trait ResuelveHotelActivo
{
    protected function hotelActivo(): string
    {
        $user = auth()->user();

        if ($user && $user->hotel_code) {
            return strtoupper($user->hotel_code);
        }

        return strtoupper(env('HOTEL_CODE', 'HEMZT'));
    }
}
