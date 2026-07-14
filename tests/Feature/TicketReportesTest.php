<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el acceso a /tickets/reportes (corte de KPIs bajo demanda para
 * dirección/gerencia): quién puede entrar y que la descarga en PDF
 * responda correctamente.
 */
class TicketReportesTest extends TestCase
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

    public function test_regular_user_cannot_access_ticket_reports(): void
    {
        $usuario = $this->crearUsuario('HEMZT', 'usuario');

        $response = $this->actingAs($usuario)->get(route('tickets.reportes'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_ticket_reports(): void
    {
        $admin = $this->crearUsuario('HEMZT', 'admin');

        $response = $this->actingAs($admin)->get(route('tickets.reportes'));

        $response->assertOk();
        $response->assertViewIs('tickets.reportes');
    }

    public function test_gerente_can_access_ticket_reports(): void
    {
        $gerente = $this->crearUsuario('HEMZT', 'gerente');

        $response = $this->actingAs($gerente)->get(route('tickets.reportes'));

        $response->assertOk();
    }

    public function test_tecnico_can_access_ticket_reports(): void
    {
        $tecnico = $this->crearUsuario('HEMZT', 'tecnico', ['departamento' => 'Sistemas']);

        $response = $this->actingAs($tecnico)->get(route('tickets.reportes'));

        $response->assertOk();
    }

    public function test_admin_can_download_ticket_reports_as_pdf(): void
    {
        $admin      = $this->crearUsuario('HEMZT', 'admin');
        $reportante = $this->crearUsuario('HEMZT');

        $this->actingAs($reportante);
        Ticket::create([
            'user_id'              => $reportante->id,
            'area'                 => 'Recepción',
            'departamento_destino' => 'Sistemas',
            'descripcion'          => 'Falla de prueba',
            'prioridad'            => 'Media',
            'status'               => 'Abierto',
        ]);

        $response = $this->actingAs($admin)->get(route('tickets.reportes.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_regular_user_cannot_download_ticket_reports_pdf(): void
    {
        $usuario = $this->crearUsuario('HEMZT', 'usuario');

        $response = $this->actingAs($usuario)->get(route('tickets.reportes.pdf'));

        $response->assertForbidden();
    }
}
