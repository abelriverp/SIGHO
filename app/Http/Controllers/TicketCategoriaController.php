<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\TicketCampo;
use App\Models\TicketCategoria;
use App\Models\TicketSubcategoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Administra el catálogo dinámico de "tipo de solicitud" -> "sistema" ->
 * "campos" que usa el formulario de creación de tickets (ver
 * TicketController::store y resources/views/tickets/create.blade.php).
 *
 * Reemplaza lo que antes vivía hardcodeado en config/tickets_sistemas.php:
 * ahora admin y gerente lo administran desde aquí, por propiedad (cada
 * hotel_code tiene su propio catálogo) y por cualquier departamento (no
 * solo Sistemas).
 *
 * Los campos (TicketCampo) de una subcategoría, o de una categoría sin
 * subcategorías, se administran junto con su padre en un solo formulario
 * (array repetible) en vez de tener pantallas de CRUD separadas: como
 * tickets ya guarda su propio snapshot de "name/label" en
 * campos_extra->campos al crearse, borrar y re-crear las filas de
 * ticket_campos en cada guardado es seguro y no afecta tickets existentes.
 */
class TicketCategoriaController extends Controller
{
    use ResuelveHotelActivo;

    public function index(Request $request)
    {
        $departamentoId = $request->query('departamento_id');

        $categorias = TicketCategoria::deHotel($this->hotelActivo())
            ->with('departamento')
            ->withCount(['subcategorias', 'campos'])
            ->when($departamentoId, fn ($q) => $q->where('departamento_id', $departamentoId))
            ->orderBy('departamento_id')
            ->orderBy('orden')
            ->get();

        $departamentos = Departamento::activos()->orderBy('nombre')->get();

        return view('tickets.categorias.index', compact('categorias', 'departamentos', 'departamentoId'));
    }

    public function create()
    {
        $departamentos = Departamento::activos()->orderBy('nombre')->get();
        return view('tickets.categorias.form', compact('departamentos'));
    }

    public function store(Request $request)
    {
        $data = $this->validarCategoria($request);
        $data['hotel_code'] = $this->hotelActivo();

        $categoria = TicketCategoria::create($data);

        AuditLog::registrar(
            'ticket_categoria.creada',
            "Creó la categoría de tickets \"{$categoria->label}\".",
            $categoria,
            null,
            $categoria->only(['label', 'departamento_id', 'requiere_subcategoria', 'orden', 'activo'])
        );

        return redirect()->route('tickets.categorias.show', $categoria)->with('success', 'Categoría creada. Ahora agrega los sistemas o campos que necesite.');
    }

    public function show(TicketCategoria $categoria)
    {
        $this->autorizarPropiedad($categoria);

        $categoria->load(['departamento', 'subcategorias.campos', 'campos']);

        return view('tickets.categorias.show', compact('categoria'));
    }

    public function edit(TicketCategoria $categoria)
    {
        $this->autorizarPropiedad($categoria);

        $categoria->load('campos');
        $departamentos = Departamento::activos()->orderBy('nombre')->get();

        return view('tickets.categorias.form', compact('categoria', 'departamentos'));
    }

    public function update(Request $request, TicketCategoria $categoria)
    {
        $this->autorizarPropiedad($categoria);

        $antes = $categoria->only(['label', 'departamento_id', 'requiere_subcategoria', 'orden', 'activo']);
        $data  = $this->validarCategoria($request, $categoria);

        $categoria->update($data);

        // Si dejó de requerir subcategoría, sus subcategorías (y los campos
        // de estas) ya no tienen sentido — se eliminan en cascada por FK.
        // Si ahora SÍ requiere subcategoría, sus campos propios (los de
        // cuando no la requería) tampoco aplican.
        if ($categoria->requiere_subcategoria) {
            $categoria->campos()->delete();
        } else {
            $categoria->subcategorias()->delete();
            $this->sincronizarCampos($request, categoriaId: $categoria->id, subcategoriaId: null);
        }

        AuditLog::registrar(
            'ticket_categoria.actualizada',
            "Editó la categoría de tickets \"{$categoria->label}\".",
            $categoria,
            $antes,
            $categoria->fresh()->only(['label', 'departamento_id', 'requiere_subcategoria', 'orden', 'activo'])
        );

        return redirect()->route('tickets.categorias.show', $categoria)->with('success', 'Categoría actualizada.');
    }

