<?php

namespace App\Services;

use App\Models\Departamento;
use App\Models\SlaPolitica;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Motor de SLA de tickets: única fuente de verdad para "cuántas horas tiene
 * este ticket para resolverse" y "está vencido / por vencer / cumplido".
 *
 * Antes de este servicio existían DOS criterios distintos y hardcodeados:
 *   - AlertasService::HORAS_VENCIDO (por prioridad: Alta=24h, Media=72h,
 *     Baja=168h), usado solo para el badge/campana de "tickets vencidos".
 *   - DashboardMetricsService::SLA_HORAS = 24 (un único valor fijo para
 *     TODOS los tickets sin importar prioridad ni departamento), usado para
 *     el % de cumplimiento de SLA del Dashboard y de Reportes.
 * Ambos quedan reemplazados por este servicio, configurable desde
 * /tickets/sla (Admin y Gerente) vía la tabla sla_politicas. Si no hay
 * ninguna política configurada, se usan los mismos valores por prioridad
 * que ya existían (DEFAULT_HORAS_RESOLUCION) — cero cambio de comportamiento
 * hasta que alguien configure algo desde el panel.
 */
class SlaService
{
    private const DEFAULT_HORAS_RESOLUCION = [
        'Alta'  => 24,
        'Media' => 72,
        'Baja'  => 168, // 7 días
    ];

    private const DEFAULT_HORAS_RESOLUCION_FALLBACK = 72; // prioridad no reconocida -> trato como Media

    // Cuando la política no trae horas_alerta_temprana explícita: avisar
    // "por vencer" cuando falte este porcentaje del tiempo total del SLA.
    private const PORCENTAJE_ALERTA_DEFAULT = 0.2;

    public const PRIORIDADES = ['Alta', 'Media', 'Baja'];

    /** @var array<string, Collection<int, SlaPolitica>> políticas activas cargadas, memoizadas por hotel_code ('*' = sin propiedad) */
    private array $politicasPorHotel = [];

    /** @var array<string, int|null> nombre de departamento -> id, memoizado */
    private array $departamentoIdPorNombre = [];

    // ==========================================
    // RESOLUCIÓN DE HORAS
    // ==========================================

    public function horasResolucion(?string $hotelCode, ?string $departamentoNombre, string $prioridad): int
    {
        $politica = $this->politicaAplicable($hotelCode, $departamentoNombre, $prioridad);

        if ($politica) {
            return $politica->horas_resolucion;
        }

        return self::DEFAULT_HORAS_RESOLUCION[$prioridad] ?? self::DEFAULT_HORAS_RESOLUCION_FALLBACK;
    }

    public function horasAlertaTemprana(?string $hotelCode, ?string $departamentoNombre, string $prioridad): int
    {
        $politica        = $this->politicaAplicable($hotelCode, $departamentoNombre, $prioridad);
        $horasResolucion = $this->horasResolucion($hotelCode, $departamentoNombre, $prioridad);

        if ($politica && !is_null($politica->horas_alerta_temprana)) {
            return $politica->horas_alerta_temprana;
        }

        return max(1, (int) round($horasResolucion * self::PORCENTAJE_ALERTA_DEFAULT));
    }

    /**
     * Cascada de resolución (la primera fila que exista gana):
     *   1) esta propiedad   + este departamento
     *   2) esta propiedad   + cualquier departamento (default de la propiedad)
     *   3) cualquier propiedad + este departamento    (default global del depto)
     *   4) cualquier propiedad + cualquier departamento (default global)
     */
    private function politicaAplicable(?string $hotelCode, ?string $departamentoNombre, string $prioridad): ?SlaPolitica
    {
        $politicas      = $this->cargarPoliticas($hotelCode);
        $departamentoId = $departamentoNombre ? $this->departamentoId($departamentoNombre) : null;

        $buscar = fn (?string $hc, ?int $depId) => $politicas->first(
            fn (SlaPolitica $p) => $p->hotel_code === $hc && $p->departamento_id === $depId && $p->prioridad === $prioridad
        );

        return $buscar($hotelCode, $departamentoId)
            ?? $buscar($hotelCode, null)
            ?? $buscar(null, $departamentoId)
            ?? $buscar(null, null);
    }

