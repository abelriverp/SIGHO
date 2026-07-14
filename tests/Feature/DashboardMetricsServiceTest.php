<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre DashboardMetricsService: las gráficas nuevas del Dashboard deben
 * respetar el mismo criterio de visibilidad por rol que ya usan
 * AlertasService y TicketController (admin/gerente ven todo; técnico solo
 * su departamento; usuario solo lo suyo), y no filtrarse entre propiedades.
 */
class DashboardMetricsServiceTest extends TestCase
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

    public function test_regular_user_only_sees_their_own_tickets_in_the_charts(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $otro    = $this->crearUsuario('HEMZT');

        $this->actingAs($usuario);
        $this->crearTicket($usuario);

        $this->actingAs($otro);
        $this->crearTicket($otro);

        $servicio = app(DashboardMetricsService::class);
        $metricas = $servicio->metricas($usuario, 'HEMZT');

        $this->assertSame(1, $metricas['total']);
    }

    public function test_tecnico_only_sees_tickets_from_their_own_departamento(): void
    {
        $tecnico  = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas']);
        $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento']);

        $servicio = app(DashboardMetricsService::class);
        $metricas = $servicio->metricas($tecnico, 'HEMZT');

        $this->assertSame(1, $metricas['total']);
        $this->assertSame(['Abierto' => 1], $metricas['ticketsPorEstado']);
    }

    public function test_admin_sees_every_ticket_of_the_property(): void
    {
        $admin      = $this->crearUsuario('HEMZT', 'admin');
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas']);
        $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento']);

        $servicio = app(DashboardMetricsService::class);
        $metricas = $servicio->metricas($admin, 'HEMZT');

        $this->assertSame(2, $metricas['total']);
    }

    public function test_charts_do_not_leak_tickets_from_another_property(): void
    {
        $admin = $this->crearUsuario('HEMZT', 'admin');
        $otraPropiedad = $this->crearUsuario('TEST2', 'usuario');

        $this->actingAs($otraPropiedad);
        $this->crearTicket($otraPropiedad);

        $servicio = app(DashboardMetricsService::class);
        $metricas = $servicio->metricas($admin, 'HEMZT');

        $this->assertSame(0, $metricas['total']);
    }

    public function test_ranking_tecnicos_is_only_populated_for_roles_that_can_see_everything(): void
    {
        $admin      = $this->crearUsuario('HEMZT', 'admin');
        $tecnico    = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas', 'name' => 'Juan Técnico']);
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        $ticket = $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas']);
        $ticket->forceFill([
            'status'           => 'Resuelto',
            'resolved_by'      => $tecnico->id,
            'fecha_resolucion' => now(),
        ])->save();

        $servicio = app(DashboardMetricsService::class);

        $metricasAdmin = $servicio->metricas($admin, 'HEMZT');
        $this->assertCount(1, $metricasAdmin['rankingTecnicos']);
        $this->assertSame('Juan Técnico', $metricasAdmin['rankingTecnicos'][0]['nombre']);
        $this->assertSame(1, $metricasAdmin['rankingTecnicos'][0]['total']);

        $metricasTecnico = $servicio->metricas($tecnico, 'HEMZT');
        $this->assertSame([], $metricasTecnico['rankingTecnicos']);
    }

    public function test_tiempo_por_departamento_averages_hours_between_creation_and_resolution(): void
    {
        $admin      = $this->crearUsuario('HEMZT', 'admin');
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        $ticket = $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas']);
        $ticket->forceFill(['created_at' => now()->subHours(10)])->save();
        $ticket->forceFill([
            'status'           => 'Resuelto',
            'fecha_resolucion' => now(),
        ])->save();

        $servicio = app(DashboardMetricsService::class);
        $metricas = $servicio->metricas($admin, 'HEMZT');

        $this->assertCount(1, $metricas['tiempoPorDepartamento']);
        $this->assertSame('Sistemas', $metricas['tiempoPorDepartamento'][0]['departamento']);
        $this->assertEqualsWithDelta(10.0, $metricas['tiempoPorDepartamento'][0]['horas_promedio'], 0.1);
    }
}