    public function destroy(TicketCategoria $categoria)
    {
        $this->autorizarPropiedad($categoria);

        $datosPrevios = $categoria->only(['label', 'departamento_id']);

        AuditLog::registrar(
            'ticket_categoria.eliminada',
            "Eliminó la categoría de tickets \"{$datosPrevios['label']}\".",
            $categoria,
            $datosPrevios,
            null
        );

        $categoria->delete();

        return redirect()->route('tickets.categorias.index')->with('success', 'Categoría eliminada. Los tickets ya creados con ella conservan su información.');
    }

    // ==========================================
    // SUBCATEGORÍAS ("sistema")
    // ==========================================

    public function subcategoriasCreate(Request $request)
    {
        $categoria = TicketCategoria::findOrFail($request->query('categoria_id'));
        $this->autorizarPropiedad($categoria);

        return view('tickets.categorias.subcategoria_form', compact('categoria'));
    }

    public function subcategoriasStore(Request $request)
    {
        $categoria = TicketCategoria::findOrFail($request->input('ticket_categoria_id'));
        $this->autorizarPropiedad($categoria);

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'nota'  => 'nullable|string|max:1000',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);
        $data['activo']              = $request->boolean('activo', true);
        $data['ticket_categoria_id'] = $categoria->id;

        $subcategoria = TicketSubcategoria::create($data);

        AuditLog::registrar(
            'ticket_subcategoria.creada',
            "Creó el sistema \"{$subcategoria->label}\" dentro de \"{$categoria->label}\".",
            $subcategoria,
            null,
            $subcategoria->only(['label', 'nota', 'orden', 'activo'])
        );

