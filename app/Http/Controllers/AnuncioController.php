<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\AuditLog;
use App\Models\Modulo;
use App\Notifications\AnuncioNuevaFuncionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Anuncios de "nueva función/cambio": un admin redacta un mensaje (con
 * mini-tutorial si aplica) y se manda por correo solo a quienes tienen
 * acceso al módulo afectado (ver Anuncio::destinatarios()). Queda guardado
 * como historial para la pantalla "Novedades" (novedades(), sin prefijo
 * admin — cualquier usuario autenticado la puede consultar).
 */
class AnuncioController extends Controller
{
    /**
     * Pantalla de administración: formulario para redactar uno nuevo +
     * historial completo de todos los que se han enviado.
     */
    public function index()
    {
        $modulos  = Modulo::where('activo', true)->orderBy('orden')->get();
        $anuncios = Anuncio::with(['modulo', 'autor'])->latest()->get();

        return view('admin.anuncios.index', compact('modulos', 'anuncios'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'modulo_id' => ['nullable', Rule::exists('modulos', 'id')->where('activo', true)],
            'titulo'    => 'required|string|max:255',
            'mensaje'   => 'required|string|max:5000',
        ]);

        $anuncio = Anuncio::create([
            'modulo_id'   => $data['modulo_id'] ?: null,
            'titulo'      => $data['titulo'],
            'mensaje'     => $data['mensaje'],
            'enviado_por' => auth()->id(),
        ]);

        $destinatarios = $anuncio->destinatarios();
        $anuncio->update(['total_destinatarios' => $destinatarios->count()]);

        $mensaje = 'Anuncio guardado.';

        try {
            Notification::send($destinatarios, new AnuncioNuevaFuncionNotification($anuncio));
            $mensaje .= " Se notificó por correo a {$destinatarios->count()} usuario(s).";
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de anuncio de nueva función', [
                'anuncio_id' => $anuncio->id,
                'error'      => $e->getMessage(),
            ]);
            $mensaje .= ' No se pudo enviar el correo; queda visible en Novedades de todas formas.';
        }

        AuditLog::registrar(
            'anuncio.enviado',
            "Envió el anuncio \"{$anuncio->titulo}\" a {$destinatarios->count()} usuario(s)" .
                ($anuncio->modulo ? " del módulo \"{$anuncio->modulo->nombre}\"." : ' (todos los usuarios).'),
            $anuncio,
            null,
            $anuncio->fresh()->only(['modulo_id', 'titulo', 'total_destinatarios'])
        );

        return redirect()->route('admin.anuncios.index')->with('success', $mensaje);
    }

    /**
     * "Novedades": lo que le corresponde ver a CUALQUIER usuario
     * autenticado, filtrado a los anuncios generales + los de los módulos
     * a los que tiene acceso (mismo criterio que decide quién recibió el
     * correo, ver Anuncio::estaDirigidoA()).
     */
    public function novedades()
    {
        $user = auth()->user();

        $anuncios = Anuncio::with(['modulo', 'autor'])
            ->latest()
            ->get()
            ->filter(fn (Anuncio $anuncio) => $anuncio->estaDirigidoA($user))
            ->values();

        return view('novedades.index', compact('anuncios'));
    }
}
