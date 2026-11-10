<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Departamento;
use App\Models\TicketRecurrente;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Mantenimiento preventivo (Fase 2): plantillas que generan un ticket solo
 * cada cierto tiempo (comando tickets:generar-recurrentes). Solo supervisores.
 */
class TicketRecurrenteController extends Controller
{
    private function reglas(): array
    {
        return [
            'titulo'               => 'required|string|max:200',
            'descripcion'          => 'required|string|max:5000',
            'departamento_destino' => ['required', 'string', Rule::in(Departamento::activos()->pluck('nombre')->all())],
            'prioridad'            => 'required|in:Baja,Media,Alta',
            'frecuencia'           => ['required', Rule::in(array_keys(TicketRecurrente::FRECUENCIAS))],
            'proxima_fecha'        => 'required|date',
            'asset_id'             => 'nullable|integer|exists:assets,id',
            'ubicacion_id'         => 'nullable|integer|exists:ubicaciones,id',
            'activo'               => 'nullable|boolean',
        ];
    }

    private function validar(Request $request): array
    {
        $data = $request->validate($this->reglas());
        $errores = [];
        if (! empty($data['asset_id']) && ! Asset::whereKey($data['asset_id'])->exists()) {
            $errores['asset_id'] = 'Equipo no válido.';
        }
        if (! empty($data['ubicacion_id']) && ! Ubicacion::whereKey($data['ubicacion_id'])->exists()) {
            $errores['ubicacion_id'] = 'Ubicación no válida.';
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }

    private function datosForm(): array
    {
        return [
            'departamentos' => Departamento::activos()->orderBy('nombre')->pluck('nombre'),
            'frecuencias'   => TicketRecurrente::FRECUENCIAS,
            'ubicaciones'   => Ubicacion::where('activa', true)->orderBy('tipo')->orderBy('codigo')->get(['id', 'tipo', 'codigo', 'nombre']),
            'activos'       => Asset::vigentes()->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo']),
        ];
    }

    public function index()
    {
        $recurrentes = TicketRecurrente::with(['asset:id,nomenclatura', 'ubicacion', 'ultimoTicket:id,status'])
            ->orderByDesc('activo')->orderBy('proxima_fecha')->get();

        return view('tickets.recurrentes.index', ['recurrentes' => $recurrentes, 'frecuencias' => TicketRecurrente::FRECUENCIAS]);
    }

    public function create()
    {
        return view('tickets.recurrentes.form', array_merge([
            'recurrente' => new TicketRecurrente(['prioridad' => 'Media', 'frecuencia' => 'mensual', 'proxima_fecha' => Carbon::tomorrow(), 'activo' => true]),
        ], $this->datosForm()));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['creado_por'] = auth()->id();
        TicketRecurrente::create($data);

        return redirect()->route('tickets.recurrentes.index')->with('success', 'Mantenimiento programado.');
    }

    public function edit(TicketRecurrente $recurrente)
    {
        return view('tickets.recurrentes.form', array_merge(['recurrente' => $recurrente], $this->datosForm()));
    }

    public function update(Request $request, TicketRecurrente $recurrente)
    {
        $recurrente->update($this->validar($request));

        return redirect()->route('tickets.recurrentes.index')->with('success', 'Mantenimiento actualizado.');
    }

    public function destroy(TicketRecurrente $recurrente)
    {
        $recurrente->delete();

        return redirect()->route('tickets.recurrentes.index')->with('success', 'Mantenimiento eliminado. Los tickets ya generados se conservan.');
    }
}
