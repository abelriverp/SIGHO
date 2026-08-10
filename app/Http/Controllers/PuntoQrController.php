<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\PuntoQr;
use Illuminate\Http\Request;

/**
 * Administra los puntos QR (habitaciones/unidades) del portal de huésped.
 * Mismo patrón que TicketCategoriaController/SlaPoliticaController: admin
 * y gerente (middleware 'supervisor'), scope por propiedad activa.
 */
class PuntoQrController extends Controller
{
    use ResuelveHotelActivo;

    public function index()
    {
        $puntos = PuntoQr::deHotel($this->hotelActivo())->orderBy('identificador')->get();

        return view('puntos-qr.index', compact('puntos'));
    }

    public function create()
    {
        return view('puntos-qr.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identificador' => 'required|string|max:50',
        ], [
            'identificador.required' => 'Indica el número de habitación o el nombre de la unidad.',
        ]);

        $data['hotel_code'] = $this->hotelActivo();

        // El token se genera solo en PuntoQr::booted() (creating).
        $punto = PuntoQr::create($data);

        AuditLog::registrar(
            'punto_qr.creado',
            "Generó un código QR para \"{$punto->identificador}\".",
            $punto,
            null,
            $punto->only(['identificador'])
        );

        return redirect()->route('puntos-qr.index')
            ->with('success', "QR generado para \"{$punto->identificador}\". Imprímelo desde la lista.");
    }

    /**
     * Pantalla grande y lista para imprimir/descargar/copiar — el QR chico
     * de la lista es solo vista previa, esto es lo que de verdad se le
     * entrega al huésped/condómino (impreso en la habitación, o el enlace
     * por WhatsApp/correo si no se va a imprimir).
     */
    public function imprimir(PuntoQr $puntoQr)
    {
        $this->autorizarPropiedad($puntoQr);

        return view('puntos-qr.imprimir', compact('puntoQr'));
    }

    public function toggleActivo(PuntoQr $puntoQr)
    {
        $this->autorizarPropiedad($puntoQr);

        $puntoQr->update(['activo' => !$puntoQr->activo]);

        AuditLog::registrar(
            $puntoQr->activo ? 'punto_qr.activado' : 'punto_qr.desactivado',
            ($puntoQr->activo ? 'Activó' : 'Desactivó') . " el QR de \"{$puntoQr->identificador}\".",
            $puntoQr,
            null,
            $puntoQr->only(['activo'])
        );

        return back()->with('success', $puntoQr->activo ? 'QR activado.' : 'QR desactivado: el enlace deja de funcionar hasta que lo reactives.');
    }

    /**
     * Invalida el QR actual (ej. se perdió o se filtró la habitación
     * equivocada) y genera uno nuevo para el mismo punto, sin tener que
     * borrar su historial.
     */
    public function regenerarToken(PuntoQr $puntoQr)
    {
        $this->autorizarPropiedad($puntoQr);

        $puntoQr->update(['token' => PuntoQr::generarTokenUnico()]);

        AuditLog::registrar(
            'punto_qr.token_regenerado',
            "Regeneró el token del QR de \"{$puntoQr->identificador}\" — el código impreso anterior queda inválido.",
            $puntoQr,
            null,
            null
        );

        return back()->with('success', 'Token regenerado. El QR anterior ya NO funciona — imprime el nuevo desde aquí.');
    }

    public function destroy(PuntoQr $puntoQr)
    {
        $this->autorizarPropiedad($puntoQr);

        $etiqueta = $puntoQr->identificador;

        AuditLog::registrar(
            'punto_qr.eliminado',
            "Eliminó el QR de \"{$etiqueta}\".",
            $puntoQr,
            $puntoQr->only(['identificador']),
            null
        );

        $puntoQr->delete();

        return redirect()->route('puntos-qr.index')->with('success', "QR de \"{$etiqueta}\" eliminado.");
    }

    private function autorizarPropiedad(PuntoQr $puntoQr): void
    {
        abort_unless($puntoQr->hotel_code === $this->hotelActivo(), 403, 'Este punto QR pertenece a otra propiedad.');
    }
}
