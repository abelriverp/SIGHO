<?php

namespace App\Http\Controllers;

use App\Models\Condomino;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResuelveHotelActivo;

class CondominoController extends Controller
{
    use ResuelveHotelActivo;

    private function queryBase()
    {
        return Condomino::where('hotel_code', $this->hotelActivo());
    }

    public function index(Request $request)
    {
        $query = $this->queryBase()->withCount('unidades');

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        $condominos = $query->orderBy('nombre')->paginate(25)->withQueryString();

        return view('condominios.condominos.index', [
            'condominos' => $condominos,
            'filtros'    => $request->only(['buscar']),
        ]);
    }

    public function create()
    {
        return view('condominios.condominos.form', ['condomino' => new Condomino()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'identificacion' => 'nullable|string|max:100',
            'fecha_alta'     => 'nullable|date',
            'notas'          => 'nullable|string',
        ]);

        $data['hotel_code'] = $this->hotelActivo();
        $data['user_id']    = auth()->id();
        $data['fecha_alta'] = $data['fecha_alta'] ?? now()->toDateString();

        $condomino = Condomino::create($data);

        return redirect()->route('condominios.condominos.index')
            ->with('success', "Condómino \"{$condomino->nombre}\" registrado correctamente.");
    }

    public function show($id)
    {
        $condomino = $this->queryBase()->with([
            'unidades',
            'estancias' => function ($q) {
                $q->with('unidad')->latest('fecha_llegada_prevista')->limit(20);
            },
        ])->findOrFail($id);

        return view('condominios.condominos.show', compact('condomino'));
    }

    public function edit($id)
    {
        $condomino = $this->queryBase()->findOrFail($id);
        return view('condominios.condominos.form', compact('condomino'));
    }

    public function update(Request $request, $id)
    {
        $condomino = $this->queryBase()->findOrFail($id);

        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'identificacion' => 'nullable|string|max:100',
            'fecha_alta'     => 'nullable|date',
            'notas'          => 'nullable|string',
        ]);

        $condomino->update($data);

        return redirect()->route('condominios.condominos.index')
            ->with('success', "Condómino \"{$condomino->nombre}\" actualizado correctamente.");
    }

    public function destroy($id)
    {
        $condomino = $this->queryBase()->findOrFail($id);
        $condomino->delete();

        return back()->with('success', 'Condómino eliminado.');
    }
}
