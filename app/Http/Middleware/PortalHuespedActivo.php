<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Apaga el portal de huesped por QR (y su administracion) mientras
 * config('app.portal_huesped') sea false. Decision de Abel, nov 2026:
 * por ahora solo el personal levanta tickets.
 */
class PortalHuespedActivo
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.portal_huesped')) {
            abort(404);
        }

        return $next($request);
    }
}
