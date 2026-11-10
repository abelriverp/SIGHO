<?php

namespace App\Http\Controllers;

use App\Models\ArticuloConocimiento;
use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Base de conocimiento (Fase 2, nov 2026). Todos los usuarios con Tickets
 * la consultan; tecnicos y admins escriben; solo supervisores eliminan.
 */
class ConocimientoController extends Controller
{
    private function exigeEditor(): void
    {
        if (! auth()->user()->puedeResponderTickets()) {
            abort(403, 'Solo técnicos y administradores pueden escribir en la base de conocimiento.');
        }
    }

    private function reglas(): array
    {
        return [
            'titulo'       => 'required|string|max:200',
            'departamento' => 'nullable|string|max:100',
            'categoria'    => 'nullable|string|max:150',
            'sintoma'      => 'nullable|string|max:5000',
            'solucion'     => 'required|string|max:20000',
            'etiquetas'    => 'nullable|string|max:255',
            'publicado'    => 'nullable|boolean',
        ];
    }

    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'departamento']);
        $editor = auth()->user()->puedeResponderTickets();

        $articulos = ArticuloConocimiento::query()
            ->when(! $editor, fn ($q) => $q->publicados())
            ->buscar($filtros['q'] ?? null)
            ->when($filtros['departamento'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('departamento', $v)->orWhereNull('departamento')))
            ->orderByDesc('usos')
            ->orderByDesc('vistas')
            ->orderBy('titulo')
            ->paginate(30)
            ->withQueryString();

        return view('conocimiento.index', [
            'articulos'     => $articulos,
            'filtros'       => $filtros,
            'departamentos' => Departamento::activos()->orderBy('nombre')->pluck('nombre'),
            'editor'        => $editor,
        ]);
    }

    public function show(ArticuloConocimiento $articulo)
    {
        if (! $articulo->publicado && ! auth()->user()->puedeResponderTickets()) {
            abort(404);
        }
        $articulo->increment('vistas');

        return view('conocimiento.show', ['articulo' => $articulo->load(['autor', 'ticketOrigen'])]);
    }

    public function create(Request $request)
    {
        $this->exigeEditor();

        $articulo = new ArticuloConocimiento([
            'publicado'    => true,
            'departamento' => auth()->user()->departamento,
        ]);

        // "Convertir esta solucion en articulo" desde un ticket resuelto.
        if ($request->filled('ticket')) {
            $ticket = Ticket::find($request->integer('ticket'));
            if ($ticket && auth()->user()->can('view', $ticket) && $ticket->notas_admin) {
                $categoria = $ticket->campos_extra['categoria_label'] ?? null;
                $sub = $ticket->campos_extra['subcategoria_label'] ?? null;
                $articulo->fill([
                    'titulo'           => trim(($sub ?: $categoria ?: 'Solución') . ': ' . \Illuminate\Support\Str::limit($ticket->descripcion, 120, '')),
                    'departamento'     => $ticket->departamento_destino,
                    'categoria'        => $categoria,
                    'sintoma'          => $ticket->descripcion,
                    'solucion'         => $ticket->notas_admin,
                    'ticket_origen_id' => $ticket->id,
                ]);
            }
        }

        return view('conocimiento.form', [
            'articulo'      => $articulo,
            'departamentos' => Departamento::activos()->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    public function store(Request $request)
    {
        $this->exigeEditor();
        $data = $request->validate($this->reglas() + ['ticket_origen_id' => 'nullable|integer|exists:tickets,id']);
        $data['publicado'] = $request->boolean('publicado');
        $data['autor_id'] = auth()->id();

        if (! empty($data['ticket_origen_id']) && ! Ticket::whereKey($data['ticket_origen_id'])->exists()) {
            $data['ticket_origen_id'] = null;
        }

        $articulo = ArticuloConocimiento::create($data);

        return redirect()->route('conocimiento.show', $articulo)->with('success', 'Artículo guardado.');
    }

    public function edit(ArticuloConocimiento $articulo)
    {
        $this->exigeEditor();

        return view('conocimiento.form', [
            'articulo'      => $articulo,
            'departamentos' => Departamento::activos()->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    public function update(Request $request, ArticuloConocimiento $articulo)
    {
        $this->exigeEditor();
        $data = $request->validate($this->reglas());
        $data['publicado'] = $request->boolean('publicado');
        $articulo->update($data);

        return redirect()->route('conocimiento.show', $articulo)->with('success', 'Artículo actualizado.');
    }

    public function destroy(ArticuloConocimiento $articulo)
    {
        if (! auth()->user()->puedeVerTodos()) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar artículos.');
        }

        AuditLog::registrar('conocimiento.eliminado', "Eliminó el artículo \"{$articulo->titulo}\".", $articulo, $articulo->only(['titulo', 'departamento', 'categoria']), null);
        $articulo->delete();

        return redirect()->route('conocimiento.index')->with('success', 'Artículo eliminado.');
    }
}
