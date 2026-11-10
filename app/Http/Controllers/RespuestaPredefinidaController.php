<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\RespuestaPredefinida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Respuestas predefinidas para notas y soluciones (Fase 2). Solo supervisores. */
class RespuestaPredefinidaController extends Controller
{
    private function reglas(): array
    {
        return [
            'titulo'       => 'required|string|max:120',
            'texto'        => 'required|string|max:5000',
            'departamento' => ['nullable', 'string', Rule::in(Departamento::pluck('nombre')->all())],
            'activo'       => 'nullable|boolean',
        ];
    }

    public function index()
    {
        return view('tickets.respuestas.index', [
            'respuestas'    => RespuestaPredefinida::orderBy('departamento')->orderBy('titulo')->get(),
            'departamentos' => Departamento::activos()->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->reglas());
        $data['activo'] = true;
        RespuestaPredefinida::create($data);

        return back()->with('success', 'Respuesta agregada.');
    }

    public function update(Request $request, RespuestaPredefinida $respuesta)
    {
        $data = $request->validate($this->reglas());
        $data['activo'] = $request->boolean('activo');
        $respuesta->update($data);

        return back()->with('success', 'Respuesta actualizada.');
    }

    public function destroy(RespuestaPredefinida $respuesta)
    {
        $respuesta->delete();

        return back()->with('success', 'Respuesta eliminada.');
    }
}
