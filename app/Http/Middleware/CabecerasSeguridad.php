<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas (endurecimiento nov 2026).
 *
 * - Nadie puede meter SIGHO en un iframe de otro sitio (clickjacking).
 * - El navegador no "adivina" tipos de archivo (un .txt subido no se ejecuta como HTML).
 * - No se filtra la URL interna a sitios externos (Referer).
 * - La CSP es deliberadamente permisiva con scripts (Alpine necesita eval y
 *   hay scripts en línea), pero bloquea plugins, cambiar <base> y mandar
 *   formularios a otro dominio.
 * - Páginas con sesión iniciada no se guardan en caché: en una PC compartida
 *   (recepción) el botón "atrás" tras cerrar sesión no muestra datos.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $h = $response->headers;
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'same-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        if (! $h->has('Content-Security-Policy')) {
            $h->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
        }
        $h->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        $esArchivo = $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            || $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse;
        if ($request->user() && ! $esArchivo && ! $h->has('Content-Disposition')) {
            $h->set('Cache-Control', 'no-store, private');
            $h->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
