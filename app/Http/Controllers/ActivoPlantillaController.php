<?php

namespace App\Http\Controllers;

use App\Models\ActivoPlantilla;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class ActivoPlantillaController extends Controller
{
    use ResuelveHotelActivo;

    /** Lista de plantillas/áreas del hotel activo */
    public function index()
    {
        $plantillas = ActivoPlantilla::where('hotel_code', $this->hotelActivo())
            ->orderBy('area_nombre')
            ->get();

        return view('admin.activo-plantillas.index', compact('plantillas'));
    }

    /** Formulario para crear una nueva plantilla/área */
    public function create()
    {
        $tiposCampo = ActivoPlantilla::tiposCampo();
        return view('admin.activo-plantillas.form', compact('tiposCampo'));
    }

    /** Guardar nueva plantilla */
    public function store(Request $request)
    {
        $hotelCode = $this->hotelActivo();

        $request->validate([
            'area_nombre'   => 'required|string|max:100',
            'descripcion'   => 'nullable|string|max:255',
            'campos'        => 'nullable|array',
            'campos.*.nombre'   => 'required|string|max:100',
            'campos.*.tipo'     => 'required|in:text,number,select,date,email',
            'campos.*.requerido' => 'nullable|boolean',
            'campos.*.opciones' => 'nullable|string',
        ]);

        // Verificar unicidad manual (hotel_code + area_nombre)
        $existe = ActivoPlantilla::where('hotel_code', $hotelCode)
            ->where('area_nombre', $request->area_nombre)
            ->exists();

        if ($existe) {
            return back()->withErrors(['area_nombre' => 'Ya existe un área con ese nombre en este hotel.'])->withInput();
        }

        // Procesar campos
        $campos = [];
        foreach ($request->campos ?? [] as $campo) {
            $entry = [
                'nombre'    => trim($campo['nombre']),
                'tipo'      => $campo['tipo'],
                'requerido' => isset($campo['requerido']) && $campo['requerido'],
            ];
            // Para tipo "select", guardar opciones como array
            if ($campo['tipo'] === 'select' && !empty($campo['opciones'])) {
                $entry['opciones'] = array_filter(array_map('trim', explode(',', $campo['opciones'])));
            }
            $campos[] = $entry;
        }

        ActivoPlantilla::create([
            'hotel_code'  => $hotelCode,
            'area_nombre' => $request->area_nombre,
            'descripcion' => $request->descripcion,
            'campos'      => $campos,
            'activa'      => true,
        ]);

        return redirect()->route('admin.activo-plantillas.index')
            ->with('success', 'Área "' . $request->area_nombre . '" creada correctamente.');
    }

    /** Formulario de edición */
    public function edit($id)
    {
        $plantilla = ActivoPlantilla::where('hotel_code', $this->hotelActivo())->findOrFail($id);
        $tiposCampo = ActivoPlantilla::tiposCampo();
        return view('admin.activo-plantillas.form', compact('plantilla', 'tiposCampo'));
    }

    /** Actualizar plantilla */
    public function update(Request $request, $id)
    {
        $hotelCode = $this->hotelActivo();
        $plantilla = ActivoPlantilla::where('hotel_code', $hotelCode)->findOrFail($id);

        $request->validate([
            'area_nombre'   => 'required|string|max:100',
            'descripcion'   => 'nullable|string|max:255',
            'campos'        => 'nullable|array',
            'campos.*.nombre'    => 'required|string|max:100',
            'campos.*.tipo'      => 'required|in:text,number,select,date,email',
            'campos.*.requerido' => 'nullable|boolean',
            'campos.*.opciones'  => 'nullable|string',
        ]);

        $campos = [];
        foreach ($request->campos ?? [] as $campo) {
            $entry = [
                'nombre'    => trim($campo['nombre']),
                'tipo'      => $campo['tipo'],
                'requerido' => isset($campo['requerido']) && $campo['requerido'],
            ];
            if ($campo['tipo'] === 'select' && !empty($campo['opciones'])) {
                $entry['opciones'] = array_filter(array_map('trim', explode(',', $campo['opciones'])));
            }
            $campos[] = $entry;
        }

        $plantilla->update([
            'area_nombre' => $request->area_nombre,
            'descripcion' => $request->descripcion,
            'campos'      => $campos,
            'activa'      => $request->boolean('activa', true),
        ]);

        return redirect()->route('admin.activo-plantillas.index')
            ->with('success', 'Área "' . $plantilla->area_nombre . '" actualizada.');
    }

    /** Eliminar plantilla (solo si no tiene ítems) */
    public function destroy($id)
    {
        $plantilla = ActivoPlantilla::where('hotel_code', $this->hotelActivo())->findOrFail($id);

        if ($plantilla->items()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: el área tiene ' . $plantilla->items()->count() . ' ítems registrados.');
        }

        $nombre = $plantilla->area_nombre;
        $plantilla->delete();

        return back()->with('success', 'Área "' . $nombre . '" eliminada.');
    }
}
