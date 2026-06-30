<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckModuloAcceso;

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
            'admin'  => CheckAdmin::class,
            'modulo' => CheckModuloAcceso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();