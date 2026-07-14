<?php

namespace App\Http\Controllers;

use App\Models\ActivoPlantilla;
use App\Models\ActivoItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class ActivoItemController extends Controller
{
    use ResuelveHotelActivo;

    /**
     * Vista principal: ítems agrupados por área.
     * Solo muestra áreas activas que tengan ítems O que estén disponibles para registro.
     */
    public function index()
    {
        $hotelCode = $this->hotelActivo();

        // Plantillas activas del hotel (para el selector de registro)
        $plantillas = ActivoPlantilla::where('hotel_code', $hotelCode)
            ->where('activa', true)
            ->orderBy('area_nombre')
            ->get();

        // Ítems: área → ubicación → listado de ítems en esa ubicación
        $itemsPorArea = ActivoItem::where('hotel_code', $hotelCode)
            ->orderBy('area_nombre')
            ->orderBy('ubicacion')
            ->orderBy('nombre_item')
            ->get()
            ->groupBy('area_nombre')
            ->map(fn($items) => $items->groupBy('ubicacion'));

        return view('inventory.activos', compact('plantillas', 'itemsPorArea', 'hotelCode'));
    }

    /**
     * Devuelve la plantilla (campos) de un área en JSON — para el formulario dinámico via AJAX.
     */
    public function plantillaJson($id)
    {
        $plantilla = ActivoPlantilla::where('hotel_code', $this->hotelActivo())
            ->where('activa', true)
            ->findOrFail($id);

        return response()->json([
            'id'     => $plantilla->id,
            'area'   => $plantilla->area_nombre,
            'campos' => $plantilla->campos,
        ]);
    }

    /**
     * Registrar un nuevo ítem.
     */
    public function store(Request $request)
    {
        $hotelCode = $this->hotelActivo();

        $request->validate([
            'plantilla_id' => 'required|exists:activo_plantillas,id',
            'ubicacion'    => 'required|string|max:150',
            'nombre_item'  => 'required|string|max:150',
            'notas'        => 'nullable|string',
        ]);

        $plantilla = ActivoPlantilla::where('hotel_code', $hotelCode)
            ->where('activa', true)
            ->findOrFail($request->plantilla_id);

        // Construir valores a partir de los campos de la plantilla
        $valores = [];
        foreach ($plantilla->campos as $campo) {
            $key = $campo['nombre'];
            $valor = $request->input('campo_' . $this->slugCampo($key));

            // Validar requeridos
            if (!empty($campo['requerido']) && empty($valor)) {
                return back()
                    ->withErrors(["campo_{$key}" => "El campo \"{$key}\" es requerido."])
                    ->withInput();
            }

            $valores[$key] = $valor ?? '';
        }

        ActivoItem::create([
            'hotel_code'   => $hotelCode,
            'plantilla_id' => $plantilla->id,
            'area_nombre'  => $plantilla->area_nombre,
            'ubicacion'    => $request->ubicacion,
            'nombre_item'  => $request->nombre_item,
            'valores'      => $valores,
            'notas'        => $request->notas,
        ]);

        return back()->with('success', '"' . $request->nombre_item . '" registrado en ' . $plantilla->area_nombre . '.');
    }

    /**
     * Actualizar un ítem existente.
     */
    public function update(Request $request, $id)
    {
        $hotelCode = $this->hotelActivo();
        $item = ActivoItem::where('hotel_code', $hotelCode)->findOrFail($id);

        $request->validate([
            'ubicacion'   => 'required|string|max:150',
            'nombre_item' => 'required|string|max:150',
            'notas'       => 'nullable|string',
        ]);

        $plantilla = $item->plantilla;
        $valores = [];
        foreach ($plantilla->campos as $campo) {
            $key = $campo['nombre'];
            $valor = $request->input('campo_' . $this->slugCampo($key));

            if (!empty($campo['requerido']) && empty($valor)) {
                return back()
                    ->withErrors(["campo_{$key}" => "El campo \"{$key}\" es requerido."])
                    ->withInput();
            }

            $valores[$key] = $valor ?? '';
        }

        $item->update([
            'ubicacion'   => $request->ubicacion,
            'nombre_item' => $request->nombre_item,
            'valores'     => $valores,
            'notas'       => $request->notas,
        ]);

        return back()->with('success', '"' . $item->nombre_item . '" actualizado correctamente.');
    }

    /**
     * Eliminar un ítem.
     */
    public function destroy($id)
    {
        $item = ActivoItem::where('hotel_code', $this->hotelActivo())->findOrFail($id);
        $nombre = $item->nombre_item;
        $item->delete();

        return back()->with('success', '"' . $nombre . '" eliminado.');
    }

    /**
     * Convierte el nombre de un campo a un slug seguro para usarlo como name en HTML.
     * Ej: "Número de Serie" → "numero_de_serie"
     */
    public static function slugCampo(string $nombre): string
    {
        $nombre = strtolower($nombre);
        $nombre = str_replace(
            ['á','é','í','ó','ú','ñ','ü',' ','-'],
            ['a','e','i','o','u','n','u','_','_'],
            $nombre
        );
        return preg_replace('/[^a-z0-9_]/', '', $nombre);
    }
}
