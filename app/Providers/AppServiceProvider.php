<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
    }
}
