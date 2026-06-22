<?php

namespace App\Http\Controllers;

use App\Models\HabitacionActivo;
use Illuminate\Http\Request;

class HabitacionActivoController extends Controller
{
    // Vista principal: Lista por pisos (DINÁMICA)
    public function index()
    {
        // Traemos todas las habitaciones ordenadas y las agrupamos por piso en memoria
        // Esto reduce las consultas a la base de datos a SOLO UNA, sin importar cuántos pisos existan.
        $habitacionesPorPiso = HabitacionActivo::orderBy('numero_habitacion')->get()->groupBy('piso');

        // Nota: En tu vista Blade ahora puedes iterar sobre $habitacionesPorPiso.
        // O si tu Blade actual depende estrictamente de $piso1, $piso2... las extraemos así para no romper tu vista:
        $piso1 = $habitacionesPorPiso->get(1, collect());
        $piso2 = $habitacionesPorPiso->get(2, collect());
        $piso3 = $habitacionesPorPiso->get(3, collect());
        $piso4 = $habitacionesPorPiso->get(4, collect());

        return view('inventory.habitaciones', compact('piso1', 'piso2', 'piso3', 'piso4', 'habitacionesPorPiso'));
    }

    // Guardar una nueva habitación
    public function store(Request $request)
    {
        $data = $request->validate([
            // 🛠️ ATENCIÓN: Cambia 'habitaciones_activos' por el nombre real de tu tabla si es distinto
            'numero_habitacion' => 'required|unique:habitaciones_activos,numero_habitacion',
            'piso' => 'required|integer',
            'ap_modelo' => 'nullable|string',
            'ap_serie' => 'nullable|string',
            'ap_mac' => 'nullable|string',
            'tv_modelo' => 'nullable|string',
            'tv_serie' => 'nullable|string',
            'control_serie' => 'nullable|string',
            'caja_fuerte_serie' => 'nullable|string',
        ]);

        HabitacionActivo::create($data);
        return back()->with('success', 'Habitación registrada correctamente.');
    }

    // Actualizar datos técnicos de una habitación
    public function update(Request $request, $id)
    {
        $hab = HabitacionActivo::findOrFail($id);

        // 🔒 Validamos los datos permitidos para la actualización en lugar de usar $request->all()
        $data = $request->validate([
            'ap_modelo' => 'nullable|string',
            'ap_serie' => 'nullable|string',
            'ap_mac' => 'nullable|string',
            'tv_modelo' => 'nullable|string',
            'tv_serie' => 'nullable|string',
            'control_serie' => 'nullable|string',
            'caja_fuerte_serie' => 'nullable|string',
            'notas_tecnicas' => 'nullable|string',
            // Si permites cambiar de piso o número, añádelos aquí con sus reglas
        ]);

        $hab->update($data);
        
        return back()->with('success', 'Inventario de la habitación #' . $hab->numero_habitacion . ' actualizado.');
    }
}