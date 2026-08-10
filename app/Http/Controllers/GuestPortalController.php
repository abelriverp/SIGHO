<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\InventarioItem;
use App\Models\PuntoQr;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Portal público de huésped: se llega vía QR impreso en la habitación/
 * unidad (/qr/{token}, sin login) y termina creando un Ticket normal, para
 * reutilizar TODO lo que ya existe (notificaciones, departamentos, motor de
 * SLA) en vez de construir una tubería paralela solo para huéspedes.
 *
 * Decisiones de alcance (confirmadas con el usuario):
 *   - El huésped puede reportar un problema O pedir amenidades del
 *     inventario marcado como disponible_huesped=true.
 *   - Un huésped nunca "existe" como User real: los tickets que genera
 *     quedan a nombre de una cuenta de sistema por propiedad (ver
 *     User::cuentaHuespedParaPropiedad).
 *   - No hay seguimiento de estado para el huésped en v1 (no hay login);
 *     solo se le confirma el folio al terminar. El staff da seguimiento
 *     por su flujo normal de tickets.
 */
class GuestPortalController extends Controller
{
    // Nombres de departamento a los que se intenta enrutar cada tipo de
    // solicitud. Si la propiedad no tiene un departamento con ese nombre
    // exacto (los administra libremente cada hotel), se usa el primero
    // activo como respaldo en vez de fallar.
    private const DEPARTAMENTO_PROBLEMA = 'Mantenimiento';
    private const DEPARTAMENTO_AMENIDAD = 'Ama de Llaves';

    // Tope por artículo por solicitud: evita que alguien pida cantidades
    // absurdas desde un formulario sin ningún control humano de por medio.
    private const MAX_CANTIDAD_POR_ARTICULO = 5;

    public function show(string $token)
    {
        $punto = $this->buscarPunto($token);

        $articulos = InventarioItem::deHotel($punto->hotel_code)
            ->where('activo', true)
            ->where('disponible_huesped', true)
            ->orderBy('nombre')
            ->get();

        return view('guest.show', compact('punto', 'articulos'));
    }

    public function store(Request $request, string $token)
    {
        $punto = $this->buscarPunto($token);

        $data = $request->validate([
            'tipo_solicitud' => ['required', Rule::in(['problema', 'amenidad'])],
            'nombre_huesped' => 'nullable|string|max:150',
            'descripcion'    => 'required_if:tipo_solicitud,problema|nullable|string|max:1000',
            'articulos'      => 'required_if:tipo_solicitud,amenidad|nullable|array',
            'articulos.*'    => 'nullable|integer|min:0|max:' . self::MAX_CANTIDAD_POR_ARTICULO,
        ], [
            'descripcion.required_if' => 'Cuéntanos brevemente cuál es el problema.',
            'articulos.required_if'   => 'Selecciona al menos un artículo.',
        ]);

        $nombreHuesped = $data['nombre_huesped'] ?? null;

        if ($data['tipo_solicitud'] === 'amenidad') {
            $articulos = $this->articulosSolicitados($punto->hotel_code, $data['articulos'] ?? []);

            if ($articulos->isEmpty()) {
                return back()->withInput()->withErrors([
                    'articulos' => 'Selecciona al menos un artículo con cantidad mayor a 0.',
                ]);
            }

            $descripcion = "Solicitud de amenidades desde la habitación/unidad {$punto->identificador}:\n"
                . $articulos->map(fn ($a) => "- {$a['cantidad']} x {$a['nombre']}")->implode("\n");

            $departamentoDestino = $this->departamentoDestino(self::DEPARTAMENTO_AMENIDAD);

            $camposExtra = [
                'categoria_label' => 'Solicitud de huésped (QR) — Amenidades',
                'origen'          => 'qr_huesped',
                'tipo_solicitud'  => 'amenidad',
                'huesped_nombre'  => $nombreHuesped,
                'articulos'       => $articulos->all(),
                'campos'          => [
                    ['label' => 'Habitación/Unidad', 'valor' => $punto->identificador],
                    ['label' => 'Huésped',            'valor' => $nombreHuesped],
                ],
            ];
        } else {
            $descripcion = $data['descripcion'];
            $departamentoDestino = $this->departamentoDestino(self::DEPARTAMENTO_PROBLEMA);

            $camposExtra = [
                'categoria_label' => 'Solicitud de huésped (QR) — Reporte de problema',
                'origen'          => 'qr_huesped',
                'tipo_solicitud'  => 'problema',
                'huesped_nombre'  => $nombreHuesped,
                'campos'          => [
                    ['label' => 'Habitación/Unidad', 'valor' => $punto->identificador],
                    ['label' => 'Huésped',            'valor' => $nombreHuesped],
                ],
            ];
        }

        $usuarioSistema = User::cuentaHuespedParaPropiedad($punto->hotel_code);

        $ticket = Ticket::create([
            'hotel_code'            => $punto->hotel_code,
            'user_id'               => $usuarioSistema->id,
            'area'                  => 'Habitación/Unidad ' . $punto->identificador,
            'departamento_destino'  => $departamentoDestino,
            'descripcion_ubicacion' => $punto->identificador,
            'descripcion'           => $descripcion,
            'prioridad'             => 'Media',
            'status'                => 'Abierto',
            'campos_extra'          => $camposExtra,
        ]);

        $destinatarios = User::where(function ($q) use ($ticket) {
            $q->where('role', 'admin')
              ->orWhere(function ($q2) use ($ticket) {
                  $q2->where('role', 'tecnico')
                     ->where('departamento', $ticket->departamento_destino);
              });
        })->get();

        Notification::send($destinatarios, new NuevoTicketNotification($ticket));

        return redirect()->route('guest.gracias', $token)->with('folio', $ticket->id);
    }

    public function gracias(string $token)
    {
        $punto = $this->buscarPunto($token);

        return view('guest.gracias', [
            'punto' => $punto,
            'folio' => session('folio'),
        ]);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Busca el punto QR sin depender de quién esté viendo la página: un
     * huésped nunca tiene sesión, así que HotelScope no filtraría nada de
     * todos modos (ver Scopes\HotelScope), pero si un miembro del staff
     * abre el link estando logueado (para probarlo antes de imprimirlo),
     * usar todasLasPropiedades() evita que su propio hotel_code interfiera.
     */
    private function buscarPunto(string $token): PuntoQr
    {
        return PuntoQr::todasLasPropiedades()->activos()->where('token', $token)->firstOrFail();
    }

    private function articulosSolicitados(string $hotelCode, array $cantidades): Collection
    {
        $ids = array_keys(array_filter($cantidades, fn ($c) => (int) $c > 0));

        if (empty($ids)) {
            return collect();
        }

        return InventarioItem::deHotel($hotelCode)
            ->where('disponible_huesped', true)
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (InventarioItem $item) => [
                'id'       => $item->id,
                'nombre'   => $item->nombre,
                'cantidad' => min((int) $cantidades[$item->id], self::MAX_CANTIDAD_POR_ARTICULO),
            ])
            ->values();
    }

    private function departamentoDestino(string $preferido): string
    {
        if (Departamento::activos()->where('nombre', $preferido)->exists()) {
            return $preferido;
        }

        return Departamento::activos()->orderBy('nombre')->value('nombre') ?? $preferido;
    }
}
