<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Sirve los archivos subidos (fotos de tickets, contratos, dictámenes, PDFs de
 * activos) SOLO con sesión iniciada (endurecimiento nov 2026). Antes se abrían
 * directo en /storage/... sin login con solo tener el enlace; ahora
 * public/.htaccess bloquea /storage y Storage::url() apunta aquí.
 */
class ArchivoController extends Controller
{
    private const TIPOS_EN_LINEA = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'mp4', 'mov'];

    public function mostrar(string $ruta)
    {
        $ruta = str_replace('\\', '/', $ruta);
        // Sin rutas relativas ni archivos ocultos: solo lo que está dentro del disco public.
        if ($ruta === '' || str_contains($ruta, '..') || str_starts_with($ruta, '/') || preg_match('#(^|/)\.#', $ruta)) {
            abort(404);
        }

        $disco = Storage::disk('public');
        if (! $disco->exists($ruta)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $nombre = basename($ruta);
        $disposicion = in_array($ext, self::TIPOS_EN_LINEA, true) ? 'inline' : 'attachment';

        return $disco->response($ruta, $nombre, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=3600',
        ], $disposicion);
    }
}
