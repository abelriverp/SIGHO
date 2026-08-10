<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveHotelActivo;
use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\SlaPolitica;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Administra las políticas de SLA (horas de resolución / alerta temprana
 * por departamento y prioridad) que usa App\Services\SlaService. Mismo
 * patrón que TicketCategoriaController: administrable por admin y gerente
 * (middleware 'supervisor'), con overrides por propiedad (cada hotel_code
 * administra las suyas).
 *
 * Las filas que se crean desde este panel siempre quedan con
 * hotel_code = propiedad activa (un override para ESA propiedad). Las
 * políticas verdaderamente globales (hotel_code null, aplican a cualquier
 * propiedad que no tenga su propio override) no se exponen todavía en la
 * UI — viven como fallback de fábrica en SlaService::DEFAULT_HORAS_RESOLUCION
 * y pueden sembrarse a mano en la tabla si a futuro se necesita un default
 * distinto sin tocar código.
 */
class SlaPoliticaController extends Controller
{
    use ResuelveHotelActivo;

    public function index(SlaService $sla)
    {
        $hotelActivo = $this->hotelActivo();

        $departamentos = Departamento::activos()->orderBy('nombre')->get();

        $resumen = $sla->resumenPorDepartamento($hotelActivo, $departamentos);

        $politicas = SlaPolitica::query()
            ->paraPropiedad($hotelActivo)
            ->with('departamento')
            ->orderByRaw('departamento_id is null desc') // "Todos los departamentos" al final
            ->orderBy('departamento_id')
            // CASE en vez de FIELD(): FIELD() es exclusivo de MySQL/MariaDB
            // y los tests corren contra SQLite (ver phpunit.xml) — CASE es
            // portable entre ambos motores.
            ->orderByRaw("CASE prioridad WHEN 'Alta' THEN 1 WHEN 'Media' THEN 2 WHEN 'Baja' THEN 3 ELSE 4 END")
            ->get();

        return view('tickets.sla.index', compact('resumen', 'politicas', 'hotelActivo'));
    }

    public function create()
    {
        $departamentos = Departamento::activos()->orderBy('nombre')->get();

        return view('tickets.sla.form', compact('departamentos'));
    }

    public function store(Request $request)
    {
        $data = $this->validarPolitica($request);
        $data['hotel_code'] = $this->hotelActivo();

        $this->validarSinDuplicado($data);

        $politica = SlaPolitica::create($data);

        AuditLog::registrar(
            'sla_politica.creada',
            "Creó una política de SLA para \"{$this->etiquetaDepartamento($politica)}\" / prioridad {$politica->prioridad}: {$politica->horas_resolucion}h.",
            $politica,
            null,
            $politica->only(['departamento_id', 'prioridad', 'horas_resolucion', 'horas_alerta_temprana', 'activo'])
        );

        return redirect()->route('tickets.sla.index')->with('success', 'Política de SLA creada.');
    }

    public function edit(SlaPolitica $politica)
    {
        $this->autorizarPropiedad($politica);

        $departamentos = Departamento::activos()->orderBy('nombre')->get();

        return view('tickets.sla.form', compact('politica', 'departamentos'));
    }

    public function update(Request $request, SlaPolitica $politica)
    {
        $this->autorizarPropiedad($politica);

        $antes = $politica->only(['departamento_id', 'prioridad', 'horas_resolucion', 'horas_alerta_temprana', 'activo']);
        $data  = $this->validarPolitica($request, $politica);
        $data['hotel_code'] = $politica->hotel_code;

        $this->validarSinDuplicado($data, $politica->id);

        $politica->update($data);

        AuditLog::registrar(
            'sla_politica.actualizada',
            "Editó la política de SLA para \"{$this->etiquetaDepartamento($politica)}\" / prioridad {$politica->prioridad}.",
            $politica,
            $antes,
            $politica->fresh()->only(['departamento_id', 'prioridad', 'horas_resolucion', 'horas_alerta_temprana', 'activo'])
        );

        return redirect()->route('tickets.sla.index')->with('success', 'Política de SLA actualizada.');
    }

    public function destroy(SlaPolitica $politica)
    {
        $this->autorizarPropiedad($politica);

        $etiqueta = "{$this->etiquetaDepartamento($politica)} / {$politica->prioridad}";

        AuditLog::registrar(
            'sla_politica.eliminada',
            "Eliminó la política de SLA \"{$etiqueta}\". Vuelve a aplicar el default del sistema.",
            $politica,
            $politica->only(['departamento_id', 'prioridad', 'horas_resolucion']),
            null
        );

        $politica->delete();

        return redirect()->route('tickets.sla.index')->with('success', 'Política eliminada. Ese departamento/prioridad vuelve a usar el valor por defecto.');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function validarPolitica(Request $request, ?SlaPolitica $politica = null): array
    {
        $data = $request->validate([
            'departamento_id'       => ['nullable', Rule::exists('departamentos', 'id')],
            'prioridad'             => ['required', Rule::in(SlaService::PRIORIDADES)],
            'horas_resolucion'      => 'required|integer|min:1|max:2160', // tope de 90 días, por seguridad
            'horas_alerta_temprana' => 'nullable|integer|min:0|max:2160',
            'activo'                => 'nullable|boolean',
        ], [
            'horas_resolucion.required' => 'Indica cuántas horas tiene este departamento/prioridad para resolver el ticket.',
            'horas_resolucion.min'      => 'Debe ser al menos 1 hora.',
        ]);

        if (!empty($data['horas_alerta_temprana']) && $data['horas_alerta_temprana'] >= $data['horas_resolucion']) {
            throw ValidationException::withMessages([
                'horas_alerta_temprana' => 'Las horas de alerta temprana deben ser menores a las horas de resolución (es un aviso ANTES del vencimiento).',
            ]);
        }

        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }

    /**
     * Evita dos políticas activas para el mismo hotel_code + departamento_id
     * + prioridad: SlaService::politicaAplicable() toma la primera que
     * encuentra, así que un duplicado dejaría una fila "invisible" sin
     * avisarle a nadie.
     */
    private function validarSinDuplicado(array $data, ?int $ignorarId = null): void
    {
        $existe = SlaPolitica::query()
            ->where('hotel_code', $data['hotel_code'])
            ->where('departamento_id', $data['departamento_id'] ?? null)
            ->where('prioridad', $data['prioridad'])
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'prioridad' => 'Ya existe una política de SLA para esta combinación de departamento y prioridad. Edita la existente en vez de crear otra.',
            ]);
        }
    }

    private function etiquetaDepartamento(SlaPolitica $politica): string
    {
        return $politica->departamento_id
            ? ($politica->departamento?->nombre ?? "Depto #{$politica->departamento_id}")
            : 'Todos los departamentos';
    }

    private function autorizarPropiedad(SlaPolitica $politica): void
    {
        abort_unless($politica->hotel_code === $this->hotelActivo(), 403, 'Esta política pertenece a otra propiedad.');
    }
}
