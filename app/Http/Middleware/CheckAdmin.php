<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Usamos el helper que creamos en el modelo User
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        // Si no es admin, le bloqueamos el paso con un error 403 (Forbidden)
        abort(403, 'No tienes permisos de Administrador para ver esta página.');
    }
}