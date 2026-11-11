<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\Ticket;
use App\Models\Ubicacion;
use App\Models\User;
use App\Notifications\NuevoTicketNotification;
use App\Services\TableroDireccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * API REST v1 de SIGHO (Fase 5). Lectura de activos, tickets, monitoreo y
 * KPIs; creación de tickets con token de escritura.
 */
class ApiController extends Controller
{
    public function activos(Request $request)
    {
        $q = Asset::with('ubicacion:id,tipo,codigo,nombre')
            ->when($request->query('categoria'), fn ($w, $v) => $w->where('categoria', $v))
            ->when($request->query('status'), fn ($w, $v) => $w->where('status', $v))
            ->when($request->query('q'), fn ($w, $v) => $w->where(fn ($x) => $x->where('nomenclatura', 'like', "%{$v}%")->orWhere('numero_serie', 'like', "%{$v}%")->orWhere('ip_address', 'like', "%{$v}%")))
            ->orderBy('nomenclatura');

        return $q->paginate(min(200, max(1, (int) $request->query('por_pagina', 100))))
            ->through(fn ($a) => $this->formatoActivo($a));
    }

    public function activo(string $nomenclatura)
    {
        $asset = Asset::with('ubicacion')->where('nomenclatura', $nomenclatura)->first();
        return $asset ? $this->formatoActivo($asset) : response()->json(['error' => 'No existe ese activo.'], 404);
    }

    private function formatoActivo(Asset $asset): array
    {
        return [
            'id'            => $asset->id,
            'nomenclatura'  => $asset->nomenclatura,
            'nombre'        => $asset->nombre,
            'categoria'     => $asset->categoria,
            'tipo'          => $asset->tipo_equipo,
            'marca'         => $asset->marca,
            'modelo'        => $asset->modelo,
            'numero_serie'  => $asset->numero_serie,
            'estado'        => $asset->status,
            'ip'            => $asset->ip_address,
            'mac'           => $asset->mac_address,
            'ubicacion'     => $asset->ubicacion?->codigo,
            'garantia_hasta'=> $asset->garantia_hasta?->toDateString(),
            'monitoreo'     => $asset->monitorear ? ['estado' => $asset->estado_monitoreo, 'latencia_ms' => $asset->latencia_ms, 'ultimo_ping' => $asset->ultimo_ping_at?->toIso8601String()] : null,
        ];
    }

    public function tickets(Request $request)
    {
        return Ticket::with(['asset:id,nomenclatura', 'ubicacion:id,codigo'])
            ->when($request->query('status'), fn ($w, $v) => $w->where('status', $v))
            ->when($request->query('desde'), fn ($w, $v) => $w->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->latest()
            ->paginate(min(200, max(1, (int) $request->query('por_pagina', 50))))
            ->through(fn ($t) => [
                'id'           => $t->id,
                'status'       => $t->status,
                'prioridad'    => $t->prioridad,
                'departamento' => $t->departamento_destino,
                'descripcion'  => $t->descripcion,
                'activo'       => $t->asset?->nomenclatura,
                'ubicacion'    => $t->ubicacion?->codigo,
                'creado'       => $t->created_at?->toIso8601String(),
                'resuelto'     => $t->fecha_resolucion?->toIso8601String(),
                'csat'         => $t->csat_calificacion,
            ]);
    }

    public function crearTicket(Request $request)
    {
        $data = $request->validate([
            'departamento_destino' => ['required', 'string', Rule::in(Departamento::activos()->pluck('nombre')->all())],
            'descripcion'          => 'required|string|max:5000',
            'prioridad'            => 'required|in:Baja,Media,Alta',
            'activo'               => 'nullable|string|max:255',
            'ubicacion'            => 'nullable|string|max:50',
        ]);
        $user = $request->user();
        $asset = ! empty($data['activo']) ? Asset::where('nomenclatura', $data['activo'])->first() : null;
        $ubic = ! empty($data['ubicacion']) ? Ubicacion::where('codigo', mb_strtoupper($data['ubicacion']))->first() : null;

        $ticket = Ticket::create([
            'hotel_code'           => Hotel::activoParaUsuario($user),
            'user_id'              => $user->id,
            'area'                 => $user->area ?? 'API',
            'departamento_destino' => $data['departamento_destino'],
            'descripcion'          => $data['descripcion'],
            'prioridad'            => $data['prioridad'],
            'asset_id'             => $asset?->id,
            'ubicacion_id'         => $ubic?->id ?? $asset?->ubicacion_id,
            'status'               => 'Abierto',
        ]);

        $dest = User::where(fn ($q) => $q->where('role', 'admin')->orWhere(fn ($w) => $w->where('role', 'tecnico')->where('departamento', $ticket->departamento_destino)))->get();
        Notification::send($dest, new NuevoTicketNotification($ticket));

        return response()->json(['id' => $ticket->id, 'status' => $ticket->status], 201);
    }

    public function monitoreo()
    {
        return Asset::where('monitorear', true)->vigentes()->orderBy('nomenclatura')
            ->get(['nomenclatura', 'tipo_equipo', 'ip_address', 'estado_monitoreo', 'latencia_ms', 'ultimo_ping_at', 'caido_desde'])
            ->map(fn ($a) => [
                'nomenclatura' => $a->nomenclatura, 'tipo' => $a->tipo_equipo, 'ip' => $a->ip_address,
                'estado' => $a->estado_monitoreo, 'latencia_ms' => $a->latencia_ms,
                'ultimo_ping' => $a->ultimo_ping_at?->toIso8601String(), 'caido_desde' => $a->caido_desde?->toIso8601String(),
            ]);
    }

    public function kpis(Request $request, TableroDireccionService $servicio)
    {
        $desde = $request->query('desde') ? Carbon::parse($request->query('desde')) : now()->startOfMonth();
        $hasta = $request->query('hasta') ? Carbon::parse($request->query('hasta')) : now();
        $user = $request->user();
        $t = $servicio->generar($user, Hotel::activoParaUsuario($user), $desde, $hasta);

        return [
            'periodo'        => ['desde' => $t['desde']->toDateString(), 'hasta' => $t['hasta']->toDateString()],
            'tickets'        => [
                'creados' => $t['tickets']['creados'], 'resueltos' => $t['tickets']['resueltos'],
                'sla_pct' => $t['tickets']['slaPorcentaje'], 'mttr_horas' => $t['mttr'],
                'csat' => $t['tickets']['calidad']['csatPromedio'], 'reabiertos_pct' => $t['tickets']['calidad']['reaperturaPct'],
                'primera_respuesta_min' => $t['tickets']['calidad']['primeraRespuestaMin'],
                'pendientes' => $t['backlogTotal'],
            ],
            'activos'        => ['total' => $t['activosTotal'], 'por_estado' => $t['activosPorEstado'], 'valor_mxn' => $t['valorActivos']],
            'monitoreo'      => ['equipos' => $t['monitoreados'], 'caidas' => $t['caidas'], 'disponibilidad_pct' => $t['disponibilidad'], 'caidos_ahora' => $t['caidosAhora']],
            'contratos'      => ['gasto_anual_mxn' => $t['gastoAnual'], 'costo_ti_mes_mxn' => $t['costoTiMes'], 'costo_por_cuarto_noche' => $t['costoPorCuartoNoche']],
            'problemas'      => ['abiertos' => $t['problemasAbiertos']],
        ];
    }
}
