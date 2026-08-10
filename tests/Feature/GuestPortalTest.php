<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\InventarioItem;
use App\Models\PuntoQr;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el portal público de huésped (GuestPortalController): acceso sin
 * login vía token impredecible, creación de tickets a partir de un reporte
 * de problema o una solicitud de amenidades, aislamiento multipropiedad, y
 * el rate limit contra spam.
 */
class GuestPortalTest extends TestCase
{
    use RefreshDatabase;

    private function crearPropiedad(string $hotelCode): void
    {
        Hotel::firstOrCreate(['hotel_code' => $hotelCode], ['nombre' => "Hotel {$hotelCode}"]);
    }

    private function crearPunto(string $hotelCode, array $extra = []): PuntoQr
    {
        $this->crearPropiedad($hotelCode);

        return PuntoQr::create(array_merge([
            'hotel_code'    => $hotelCode,
            'identificador' => '204',
            'activo'        => true,
        ], $extra));
    }

    /**
     * inventario_items.user_id es obligatorio (dueño del registro) aunque
     * no tenga nada que ver con quién puede pedirlo como huésped; se crea
     * un usuario de la propiedad solo para satisfacer esa FK.
     */
    private function crearArticulo(string $hotelCode, array $extra = []): InventarioItem
    {
        $this->crearPropiedad($hotelCode);

        $dueño = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => $hotelCode,
            'must_change_password' => false,
        ]);

        return InventarioItem::create(array_merge([
            'hotel_code' => $hotelCode,
            'user_id'    => $dueño->id,
            'area'       => 'Ama de Llaves',
            'cantidad'   => 50,
            'status'     => 'Disponible',
            'activo'     => true,
        ], $extra));
    }

    public function test_an_unknown_token_returns_404_without_requiring_login(): void
    {
        $response = $this->get('/qr/token-que-no-existe');

        $response->assertNotFound();
    }

    public function test_an_inactive_puntos_qr_returns_404(): void
    {
        $punto = $this->crearPunto('HEMZT', ['activo' => false]);

        $response = $this->get('/qr/' . $punto->token);

        $response->assertNotFound();
    }

    public function test_the_form_loads_without_authentication(): void
    {
        $punto = $this->crearPunto('HEMZT');

        $response = $this->get('/qr/' . $punto->token);

        $response->assertOk();
        $response->assertSee('204');
        $this->assertGuest();
    }

    public function test_reporting_a_problem_creates_a_ticket_owned_by_the_guest_system_account(): void
    {
        $punto = $this->crearPunto('HEMZT');
        Departamento::firstOrCreate(['nombre' => 'Mantenimiento'], ['activo' => true]);

        $response = $this->post('/qr/' . $punto->token, [
            'tipo_solicitud' => 'problema',
            'nombre_huesped' => 'Juan Pérez',
            'descripcion'    => 'El aire acondicionado no enfría.',
        ]);

        $response->assertRedirect(route('guest.gracias', $punto->token));
        $this->assertGuest();

        $ticket = Ticket::withoutGlobalScopes()->latest('id')->first();

        $this->assertNotNull($ticket);
        $this->assertSame('HEMZT', $ticket->hotel_code);
        $this->assertSame('Mantenimiento', $ticket->departamento_destino);
        $this->assertSame('Abierto', $ticket->status);
        $this->assertStringContainsString('204', $ticket->area);
        $this->assertSame('El aire acondicionado no enfría.', $ticket->descripcion);

        $duenio = User::find($ticket->user_id);
        $this->assertSame('usuario', $duenio->role);
        $this->assertSame('HEMZT', $duenio->hotel_code);
    }

    public function test_requesting_amenities_only_includes_items_flagged_as_guest_available(): void
    {
        $punto = $this->crearPunto('HEMZT');
        Departamento::firstOrCreate(['nombre' => 'Ama de Llaves'], ['activo' => true]);

        $toallas = $this->crearArticulo('HEMZT', ['nombre' => 'Toallas', 'disponible_huesped' => true]);
        $quimico = $this->crearArticulo('HEMZT', ['nombre' => 'Cloro industrial', 'disponible_huesped' => false]); // no debe poder pedirse

        $response = $this->post('/qr/' . $punto->token, [
            'tipo_solicitud' => 'amenidad',
            'articulos'      => [
                $toallas->id => 2,
                $quimico->id => 3,
            ],
        ]);

        $response->assertRedirect(route('guest.gracias', $punto->token));

        $ticket = Ticket::withoutGlobalScopes()->latest('id')->first();

        $this->assertNotNull($ticket);
        $this->assertSame('Ama de Llaves', $ticket->departamento_destino);
        $this->assertStringContainsString('Toallas', $ticket->descripcion);
        $this->assertStringNotContainsString('Cloro', $ticket->descripcion);

        $articulosGuardados = $ticket->campos_extra['articulos'] ?? [];
        $this->assertCount(1, $articulosGuardados);
        $this->assertSame('Toallas', $articulosGuardados[0]['nombre']);
        $this->assertSame(2, $articulosGuardados[0]['cantidad']);
    }

    public function test_amenity_quantity_is_capped_regardless_of_what_is_submitted(): void
    {
        $punto = $this->crearPunto('HEMZT');

        $toallas = $this->crearArticulo('HEMZT', ['nombre' => 'Toallas', 'disponible_huesped' => true]);

        // La validación ya rechaza más de 5; esto confirma que el valor
        // máximo permitido llega intacto hasta el campos_extra del ticket.
        $response = $this->post('/qr/' . $punto->token, [
            'tipo_solicitud' => 'amenidad',
            'articulos'      => [$toallas->id => 5],
        ]);

        $response->assertRedirect(route('guest.gracias', $punto->token));

        $ticket = Ticket::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame(5, $ticket->campos_extra['articulos'][0]['cantidad']);
    }

    public function test_guest_requests_do_not_leak_inventory_items_from_another_property(): void
    {
        $puntoA = $this->crearPunto('HEMZT');
        $this->crearArticulo('OTRA', ['nombre' => 'Almohadas extra', 'disponible_huesped' => true]);

        $response = $this->get('/qr/' . $puntoA->token);

        $response->assertOk();
        $response->assertDontSee('Almohadas extra');
    }

    public function test_falls_back_to_another_active_department_when_the_preferred_one_is_missing(): void
    {
        $punto = $this->crearPunto('HEMZT');

        // Ningún departamento se llama "Mantenimiento" en este escenario;
        // solo existe uno activo con otro nombre.
        Departamento::query()->delete();
        Departamento::create(['nombre' => 'Recepción', 'activo' => true]);

        $response = $this->post('/qr/' . $punto->token, [
            'tipo_solicitud' => 'problema',
            'descripcion'    => 'Falla de prueba',
        ]);

        $response->assertRedirect(route('guest.gracias', $punto->token));

        $ticket = Ticket::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame('Recepción', $ticket->departamento_destino);
    }

    public function test_guest_requests_are_throttled_per_token(): void
    {
        $punto = $this->crearPunto('HEMZT');
        Departamento::firstOrCreate(['nombre' => 'Mantenimiento'], ['activo' => true]);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/qr/' . $punto->token, [
                'tipo_solicitud' => 'problema',
                'descripcion'    => "Reporte {$i}",
            ]);
        }

        $response = $this->post('/qr/' . $punto->token, [
            'tipo_solicitud' => 'problema',
            'descripcion'    => 'Reporte de más',
        ]);

        $response->assertSessionHasErrors('tipo_solicitud');
    }
}