    private function cargarPoliticas(?string $hotelCode): Collection
    {
        $clave = $hotelCode ?? '*';

        if (!isset($this->politicasPorHotel[$clave])) {
            $this->politicasPorHotel[$clave] = SlaPolitica::query()
                ->activos()
                ->where(function ($q) use ($hotelCode) {
                    $q->whereNull('hotel_code');
                    if ($hotelCode) {
                        $q->orWhere('hotel_code', $hotelCode);
                    }
                })
                ->get();
        }

        return $this->politicasPorHotel[$clave];
    }

    private function departamentoId(string $nombre): ?int
    {
        if (!array_key_exists($nombre, $this->departamentoIdPorNombre)) {
            $this->departamentoIdPorNombre[$nombre] = Departamento::where('nombre', $nombre)->value('id');
        }

        return $this->departamentoIdPorNombre[$nombre];
    }

    // ==========================================
    // ESTADO DE UN TICKET
    // ==========================================

    /**
     * Estado de SLA de un ticket individual. Devuelve:
     *  - horas_resolucion: horas totales que tenía para resolverse
     *  - vence_en: Carbon con el momento límite (created_at + horas_resolucion), o null si el ticket no tiene created_at
     *  - horas_restantes: horas que faltan (negativo si ya venció / se pasó al resolver)
     *  - estado: 'ok' | 'por_vencer' | 'vencido' (sigue abierto) o
     *            'cumplido' | 'incumplido' (ya se marcó como Resuelto al menos una vez)
     */
    public function estado(Ticket $ticket): array
    {
        $horasResolucion = $this->horasResolucion($ticket->hotel_code, $ticket->departamento_destino, $ticket->prioridad);
        $venceEn         = $ticket->created_at?->copy()->addHours($horasResolucion);

        // Ya se resolvió: se juzga contra el momento en que se marcó como
        // resuelto, no contra "ahora". Esto no cambia aunque el ticket haya
        // sido reabierto después: refleja cómo le fue en su último ciclo.
        if ($ticket->fecha_resolucion && $ticket->created_at) {
            $horasTomadas = $ticket->created_at->diffInMinutes($ticket->fecha_resolucion) / 60;

            return [
                'horas_resolucion' => $horasResolucion,
                'vence_en'         => $venceEn,
                'horas_restantes'  => round($horasResolucion - $horasTomadas, 1),
                'estado'           => $horasTomadas <= $horasResolucion ? 'cumplido' : 'incumplido',
            ];
        }

        if (!$venceEn) {
            return [
                'horas_resolucion' => $horasResolucion,
                'vence_en'         => null,
                'horas_restantes'  => null,
                'estado'           => 'ok',
            ];
        }

        // Diferencia en horas con signo: positivo = todavía falta tiempo,
        // negativo = ya se pasó del límite. Se calcula con timestamps
        // directamente (en vez de diffInHours($x, false)) para no depender
        // de la convención de signo de Carbon, que ha cambiado entre
        // versiones y es fácil de leer al revés.
        $horasRestantes = ($venceEn->timestamp - now()->timestamp) / 3600;
        $horasAlerta    = $this->horasAlertaTemprana($ticket->hotel_code, $ticket->departamento_destino, $ticket->prioridad);

        $estado = match (true) {
            $horasRestantes <= 0            => 'vencido',
            $horasRestantes <= $horasAlerta => 'por_vencer',
            default                         => 'ok',
        };

        return [
            'horas_resolucion' => $horasResolucion,
            'vence_en'         => $venceEn,
            'horas_restantes'  => round($horasRestantes, 1),
            'estado'           => $estado,
        ];
    }

    public function estaVencido(Ticket $ticket): bool
    {
        return $this->estado($ticket)['estado'] === 'vencido';
    }

    public function estaPorVencer(Ticket $ticket): bool
    {
        return $this->estado($ticket)['estado'] === 'por_vencer';
    }

