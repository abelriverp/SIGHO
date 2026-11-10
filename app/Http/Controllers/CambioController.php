<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Cambio;
use App\Models\Problema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestion de cambios (Fase 5): plan, ventana, rollback y aprobacion.
 * Normal: gerente/admin aprueba antes de ejecutar (no el mismo solicitante,
 * salvo admin). Estandar: preaprobado al enviarse. Emergencia: se puede
 * ejecutar desde borrador y se revisa despues.
 */
class CambioController extends Controller
{
    private function exigeEditor(): void
    {
        $u = auth()->user();
        if (! ($u->puedeResponderTickets() || $u->puedeVerTodos())) {
            abort(403, 'Solo técnicos, gerentes y administradores gestionan cambios.');
        }
    }

    public function index(Request $request)
    {
        $this->exigeEditor();
        $vista = $request->query('vista', 'activos');

        $cambios = Cambio::with(['solicitante:id,name', 'aprobador:id,name'])->withCount('activos')
            ->when($vista === 'activos', fn ($q) => $q->whereNotIn('estado', ['completado', 'cancelado', 'rechazado']))
            ->when($vista === 'pendientes', fn ($q) => $q->where('estado', 'pendiente_aprobacion'))
            ->when($vista === 'historial', fn ($q) => $q->whereIn('estado', ['completado', 'fallido', 'cancelado', 'rechazado']))
            ->orderByRaw('ventana_inicio IS NULL')->orderBy('ventana_inicio')->latest('id')->get();

        $pendientes = Cambio::where('estado', 'pendiente_aprobacion')->count();

        return view('cambios.index', ['cambios' => $cambios, 'vista' => $vista, 'pendientes' => $pendientes, 'estados' => Cambio::ESTADOS]);
    }

