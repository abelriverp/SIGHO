<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckModuloAcceso;
use App\Http\Middleware\CheckSupervisor;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware global web
        $middleware->appendToGroup('web', ForcePasswordChange::class);

        // Aliases para usar en rutas con parámetros
        $middleware->alias([
            'admin'      => CheckAdmin::class,
            'modulo'     => CheckModuloAcceso::class,
            'supervisor' => CheckSupervisor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El límite de tamaño de subida (post_max_size / upload_max_filesize
        // en php.ini) rechaza la petición ANTES de que Laravel llegue a
        // validar los archivos uno por uno, así que sin este handler el
        // usuario ve la página cruda de error 413 en vez de un mensaje
        // entendible. Redirigimos de vuelta con el mismo mensaje que ya
        // usamos para archivos individuales demasiado grandes.
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $mensaje = 'Los archivos que intentaste subir son demasiado grandes en conjunto '
                . '(el servidor tiene un límite total por solicitud). Intenta subir menos '
                . 'fotos a la vez o con menor resolución.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $mensaje], 413);
            }

            return back()->withInput($request->except(['archivos', 'fotos_resolucion']))
                ->withErrors(['archivos' => $mensaje]);
        });
    })->create();