    // ==========================================
    // LISTAS PARA LA CAMPANA DE ALERTAS (AlertasService) Y DASHBOARD
    // Mismo criterio de visibilidad por rol que ya usan AlertasService,
    // DashboardMetricsService y TicketController:
    //   - admin/gerente: todos los tickets de su propiedad.
    //   - técnico: solo los de su propio departamento.
    //   - usuario: solo los que él mismo reportó.
    //
    // A diferencia del AlertasService anterior, aquí se incluye también el
    // status "Reabierto" además de "Abierto": un ticket reabierto sigue
    // siendo trabajo pendiente y su SLA sigue corriendo, así que no tiene
    // sentido excluirlo de "vencido"/"por vencer".
    // ==========================================

    public function ticketsVencidos(User $user, int $limite = 5): Collection
    {
        return $this->ticketsAbiertosVisibles($user)
            ->filter(fn (Ticket $t) => $this->estaVencido($t))
            ->sortBy('created_at')
            ->take($limite)
            ->values();
    }

    public function ticketsVencidosCount(User $user): int
    {
        return $this->ticketsAbiertosVisibles($user)
            ->filter(fn (Ticket $t) => $this->estaVencido($t))
            ->count();
    }

    public function ticketsPorVencer(User $user, int $limite = 5): Collection
    {
        return $this->ticketsAbiertosVisibles($user)
            ->filter(fn (Ticket $t) => $this->estaPorVencer($t))
            ->sortBy(fn (Ticket $t) => $this->estado($t)['horas_restantes'])
            ->take($limite)
            ->values();
    }

    public function ticketsPorVencerCount(User $user): int
    {
        return $this->ticketsAbiertosVisibles($user)
            ->filter(fn (Ticket $t) => $this->estaPorVencer($t))
            ->count();
    }

    private function ticketsAbiertosVisibles(User $user): Collection
    {
        $query = Ticket::query()->whereIn('status', ['Abierto', 'Reabierto']);

        if (!$user->puedeVerTodos()) {
            if ($user->isTecnico()) {
                $query->where('departamento_destino', $user->departamento);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        return $query->get(['id', 'hotel_code', 'area', 'departamento_destino', 'prioridad', 'status', 'created_at']);
    }

    // ==========================================
    // ESCALAMIENTO (comando tickets:escalar-sla)
    // Corre sin usuario autenticado (consola): HotelScope no filtra nada
    // en ese caso (ver Scopes\HotelScope), así que esto revisa TODAS las
    // propiedades en una sola pasada, tal como debe hacerlo un job de
    // sistema.
    // ==========================================

    public function ticketsPendientesDeEscalar(): Collection
    {
        return Ticket::query()
            ->with('user')
            ->whereIn('status', ['Abierto', 'Reabierto'])
            ->whereNull('sla_escalado_at')
            ->get()
            ->filter(fn (Ticket $t) => $this->estaVencido($t))
            ->values();
    }

    public function marcarEscalado(Ticket $ticket): void
    {
        $ticket->update(['sla_escalado_at' => now()]);
    }

    // ==========================================
    // PANEL DE ADMINISTRACIÓN (/tickets/sla)
    // ==========================================

    /**
     * Valores EFECTIVOS (ya resueltos con la cascada) de horas de
     * resolución/alerta por departamento y prioridad, para que Admin/Gerente
     * vean de un vistazo qué SLA aplica hoy aunque no hayan configurado
     * nada todavía (en ese caso se ven los defaults del sistema).
     */
    public function resumenPorDepartamento(string $hotelCode, Collection $departamentos): array
    {
        return $departamentos->map(function ($departamento) use ($hotelCode) {
            return [
                'departamento' => $departamento,
                'valores'      => collect(self::PRIORIDADES)->mapWithKeys(fn ($prioridad) => [
                    $prioridad => [
                        'horas_resolucion'      => $this->horasResolucion($hotelCode, $departamento->nombre, $prioridad),
                        'horas_alerta_temprana' => $this->horasAlertaTemprana($hotelCode, $departamento->nombre, $prioridad),
                    ],
                ]),
            ];
        })->all();
    }
}
