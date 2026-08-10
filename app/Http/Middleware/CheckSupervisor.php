<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Igual que CheckAdmin, pero también deja pasar a Gerente. Pensado para
 * pantallas de configuración que no son "solo HQ" (como Usuarios o
 * Propiedades) sino que un Gerente también debe poder ajustar para su
 * propia propiedad — hoy: catálogo de categorías de tickets.
 */
class CheckSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->puedeVerTodos()) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para administrar esta sección.');
    }
}