        return redirect()->route('tickets.categorias.subcategorias.edit', $subcategoria)
            ->with('success', 'Sistema creado. Agrega abajo los campos que debe pedir.');
    }

    public function subcategoriasEdit(TicketSubcategoria $subcategoria)
    {
        $this->autorizarPropiedad($subcategoria->categoria);
        $subcategoria->load('campos');

        return view('tickets.categorias.subcategoria_form', ['categoria' => $subcategoria->categoria, 'subcategoria' => $subcategoria]);
    }

    public function subcategoriasUpdate(Request $request, TicketSubcategoria $subcategoria)
    {
        $this->autorizarPropiedad($subcategoria->categoria);

        $data = $request->validate([
            'label'  => 'required|string|max:255',
            'nota'   => 'nullable|string|max:1000',
            'orden'  => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);
        $data['activo'] = $request->boolean('activo', true);

        $subcategoria->update($data);
        $this->sincronizarCampos($request, categoriaId: null, subcategoriaId: $subcategoria->id);

        AuditLog::registrar(
            'ticket_subcategoria.actualizada',
            "Editó el sistema \"{$subcategoria->label}\".",
            $subcategoria,
            null,
            $subcategoria->fresh()->only(['label', 'nota', 'orden', 'activo'])
        );

        return redirect()->route('tickets.categorias.show', $subcategoria->ticket_categoria_id)->with('success', 'Sistema actualizado.');
    }

    public function subcategoriasDestroy(TicketSubcategoria $subcategoria)
    {
        $this->autorizarPropiedad($subcategoria->categoria);

        $categoriaId  = $subcategoria->ticket_categoria_id;
        $datosPrevios = $subcategoria->only(['label', 'ticket_categoria_id']);

        AuditLog::registrar(
            'ticket_subcategoria.eliminada',
            "Eliminó el sistema \"{$datosPrevios['label']}\".",
            $subcategoria,
            $datosPrevios,
            null
        );

        $subcategoria->delete();

        return redirect()->route('tickets.categorias.show', $categoriaId)->with('success', 'Sistema eliminado.');
    }

    /**
     * Clona una subcategoría (con todos sus campos) dentro de la misma
     * categoría, con un nombre distinto — pensado para el caso "GestHotel
     * necesita exactamente los mismos campos que ya tiene Opera": en vez de
     * capturar todo de nuevo a mano, se duplica y solo se cambia el label.
     */
    public function subcategoriasDuplicar(TicketSubcategoria $subcategoria)
    {
        $this->autorizarPropiedad($subcategoria->categoria);
        $subcategoria->load('campos');

        $copia = TicketSubcategoria::create([
            'ticket_categoria_id' => $subcategoria->ticket_categoria_id,
            'label'               => $subcategoria->label . ' (copia)',
            'nota'                => $subcategoria->nota,
            'orden'               => $subcategoria->orden,
            'activo'              => true,
        ]);

        foreach ($subcategoria->campos as $campo) {
            TicketCampo::create([
                'ticket_subcategoria_id' => $copia->id,
                'name'                   => $campo->name,
                'label'                  => $campo->label,
                'tipo_campo'             => $campo->tipo_campo,
                'requerido'              => $campo->requerido,
                'orden'                  => $campo->orden,
            ]);
        }

        AuditLog::registrar(
            'ticket_subcategoria.duplicada',
            "Duplicó el sistema \"{$subcategoria->label}\" como \"{$copia->label}\".",
            $copia,
            null,
            $copia->only(['label'])
        );

        return redirect()->route('tickets.categorias.subcategorias.edit', $copia)
            ->with('success', 'Sistema duplicado. Cambia el nombre y ajusta lo que necesites.');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function validarCategoria(Request $request, ?TicketCategoria $categoria = null): array
    {
        $data = $request->validate([
            'label'                 => 'required|string|max:255',
            'departamento_id'       => ['required', Rule::exists('departamentos', 'id')],
            'requiere_subcategoria' => 'nullable|boolean',
            'orden'                 => 'nullable|integer|min:0',
            'activo'                => 'nullable|boolean',
        ]);

        $data['requiere_subcategoria'] = $request->boolean('requiere_subcategoria');
        $data['activo']                = $request->boolean('activo', true);
        $data['orden']                 = $data['orden'] ?? 0;

        return $data;
    }

    /**
     * Reemplaza por completo los campos de una categoría (sin subcategoría)
     * o de una subcategoría, a partir del array repetible que manda el
     * formulario. Los tickets ya creados guardan su propio snapshot en
     * campos_extra, así que no dependen de que estas filas se conserven.
     */
    private function sincronizarCampos(Request $request, ?int $categoriaId, ?int $subcategoriaId): void
    {
        if ($categoriaId) {
            TicketCampo::where('ticket_categoria_id', $categoriaId)->delete();
        } else {
            TicketCampo::where('ticket_subcategoria_id', $subcategoriaId)->delete();
        }

        $campos = $request->input('campos', []);

        foreach ($campos as $orden => $campo) {
            if (empty($campo['name']) || empty($campo['label'])) {
                continue;
            }

            TicketCampo::create([
                'ticket_categoria_id'    => $categoriaId,
                'ticket_subcategoria_id' => $subcategoriaId,
                'name'                   => \Illuminate\Support\Str::slug($campo['name'], '_'),
                'label'                  => $campo['label'],
                'tipo_campo'             => in_array($campo['tipo_campo'] ?? 'text', array_keys(TicketCampo::TIPOS)) ? $campo['tipo_campo'] : 'text',
                'requerido'              => !empty($campo['requerido']),
                'orden'                  => $orden,
            ]);
        }
    }

    /**
     * Una categoría/subcategoría solo se puede administrar desde la
     * propiedad a la que pertenece (mismo criterio que el resto del panel:
     * Admin puede pararse en cualquiera vía el selector de propiedad
     * activa; Gerente queda fijo a la suya vía hotel_code).
     */
    private function autorizarPropiedad(TicketCategoria $categoria): void
    {
        abort_unless($categoria->hotel_code === $this->hotelActivo(), 403, 'Esta categoría pertenece a otra propiedad.');
    }
}