    private function datosForm(): array
    {
        return [
            'activos'   => Asset::vigentes()->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo']),
            'problemas' => Problema::whereNotIn('estado', ['cerrado'])->orderByDesc('id')->get(['id', 'titulo']),
            'tipos'     => Cambio::TIPOS,
        ];
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo'              => 'required|string|max:200',
            'descripcion'         => 'required|string|max:5000',
            'tipo'                => ['required', Rule::in(array_keys(Cambio::TIPOS))],
            'riesgo'              => 'required|in:Baja,Media,Alta',
            'impacto'             => 'nullable|string|max:3000',
            'plan_implementacion' => 'required|string|max:10000',
            'plan_rollback'       => 'required|string|max:10000',
            'ventana_inicio'      => 'nullable|date',
            'ventana_fin'         => 'nullable|date|after_or_equal:ventana_inicio',
            'problema_id'         => 'nullable|integer',
            'activos'             => 'nullable|array',
            'activos.*'           => 'integer',
        ], [
            'plan_rollback.required' => 'Todo cambio necesita un plan para regresar al estado anterior si algo sale mal.',
        ]);
    }

    private function guardar(Request $request, Cambio $cambio): Cambio
    {
        $data = $this->validar($request);
        $ids = Asset::whereIn('id', $data['activos'] ?? [])->pluck('id');
        $data['problema_id'] = ! empty($data['problema_id']) && Problema::whereKey($data['problema_id'])->exists() ? $data['problema_id'] : null;
        unset($data['activos']);
        $cambio->fill($data)->save();
        $cambio->activos()->sync($ids);

        return $cambio;
    }

    public function create(Request $request)
    {
        $this->exigeEditor();
        $cambio = new Cambio(['tipo' => 'normal', 'riesgo' => 'Media', 'problema_id' => $request->query('problema_id')]);
        return view('cambios.form', array_merge(['cambio' => $cambio], $this->datosForm()));
    }

    public function store(Request $request)
    {
        $this->exigeEditor();
        $cambio = new Cambio(['solicitado_por' => auth()->id(), 'estado' => 'borrador']);
        $this->guardar($request, $cambio);
        AuditLog::registrar('cambio.creado', "Registró el cambio \"{$cambio->titulo}\".", $cambio);

        return redirect()->route('cambios.show', $cambio)->with('success', 'Cambio guardado como borrador. Envíalo a aprobación cuando esté listo.');
    }

    public function show(Cambio $cambio)
    {
        $this->exigeEditor();
        $cambio->load(['activos.ubicacion', 'problema', 'solicitante', 'aprobador']);

        return view('cambios.show', ['cambio' => $cambio, 'estados' => Cambio::ESTADOS, 'tipos' => Cambio::TIPOS]);
    }

    public function edit(Cambio $cambio)
    {
        $this->exigeEditor();
        if (! $cambio->editable()) {
            return redirect()->route('cambios.show', $cambio)->with('error', 'Solo se edita un cambio en borrador, rechazado o fallido.');
        }
        $cambio->load('activos:id');
        return view('cambios.form', array_merge(['cambio' => $cambio], $this->datosForm()));
    }

    public function update(Request $request, Cambio $cambio)
    {
        $this->exigeEditor();
        if (! $cambio->editable()) {
            return redirect()->route('cambios.show', $cambio)->with('error', 'Solo se edita un cambio en borrador, rechazado o fallido.');
        }
        $this->guardar($request, $cambio);

        return redirect()->route('cambios.show', $cambio)->with('success', 'Cambio actualizado.');
    }

    /** Mueve el cambio por su flujo validando quién puede hacer cada paso. */
    public function transicion(Request $request, Cambio $cambio)
    {
        $this->exigeEditor();
        $u = auth()->user();
        $data = $request->validate([
            'estado'     => ['required', Rule::in(array_keys(Cambio::ESTADOS))],
            'comentario' => 'nullable|string|max:3000',
        ]);
        $destino = $data['estado'];
        $comentario = $data['comentario'] ?? null;

        // Emergencia: se ejecuta desde borrador sin esperar aprobación.
        $permitido = $cambio->puedePasarA($destino)
            || ($cambio->tipo === 'emergencia' && $cambio->estado === 'borrador' && $destino === 'en_ejecucion');
        if (! $permitido) {
            return back()->with('error', 'Ese paso no es válido desde "' . Cambio::ESTADOS[$cambio->estado] . '".');
        }

        $extra = [];
        if (in_array($destino, ['aprobado', 'rechazado'], true)) {
            if (! $u->puedeVerTodos()) {
                abort(403, 'Solo un Gerente o Administrador aprueba o rechaza cambios.');
            }
            if ($cambio->solicitado_por === $u->id && ! $u->isAdmin()) {
                return back()->with('error', 'No puedes aprobar tu propio cambio; pídeselo a otro gerente o a un administrador.');
            }
            if ($destino === 'rechazado' && blank($comentario)) {
                return back()->with('error', 'Explica por qué se rechaza el cambio.');
            }
            $extra = ['aprobado_por' => $u->id, 'aprobado_at' => now(), 'comentario_aprobacion' => $comentario];
        }

        // Estándar: preaprobado, pasa directo a aprobado al enviarse.
        if ($destino === 'pendiente_aprobacion' && $cambio->tipo === 'estandar') {
            $destino = 'aprobado';
            $extra = ['aprobado_at' => now(), 'comentario_aprobacion' => 'Cambio estándar preaprobado.'];
        }

        if (in_array($destino, ['completado', 'fallido'], true)) {
            if (blank($comentario)) {
                return back()->with('error', 'Describe el resultado del cambio.');
            }
            $extra = ['resultado' => $comentario, 'completado_at' => now()];
        }

        if ($destino === 'borrador') {
            $extra = ['aprobado_por' => null, 'aprobado_at' => null];
        }

        $anterior = $cambio->estado;
        $cambio->update(['estado' => $destino] + $extra);
        AuditLog::registrar('cambio.' . $destino, "Cambio \"{$cambio->titulo}\": {$anterior} → {$destino}.", $cambio, ['estado' => $anterior], ['estado' => $destino, 'comentario' => $comentario]);

        return back()->with('success', 'Cambio ahora: ' . Cambio::ESTADOS[$destino] . '.');
    }
}
