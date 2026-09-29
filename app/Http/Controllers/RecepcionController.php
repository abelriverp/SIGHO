<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\EmailValidacion;
use App\Models\ExtensionDeteccion;
use App\Models\PerfilCorreoValidacion;

/**
 * Pantalla de inicio del módulo "Herramientas de Recepción": accesos a los
 * tres análisis (Validador de Emails, Detector de Extensiones, Correos de
 * Llegada) y un vistazo a lo último procesado. Equivalente web del
 * MenuPrincipal de la app de escritorio original.
 */
class RecepcionController extends Controller
{
    use ResuelveHotelActivo;

    public function index()
    {
        $hotelCode = $this->hotelActivo();

        $ultimasValidaciones = EmailValidacion::where('hotel_code', $hotelCode)
            ->latest()
            ->limit(5)
            ->get();

        $ultimasDetecciones = ExtensionDeteccion::where('hotel_code', $hotelCode)
            ->latest()
            ->limit(5)
            ->get();

        $ultimosPerfiles = PerfilCorreoValidacion::where('hotel_code', $hotelCode)
            ->latest()
            ->limit(5)
            ->get();

        return view('recepcion.index', compact('ultimasValidaciones', 'ultimasDetecciones', 'ultimosPerfiles'));
    }
}
