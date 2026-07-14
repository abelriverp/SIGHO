<?php

namespace App\Providers;

use App\Models\Hotel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límite de intentos de login: por email+IP (no solo por IP) para que
        // un atacante no pueda "repartir" intentos entre varias IPs sin límite,
        // y para que un vecino en la misma red/oficina no se vea bloqueado por
        // los intentos fallidos de otro usuario.
        RateLimiter::for('login', function ($request) {
            $llave = strtolower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($llave)->response(function ($request, array $headers) {
                return back()
                    ->withErrors(['email' => 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.'])
                    ->onlyInput('email');
            });
        });

        // Comparte la lista de propiedades con el layout para el selector de
        // "propiedad activa" del Admin (ver Hotel::activoParaUsuario y
        // AdminSettingsController::propiedadActivaUpdate). Solo se consulta
        // cuando hay un Admin autenticado: el resto de roles está fijo a su
        // propia propiedad y no necesita este dato en cada request.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if ($user && $user->isAdmin()) {
                $view->with([
                    'hotelesDisponibles'      => Hotel::orderBy('nombre')->get(),
                    'hotelActivoSeleccionado' => Hotel::activoParaUsuario($user),
                ]);
            }
        });
    }
}
