<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModuloAcceso
{
    // Módulos libres — todos los usuarios autenticados tienen acceso sin asignación
    protected array $modulosLibres = [
        'gestion_tickets',
    ];

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

        // Módulos libres — no requieren asignación
        if (in_array($clave, $this->modulosLibres)) {
            return $next($request);
        }

        // Para el resto de módulos, verificar asignación
        if (!$user->relationLoaded('modulos')) {
            $user->load('modulos');
        }

        if (!$user->modulos->contains('clave', $clave)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}