<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        // Si no hay usuario logueado, dejamos pasar SIN TOCAR NADA
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Solo aplica a usuarios normales con must_change_password activo
        if ($user->must_change_password && !$user->isAdmin()) {
            
            // Rutas que se permiten para no crear bucle infinito
            $rutasPermitidas = [
                'password.change',
                'password.change.update', 
                'logout',
                'login',
                'login.post',
            ];
            
            if (!$request->routeIs(...$rutasPermitidas)) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}