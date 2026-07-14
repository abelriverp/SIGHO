<?php

namespace App\Http\Controllers;

use App\Models\HabitacionActivo;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class HabitacionActivoController extends Controller
{
    use ResuelveHotelActivo;

    /**
     * Vista principal: Lista habitaciones del hotel activo, agrupadas por piso (dinámico).
     * Un solo query, sin límite de pisos.
     */
    public function index()
    {
        $hotelCode = $this->hotelActivo();

        $habitacionesPorPiso = HabitacionActivo::where('hotel_code', $hotelCode)
            ->orderBy('numero_habitacion')
            ->get()
            ->groupBy('piso')
            ->sortKeys(); // Pisos en orden ascendente (1, 2, 3... N)

        return view('inventory.habitaciones', compact('habitacionesPorPiso', 'hotelCode'));
    }

    /**
     * Reglas de validación compartidas para campos técnicos.
     */
    private function reglasCampos(): array
    {
        return [
            'ap_modelo'             => 'nullable|string|max:255',
            'ap_serie'              => 'nullable|string|max:255',
            'ap_mac'                => 'nullable|string|max:255',
            'ap_ip'                 => 'nullable|string|max:255',
            'tv_marca'              => 'nullable|string|max:255',
            'tv_modelo'             => 'nullable|string|max:255',
            'tv_serie'              => 'nullable|string|max:255',
            'tv_pulgadas'           => 'nullable|string|max:10',
            'control_remoto_estado' => 'nullable|in:Funciona,Dañado,Extraviado',
            'control_serie'         => 'nullable|string|max:255',
            'caja_fuerte_modelo'    => 'nullable|string|max:255',
            'caja_fuerte_serie'     => 'nullable|string|max:255',
            'caja_fuerte_bateria'   => 'nullable|string|max:255',
            'notas_tecnicas'        => 'nullable|string',
        ];
    }

    /**
     * Guardar una nueva habitación ligada al hotel activo.
     * La unicidad de numero_habitacion se valida dentro del mismo hotel.
     */
    public function store(Request $request)
    {
        $hotelCode = $this->hotelActivo();

        $data = $request->validate(array_merge([
            'numero_habitacion' => [
                'required', 'string', 'max:50',
                \Illuminate\Validation\Rule::unique('habitacion_activos', 'numero_habitacion')
                    ->where('hotel_code', $hotelCode),
            ],
            'piso' => 'required|integer|min:1',
        ], $this->reglasCampos()));

        $data['hotel_code'] = $hotelCode;

        HabitacionActivo::create($data);

        return back()->with('success', 'Habitación #' . $data['numero_habitacion'] . ' registrada correctamente.');
    }

    /**
     * Actualizar datos técnicos de una habitación.
     * Verifica que la habitación pertenezca al hotel activo antes de editar.
     */
    public function update(Request $request, $id)
    {
        $hotelCode = $this->hotelActivo();

        $hab = HabitacionActivo::where('id', $id)
            ->where('hotel_code', $hotelCode)
            ->firstOrFail();

        $data = $request->validate($this->reglasCampos());

        $hab->update($data);

        return back()->with('success', 'Inventario de la habitación #' . $hab->numero_habitacion . ' actualizado.');
    }
}
