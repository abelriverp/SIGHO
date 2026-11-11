<?php

namespace App\Providers;

use App\Models\Hotel;
use Carbon\Carbon;
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
        // Fase 4: ping del sistema; las pruebas lo reemplazan con uno falso.
        $this->app->bind(\App\Services\Monitoreo\Pinger::class, \App\Services\Monitoreo\PingSistema::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Carbon no sigue automáticamente el locale de la app: sin esto,
        // diffForHumans() (ej. "abierto desde..." en la campana de Alertas)
        // sale en inglés aunque config('app.locale') ya esté en 'es'. Se
        // amarra a config('app.locale') en vez de fijar 'es' a mano para que
        // no se vuelva a desincronizar si el locale de la app cambia.
        Carbon::setLocale(config('app.locale'));

        // Los enlaces a archivos subidos usan el mismo host con el que se
        // entró (IP o nombre), no solo APP_URL.
        if (! $this->app->runningInConsole()) {
            config(['filesystems.disks.public.url' => url('/archivos')]);
        }

        // Contraseñas: mínimo 8 caracteres con letras y números (antes 6 letras).
        \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers());

        // Límite de intentos de login: por email+IP (no solo por IP) para que
        // un atacante no pueda "repartir" intentos entre varias IPs sin límite,
        // y para que un vecino en la misma red/oficina no se vea bloqueado por
        // los intentos fallidos de otro usuario.
        RateLimiter::for('login', function ($request) {
            $llave = strtolower((string) $request->input('email')) . '|' . $request->ip();

            $respuesta = fn ($request, array $headers) => back()
                ->withErrors(['email' => 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.'])
                ->onlyInput('email');

            // Además, tope por IP (endurecimiento nov 2026): impide probar
            // muchas cuentas distintas desde una misma PC.
            return [
                Limit::perMinute(5)->by($llave)->response($respuesta),
                Limit::perMinutes(15, 30)->by('ip|' . $request->ip())->response($respuesta),
            ];
        });

        // Portal de huésped por QR (sin login, ver GuestPortalController):
        // por token+IP para no dejar que alguien spamee tickets desde un
        // solo enlace, sin bloquear a otra habitación que use su propio
        // token normalmente.
        RateLimiter::for('guest-request', function ($request) {
            $llave = $request->route('token') . '|' . $request->ip();

            return Limit::perHour(10)->by($llave)->response(function ($request, array $headers) {
                return back()
                    ->withErrors(['tipo_solicitud' => 'Se enviaron demasiadas solicitudes desde este enlace. Espera un momento e intenta de nuevo, o contacta directamente a recepción.'])
                    ->withInput();
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
