<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Problema;
use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Gestion de problemas (Fase 5): agrupa tickets con la misma causa y
 * documenta solucion temporal, causa raiz y solucion definitiva.
 */
class ProblemaController extends Controller
{
    private const UMBRAL_CANDIDATO = 3;   // tickets del mismo equipo/lugar en 30 dias

    private function exigeEditor(): void
    {
        $u = auth()->user();
        if (! ($u->puedeResponderTickets() || $u->puedeVerTodos())) {
            abort(403, 'Solo técnicos, gerentes y administradores gestionan problemas.');
        }
    }

    public function index(Request $request)
    {
        $this->exigeEditor();
        $estado = $request->query('estado', 'abiertos');

        $problemas = Problema::with(['asset:id,nomenclatura', 'ubicacion', 'responsable:id,name'])->withCount('tickets')
            ->when($estado === 'abiertos', fn ($q) => $q->whereNotIn('estado', ['resuelto', 'cerrado']))
            ->when(array_key_exists($estado, Problema::ESTADOS), fn ($q) => $q->where('estado', $estado))
            ->latest()->get();

        return view('problemas.index', [
            'problemas'  => $problemas,
            'estado'     => $estado,
            'candidatos' => $this->candidatos(),
        ]);
    }

    /** Equipos y lugares con tickets repetidos en 30 días sin problema abierto. */
    private function candidatos(): array
    {
        $desde = now()->subDays(30);
        $resultado = [];

        foreach (['asset_id' => Asset::class, 'ubicacion_id' => Ubicacion::class] as $col => $modelo) {
            $conteo = Ticket::where('created_at', '>=', $desde)->whereNotNull($col)
                ->selectRaw("{$col} as ref, count(*) as total")->groupBy($col)
                ->having('total', '>=', self::UMBRAL_CANDIDATO)->pluck('total', 'ref');
            if ($conteo->isEmpty()) {
                continue;
            }
            $conProblema = Problema::whereNotIn('estado', ['resuelto', 'cerrado'])->whereIn($col, $conteo->keys())->pluck($col)->all();
            foreach ($modelo::whereIn('id', $conteo->keys())->get() as $m) {
                if (in_array($m->id, $conProblema)) {
                    continue;
                }
                $resultado[] = [
                    'col'      => $col,
                    'id'       => $m->id,
                    'etiqueta' => $col === 'asset_id' ? $m->nomenclatura . ' · ' . ($m->tipo_equipo ?: $m->nombre) : $m->etiqueta,
                    'total'    => $conteo[$m->id],
                ];
            }
        }

        return collect($resultado)->sortByDesc('total')->values()->all();
    }

    private function datosForm(): array
    {
        return [
            'activos'     => Asset::vigentes()->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo']),
            'ubicaciones' => Ubicacion::orderBy('tipo')->orderBy('codigo')->get(['id', 'tipo', 'codigo', 'nombre']),
            'tecnicos'    => User::whereIn('role', ['tecnico', 'admin', 'gerente'])->orderBy('name')->get(['id', 'name']),
            'estados'     => Problema::ESTADOS,
        ];
    }

