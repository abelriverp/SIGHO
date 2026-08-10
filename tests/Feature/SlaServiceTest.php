<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\SlaPolitica;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el motor de SLA (App\Services\SlaService): la cascada de resolución
 * de políticas (departamento+propiedad > propiedad > departamento global >
 * default del sistema), el cálculo de estado por ticket, y que la
 * visibilidad por rol siga siendo la misma que ya usan AlertasService /
 * DashboardMetricsService / TicketController.
 */
class SlaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $hotelCode, string $role = 'usuario', array $extra = []): User
    {
        Hotel::firstOrCreate(['hotel_code' => $hotelCode], ['nombre' => "Hotel {$hotelCode}"]);

        return User::factory()->create(array_merge([
            'role'                 => $role,
            'hotel_code'           => $hotelCode,
            'must_change_password' => false,
        ], $extra));
    }

    private function crearTicket(User $reportante, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'user_id'              => $reportante->id,
            'area'                 => 'Recepción',
            'departamento_destino' => 'Sistemas',
            'descripcion'          => 'Falla de prueba',
            'prioridad'            => 'Media',
            'status'               => 'Abierto',
        ], $extra));
    }

    public function test_falls_back_to_default_hours_when_no_policy_is_configured(): void
    {
        $sla = app(SlaService::class);

        $this->assertSame(24, $sla->horasResolucion('HEMZT', 'Sistemas', 'Alta'));
        $this->assertSame(72, $sla->horasResolucion('HEMZT', 'Sistemas', 'Media'));
        $this->assertSame(168, $sla->horasResolucion('HEMZT', 'Sistemas', 'Baja'));
    }

    public function test_property_wide_override_beats_the_system_default(): void
    {
        SlaPolitica::create([
            'hotel_code'        => 'HEMZT',
            'departamento_id'   => null,
            'prioridad'         => 'Alta',
            'horas_resolucion'  => 8,
            'activo'            => true,
        ]);

        $sla = app(SlaService::class);

        $this->assertSame(8, $sla->horasResolucion('HEMZT', 'Sistemas', 'Alta'));
        // Otra propiedad sin override propio no se ve afectada.
        $this->assertSame(24, $sla->horasResolucion('OTRA', 'Sistemas', 'Alta'));
    }

    public function test_department_specific_override_beats_the_property_wide_one(): void
    {
        // 'Mantenimiento' ya viene sembrado por la migración de departamentos
        // (junto con Sistemas/Seguridad/Ama de Llaves); firstOrCreate evita
        // chocar con el UNIQUE de departamentos.nombre si ya existe.
        $depto = Departamento::firstOrCreate(['nombre' => 'Mantenimiento'], ['activo' => true]);

        SlaPolitica::create([
            'hotel_code'       => 'HEMZT',
            'departamento_id'  => null,
            'prioridad'        => 'Alta',
            'horas_resolucion' => 8,
            'activo'           => true,
        ]);
        SlaPolitica::create([
            'hotel_code'       => 'HEMZT',
            'departamento_id'  => $depto->id,
            'prioridad'        => 'Alta',
            'horas_resolucion' => 4,
            'activo'           => true,
        ]);

        $sla = app(SlaService::class);

        $this->assertSame(4, $sla->horasResolucion('HEMZT', 'Mantenimiento', 'Alta'));
        // Otro departamento de la misma propiedad sigue usando el default de la propiedad.
        $this->assertSame(8, $sla->horasResolucion('HEMZT', 'Sistemas', 'Alta'));
    }

    public function test_inactive_policy_is_ignored(): void
    {
        SlaPolitica::create([
            'hotel_code'       => 'HEMZT',
            'departamento_id'  => null,
            'prioridad'        => 'Alta',
            'horas_resolucion' => 8,
            'activo'           => false,
        ]);

        $sla = app(SlaService::class);

        $this->assertSame(24, $sla->horasResolucion('HEMZT', 'Sistemas', 'Alta'));
    }

    public function test_horas_alerta_temprana_defaults_to_20_percent_of_resolution_hours(): void
    {
        $sla = app(SlaService::class);

        // Alta = 24h -> 20% = 4.8 -> redondeado a 5
        $this->assertSame(5, $sla->horasAlertaTemprana('HEMZT', 'Sistemas', 'Alta'));
    }

    public function test_estado_marks_an_open_ticket_past_its_deadline_as_vencido(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        $ticket = $this->crearTicket($usuario, ['prioridad' => 'Alta']);
        $ticket->forceFill(['created_at' => now()->subHours(25)])->save();

        $sla = app(SlaService::class);
        $estado = $sla->estado($ticket->fresh());

        $this->assertSame('vencido', $estado['estado']);
        $this->assertTrue($estado['horas_restantes'] < 0);
    }

    public function test_estado_marks_a_ticket_close_to_its_deadline_as_por_vencer(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        // Alta = 24h de SLA, alerta a las 5h antes de vencer (20%).
        // Creado hace 22h -> quedan 2h, dentro de la ventana de alerta.
        $ticket = $this->crearTicket($usuario, ['prioridad' => 'Alta']);
        $ticket->forceFill(['created_at' => now()->subHours(22)])->save();

        $sla = app(SlaService::class);
        $estado = $sla->estado($ticket->fresh());

        $this->assertSame('por_vencer', $estado['estado']);
    }

    public function test_estado_marks_a_resolved_ticket_as_cumplido_or_incumplido(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        $aTiempo = $this->crearTicket($usuario, ['prioridad' => 'Media']);
        $aTiempo->forceFill(['created_at' => now()->subHours(10)])->save();
        $aTiempo->forceFill(['status' => 'Resuelto', 'fecha_resolucion' => now()])->save();

        $tarde = $this->crearTicket($usuario, ['prioridad' => 'Media']);
        $tarde->forceFill(['created_at' => now()->subHours(100)])->save();
        $tarde->forceFill(['status' => 'Resuelto', 'fecha_resolucion' => now()])->save();

        $sla = app(SlaService::class);

        $this->assertSame('cumplido', $sla->estado($aTiempo->fresh())['estado']);
        $this->assertSame('incumplido', $sla->estado($tarde->fresh())['estado']);
    }

    public function test_tecnico_only_sees_overdue_tickets_from_their_own_department(): void
    {
        $tecnico    = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        $propio = $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas', 'prioridad' => 'Alta']);
        $propio->forceFill(['created_at' => now()->subHours(25)])->save();

        $ajeno = $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento', 'prioridad' => 'Alta']);
        $ajeno->forceFill(['created_at' => now()->subHours(25)])->save();

        $sla = app(SlaService::class);

        $this->assertSame(1, $sla->ticketsVencidosCount($tecnico));
        $this->assertSame($propio->id, $sla->ticketsVencidos($tecnico)->first()->id);
    }

    public function test_pendientes_de_escalar_excludes_tickets_already_marked(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        $vencido = $this->crearTicket($usuario, ['prioridad' => 'Alta']);
        $vencido->forceFill(['created_at' => now()->subHours(25)])->save();

        $yaEscalado = $this->crearTicket($usuario, ['prioridad' => 'Alta']);
        $yaEscalado->forceFill(['created_at' => now()->subHours(25), 'sla_escalado_at' => now()])->save();

        $sla = app(SlaService::class);
        $ids = $sla->ticketsPendientesDeEscalar()->pluck('id')->all();

        $this->assertContains($vencido->id, $ids);
        $this->assertNotContains($yaEscalado->id, $ids);
    }
}
