<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre TicketPolicy directamente (sin pasar por HTTP): la regla de "quién
 * puede ver qué ticket" y "quién puede pedir reportes" vivía duplicada en
 * TicketController::show(), downloadPDF(), el buscador de token de index()
 * y reportes()/reportesPdf(). Ahora vive en un solo lugar; estos tests
 * documentan el comportamiento esperado para que un cambio futuro que la
 * rompa se note aquí primero. Los tests HTTP existentes (TicketReportesTest,
 * y los assertForbidden/assertOk dentro de las vistas de tickets) siguen
 * pasando porque el controller solo delegó la misma lógica a $user->can().
 */
class TicketPolicyTest extends TestCase
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

    // ── view() ──────────────────────────────────────────────

    public function test_admin_can_view_any_ticket_in_the_property(): void
    {
        $admin      = $this->crearUsuario('HEMZT', 'admin');
        $reportante = $this->crearUsuario('HEMZT');
        $ticket     = $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento']);

        $this->assertTrue($admin->can('view', $ticket));
    }

    public function test_gerente_can_view_any_ticket_in_the_property(): void
    {
        $gerente    = $this->crearUsuario('HEMZT', 'gerente');
        $reportante = $this->crearUsuario('HEMZT');
        $ticket     = $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento']);

        $this->assertTrue($gerente->can('view', $ticket));
    }

    public function test_tecnico_can_view_a_ticket_from_their_own_departamento(): void
    {
        $tecnico    = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $reportante = $this->crearUsuario('HEMZT');
        $ticket     = $this->crearTicket($reportante, ['departamento_destino' => 'Sistemas']);

        $this->assertTrue($tecnico->can('view', $ticket));
    }

    public function test_tecnico_cannot_view_a_ticket_from_another_departamento(): void
    {
        $tecnico    = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $reportante = $this->crearUsuario('HEMZT');
        $ticket     = $this->crearTicket($reportante, ['departamento_destino' => 'Mantenimiento']);

        $this->assertFalse($tecnico->can('view', $ticket));
    }

    public function test_tecnico_can_view_a_ticket_they_reported_even_outside_their_departamento(): void
    {
        $tecnico = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $ticket  = $this->crearTicket($tecnico, ['departamento_destino' => 'Mantenimiento']);

        $this->assertTrue($tecnico->can('view', $ticket));
    }

    public function test_regular_user_can_only_view_their_own_ticket(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $otro    = $this->crearUsuario('HEMZT');

        $suyo    = $this->crearTicket($usuario);
        $ajeno   = $this->crearTicket($otro);

        $this->assertTrue($usuario->can('view', $suyo));
        $this->assertFalse($usuario->can('view', $ajeno));
    }

    // ── viewReportes() ──────────────────────────────────────

    public function test_admin_gerente_and_tecnico_can_view_reportes_but_regular_user_cannot(): void
    {
        $admin   = $this->crearUsuario('HEMZT', 'admin');
        $gerente = $this->crearUsuario('HEMZT', 'gerente');
        $tecnico = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $usuario = $this->crearUsuario('HEMZT', 'usuario');

        $this->assertTrue($admin->can('viewReportes', Ticket::class));
        $this->assertTrue($gerente->can('viewReportes', Ticket::class));
        $this->assertTrue($tecnico->can('viewReportes', Ticket::class));
        $this->assertFalse($usuario->can('viewReportes', Ticket::class));
    }

    // ── delete() ─────────────────────────────────────────────

    public function test_only_admin_can_delete_tickets(): void
    {
        $admin   = $this->crearUsuario('HEMZT', 'admin');
        $gerente = $this->crearUsuario('HEMZT', 'gerente');
        $tecnico = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);
        $usuario = $this->crearUsuario('HEMZT', 'usuario');

        $this->assertTrue($admin->can('delete', Ticket::class));
        $this->assertFalse($gerente->can('delete', Ticket::class));
        $this->assertFalse($tecnico->can('delete', Ticket::class));
        $this->assertFalse($usuario->can('delete', Ticket::class));
    }
}
