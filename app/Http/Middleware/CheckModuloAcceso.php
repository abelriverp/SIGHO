<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModuloAcceso
{
    public function handle(Request $request, Closure $next, string $clave): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin siempre pasa
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Cargar módulos si no están cargados
        if (!$user->relationLoaded('modulos')) {
            $user->load('modulos');
        }

        if (!$user->tieneModulo($clave)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}