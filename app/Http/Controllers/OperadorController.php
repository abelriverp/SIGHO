<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Operador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "Operadores": catálogo COMPARTIDO por departamento (ver Operador) para
 * dejar constancia de quién ejecutó el trabajo al resolver un ticket (ver
 * TicketController::resolve). No son cuentas del sistema — no inician
 * sesión, no tienen rol, no tienen permisos propios. Dos técnicos del
 * mismo departamento (ej. Ana y Beto) ven y administran el mismo
 * catálogo, no listas separadas.
 *
 * Igual que PuntoQrController: no hay borrado físico expuesto en la UI,
 * solo activar/desactivar, para no perder el conteo histórico de tickets
 * ya resueltos con un operador dado de baja.
 */
class OperadorController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        Gate::authorize('viewAny', Operador::class);

        if ($user->isAdmin()) {
            // Admin ve el catálogo completo (soporte/auditoría), agrupado
            // por propiedad + departamento — que es la unidad real de
            // visibilidad hoy, no por técnico individual.
            // withCount('tickets') trae de una vez cuántos tickets tiene
            // resueltos cada operador, sin N+1 por fila.
            $operadores = Operador::withCount('tickets')
                ->orderBy('hotel_code')
                ->orderBy('departamento')
                ->orderBy('nombre')
                ->get()
                ->groupBy(fn ($op) => $op->hotel_code . ' · ' . ($op->departamento ?: 'Sin departamento'));

            $tecnicos = User::where('role', 'tecnico')->orderBy('name')->get();

            return view('operadores.index', compact('operadores', 'tecnicos'))->with('esAdmin', true);
        }

        $operadores = Operador::deDepartamento($user->departamento)->withCount('tickets')->orderBy('nombre')->get();

        return view('operadores.index', compact('operadores'))->with('esAdmin', false);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        Gate::authorize('create', Operador::class);

        $data = $request->validate([
            'nombre'     => 'required|string|max:120',
            'tecnico_id' => 'nullable|integer|exists:users,id',
        ], [
            'nombre.required' => 'Indica el nombre del operador.',
        ]);

        // Un técnico se da de alta operadores para SU departamento; solo
        // Admin puede elegir a nombre de qué técnico (y por lo tanto, de
        // qué propiedad/departamento) se registra.
        $tecnicoRegistro = $user;
        if ($user->isAdmin() && !empty($data['tecnico_id'])) {
            $tecnicoRegistro = User::findOrFail($data['tecnico_id']);
        }

        // hotel_code y departamento explícitos con los del técnico elegido
        // (no los del creador): si Admin registra a nombre de un técnico
        // de otra propiedad/departamento, el operador debe quedar ahí, no
        // en la propiedad de Admin (que además puede no tener hotel_code
        // propio). El operador queda visible para TODO ese departamento,
        // no solo para $tecnicoRegistro.
        $operador = Operador::create([
            'hotel_code'   => $tecnicoRegistro->hotel_code,
            'departamento' => $tecnicoRegistro->departamento,
            'tecnico_id'   => $tecnicoRegistro->id,
            'nombre'       => $data['nombre'],
            'activo'       => true,
        ]);

        AuditLog::registrar(
            'operador.creado',
            "Registró al operador \"{$operador->nombre}\" (departamento: " . ($operador->departamento ?: 'sin definir') . ').',
            $operador,
            null,
            $operador->only(['tecnico_id', 'departamento', 'nombre', 'activo'])
        );

        return back()->with('success', "Operador \"{$operador->nombre}\" registrado.");
    }

    public function update(Request $request, Operador $operador)
    {
        Gate::authorize('update', $operador);

        $data = $request->validate([
            'nombre' => 'required|string|max:120',
        ]);

        $antes = $operador->only(['nombre']);
        $operador->update(['nombre' => $data['nombre']]);

        AuditLog::registrar(
            'operador.renombrado',
            "Renombró al operador \"{$antes['nombre']}\" a \"{$operador->nombre}\".",
            $operador,
            $antes,
            $operador->only(['nombre'])
        );

        return back()->with('success', 'Operador actualizado.');
    }

    public function toggleActivo(Operador $operador)
    {
        Gate::authorize('update', $operador);

        $operador->update(['activo' => !$operador->activo]);

        AuditLog::registrar(
            $operador->activo ? 'operador.activado' : 'operador.desactivado',
            ($operador->activo ? 'Activó' : 'Desactivó') . " al operador \"{$operador->nombre}\".",
            $operador,
            null,
            $operador->only(['activo'])
        );

        return back()->with('success', $operador->activo
            ? 'Operador activado.'
            : 'Operador desactivado: ya no aparece al resolver tickets, pero su historial se conserva.');
    }
}