    public function create(Request $request)
    {
        $this->exigeEditor();
        $problema = new Problema(['prioridad' => 'Media', 'estado' => 'abierto', 'responsable_id' => auth()->id()]);
        $ticketsSugeridos = collect();

        if ($request->filled('asset_id') || $request->filled('ubicacion_id')) {
            $col = $request->filled('asset_id') ? 'asset_id' : 'ubicacion_id';
            $problema->{$col} = $request->integer($col);
            $ticketsSugeridos = Ticket::where($col, $request->integer($col))->where('created_at', '>=', now()->subDays(90))
                ->latest()->get(['id', 'descripcion', 'status', 'created_at']);
            $ref = $col === 'asset_id' ? Asset::find($problema->asset_id)?->nomenclatura : Ubicacion::find($problema->ubicacion_id)?->etiqueta;
            $problema->titulo = "Fallas repetidas en {$ref}";
        }
        if ($request->filled('ticket')) {
            $t = Ticket::find($request->integer('ticket'));
            if ($t) {
                $problema->fill(['titulo' => \Illuminate\Support\Str::limit($t->descripcion, 150, ''), 'asset_id' => $t->asset_id, 'ubicacion_id' => $t->ubicacion_id]);
                $ticketsSugeridos = collect([$t]);
            }
        }

        return view('problemas.form', array_merge(['problema' => $problema, 'ticketsSugeridos' => $ticketsSugeridos], $this->datosForm()));
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'titulo'              => 'required|string|max:200',
            'descripcion'         => 'required|string|max:5000',
            'prioridad'           => 'required|in:Baja,Media,Alta',
            'estado'              => ['nullable', Rule::in(array_keys(Problema::ESTADOS))],
            'workaround'          => 'nullable|string|max:5000',
            'causa_raiz'          => 'nullable|string|max:5000',
            'solucion_definitiva' => 'nullable|string|max:5000',
            'asset_id'            => 'nullable|integer',
            'ubicacion_id'        => 'nullable|integer',
            'responsable_id'      => 'nullable|integer|exists:users,id',
            'tickets'             => 'nullable|array',
            'tickets.*'           => 'integer',
        ]);
        $errores = [];
        if (! empty($data['asset_id']) && ! Asset::whereKey($data['asset_id'])->exists()) $errores['asset_id'] = 'Equipo no válido.';
        if (! empty($data['ubicacion_id']) && ! Ubicacion::whereKey($data['ubicacion_id'])->exists()) $errores['ubicacion_id'] = 'Ubicación no válida.';
        if (in_array($data['estado'] ?? '', ['resuelto', 'cerrado'], true) && blank($data['causa_raiz'] ?? null)) {
            $errores['causa_raiz'] = 'Para resolver o cerrar el problema documenta la causa raíz.';
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
        return $data;
    }

    public function store(Request $request)
    {
        $this->exigeEditor();
        $data = $this->validar($request);
        $ids = Ticket::whereIn('id', $data['tickets'] ?? [])->pluck('id');
        unset($data['tickets']);

        $problema = Problema::create($data + ['creado_por' => auth()->id(), 'estado' => $data['estado'] ?? 'abierto']);
        $problema->tickets()->sync($ids);
        AuditLog::registrar('problema.creado', "Abrió el problema \"{$problema->titulo}\" con {$ids->count()} ticket(s).", $problema);

        return redirect()->route('problemas.show', $problema)->with('success', 'Problema registrado.');
    }

    public function show(Problema $problema)
    {
        $this->exigeEditor();
        $problema->load(['tickets' => fn ($q) => $q->latest(), 'cambios', 'asset', 'ubicacion', 'responsable', 'creador']);

        return view('problemas.show', ['problema' => $problema, 'estados' => Problema::ESTADOS]);
    }

    public function edit(Problema $problema)
    {
        $this->exigeEditor();
        $problema->load('tickets:id');
        return view('problemas.form', array_merge(['problema' => $problema, 'ticketsSugeridos' => $problema->tickets()->get(['tickets.id', 'descripcion', 'status', 'tickets.created_at'])], $this->datosForm()));
    }

    public function update(Request $request, Problema $problema)
    {
        $this->exigeEditor();
        $data = $this->validar($request);
        $ids = Ticket::whereIn('id', $data['tickets'] ?? [])->pluck('id');
        unset($data['tickets']);

        $resolviendo = in_array($data['estado'] ?? '', ['resuelto', 'cerrado'], true) && $problema->abierto();
        if ($resolviendo) {
            $data['resuelto_at'] = now();
        }
        $problema->update($data);
        $problema->tickets()->sync($ids);

        // Al resolver, deja constancia en cada ticket ligado que siga abierto.
        if ($resolviendo) {
            foreach ($problema->tickets()->whereIn('status', ['Abierto', 'Reabierto'])->get() as $t) {
                TicketNota::create([
                    'ticket_id' => $t->id, 'user_id' => auth()->id(), 'tipo' => 'publica', 'es_automatica' => true,
                    'nota' => "Problema #{$problema->id} resuelto. Causa raíz: {$problema->causa_raiz}",
                ]);
            }
            AuditLog::registrar('problema.resuelto', "Resolvió el problema \"{$problema->titulo}\".", $problema);
        }

        return redirect()->route('problemas.show', $problema)->with('success', 'Problema actualizado.');
    }

    /** Liga un ticket a un problema existente desde el detalle del ticket. */
    public function ligarTicket(Request $request, $ticketId)
    {
        $this->exigeEditor();
        $ticket = Ticket::findOrFail($ticketId);
        $problema = Problema::findOrFail($request->validate(['problema_id' => 'required|integer'])['problema_id']);
        $problema->tickets()->syncWithoutDetaching([$ticket->id]);

        return back()->with('success', "Ticket ligado al problema #{$problema->id}.");
    }
}
