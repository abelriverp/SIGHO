<?php

namespace App\Http\Controllers;

use App\Models\Condomino;
use App\Models\Unidad;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class UnidadController extends Controller
{
    use ResuelveHotelActivo;

    private function queryBase()
    {
        return Unidad::where('hotel_code', $this->hotelActivo());
    }

    public function index(Request $request)
    {
        $query = $this->queryBase()->with('condomino');

        if ($request->filled('estatus')) $query->where('estatus', $request->estatus);
        if ($request->filled('torre'))   $query->where('torre', $request->torre);
        if ($request->filled('buscar'))  $query->where('identificador', 'like', '%' . $request->buscar . '%');

        $unidades = $query->orderBy('torre')->orderBy('identificador')->paginate(25)->withQueryString();

        $torres = $this->queryBase()->whereNotNull('torre')->distinct()->pluck('torre');

        return view('condominios.unidades.index', [
            'unidades' => $unidades,
            'torres'   => $torres,
            'estatus'  => Unidad::ESTATUS,
            'filtros'  => $request->only(['estatus', 'torre', 'buscar']),
        ]);
    }

    public function create()
    {
        $condominos = Condomino::where('hotel_code', $this->hotelActivo())->orderBy('nombre')->get();
        return view('condominios.unidades.form', [
            'unidad'     => new Unidad(),
            'condominos' => $condominos,
            'estatus'    => Unidad::ESTATUS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identificador' => 'required|string|max:20',
            'torre'         => 'nullable|string|max:50',
            'piso'          => 'nullable|string|max:10',
            'tipo_unidad'   => 'nullable|string|max:50',
            'condomino_id'  => 'nullable|exists:condominos,id',
            'estatus'       => 'required|in:' . implode(',', Unidad::ESTATUS),
            'notas'         => 'nullable|string',
        ]);

        $data['hotel_code'] = $this->hotelActivo();

        $unidad = Unidad::create($data);

        return redirect()->route('condominios.unidades.index')
            ->with('success', "Unidad \"{$unidad->identificador}\" registrada correctamente.");
    }

    public function edit($id)
    {
        $unidad = $this->queryBase()->findOrFail($id);
        $condominos = Condomino::where('hotel_code', $this->hotelActivo())->orderBy('nombre')->get();

        return view('condominios.unidades.form', [
            'unidad'     => $unidad,
            'condominos' => $condominos,
            'estatus'    => Unidad::ESTATUS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $unidad = $this->queryBase()->findOrFail($id);

        $data = $request->validate([
            'identificador' => 'required|string|max:20',
            'torre'         => 'nullable|string|max:50',
            'piso'          => 'nullable|string|max:10',
            'tipo_unidad'   => 'nullable|string|max:50',
            'condomino_id'  => 'nullable|exists:condominos,id',
            'estatus'       => 'required|in:' . implode(',', Unidad::ESTATUS),
            'notas'         => 'nullable|string',
        ]);

        $unidad->update($data);

        return redirect()->route('condominios.unidades.index')
            ->with('success', "Unidad \"{$unidad->identificador}\" actualizada correctamente.");
    }

    public function destroy($id)
    {
        $unidad = $this->queryBase()->findOrFail($id);
        $unidad->delete();

        return back()->with('success', 'Unidad eliminada.');
    }
}
