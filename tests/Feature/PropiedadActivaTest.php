<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el selector de "propiedad activa" para el Administrador: antes de
 * esto, ResuelveHotelActivo dejaba al Admin fijo al HOTEL_CODE del .env sin
 * importar cuántas propiedades hubiera dado de alta en Admin > Propiedades
 * (comentario que quedaba en el propio código, sin ninguna pantalla que
 * realmente estableciera session('active_hotel_code')). Ahora sí existe
 * esa pantalla (AdminSettingsController::propiedadActivaUpdate), y la
 * regla vive en Hotel::activoParaUsuario().
 */
class PropiedadActivaTest extends TestCase
{
    use RefreshDatabase;

    private function crearHotel(string $hotelCode, string $nombre = null): Hotel
    {
        return Hotel::firstOrCreate(
            ['hotel_code' => $hotelCode],
            ['nombre' => $nombre ?? "Hotel {$hotelCode}", 'tipo' => 'hotel']
        );
    }

    private function crearUsuario(string $hotelCode = null, string $role = 'usuario', array $extra = []): User
    {
        if ($hotelCode) {
            $this->crearHotel($hotelCode);
        }

        return User::factory()->create(array_merge([
            'role'                 => $role,
            'hotel_code'           => $hotelCode,
            'must_change_password' => false,
        ], $extra));
    }

    public function test_admin_can_switch_the_active_property(): void
    {
        $this->crearHotel('HEMZT', 'Hotel Emporio Mazatlán');
        $this->crearHotel('HECUL', 'Hotel Emporio Culiacán');
        $admin = $this->crearUsuario('HEMZT', 'admin');

        $response = $this->actingAs($admin)->post(route('admin.propiedad-activa.update'), [
            'hotel_code' => 'hecul', // en minúsculas a propósito: debe normalizarse
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('HECUL', session('active_hotel_code'));
        $this->assertSame('HECUL', Hotel::activoParaUsuario($admin->fresh()));
    }

    public function test_non_admin_roles_cannot_switch_the_active_property(): void
    {
        $this->crearHotel('HEMZT');
        $this->crearHotel('HECUL');

        foreach (['usuario', 'tecnico', 'gerente'] as $role) {
            $extra = $role === 'tecnico' ? ['departamento' => 'Sistemas'] : [];
            $user  = $this->crearUsuario('HEMZT', $role, $extra);

            $response = $this->actingAs($user)->post(route('admin.propiedad-activa.update'), [
                'hotel_code' => 'HECUL',
            ]);

            $response->assertForbidden();
        }

        $this->assertNull(session('active_hotel_code'));
    }

    public function test_switching_to_a_nonexistent_hotel_code_is_rejected(): void
    {
        $this->crearHotel('HEMZT');
        $admin = $this->crearUsuario('HEMZT', 'admin');

        $response = $this->actingAs($admin)->post(route('admin.propiedad-activa.update'), [
            'hotel_code' => 'NOEXISTE',
        ]);

        $response->assertSessionHasErrors('hotel_code');
        $this->assertNull(session('active_hotel_code'));
    }

    public function test_admin_without_a_selection_falls_back_to_their_own_hotel_code(): void
    {
        $this->crearHotel('HEMZT');
        $admin = $this->crearUsuario('HEMZT', 'admin');

        // Sin haber usado nunca el selector, el comportamiento debe ser
        // exactamente el de antes: la propiedad asignada al propio Admin.
        $this->assertSame('HEMZT', Hotel::activoParaUsuario($admin));
    }

    public function test_admin_without_own_hotel_and_without_selection_falls_back_to_env_default(): void
    {
        $admin = User::factory()->create([
            'role'                 => 'admin',
            'hotel_code'           => null,
            'must_change_password' => false,
        ]);

        $this->assertSame(strtoupper(env('HOTEL_CODE', 'HEMZT')), Hotel::activoParaUsuario($admin));
    }

    public function test_a_stale_selection_pointing_to_a_deleted_hotel_is_ignored(): void
    {
        $this->crearHotel('HEMZT');
        $admin = $this->crearUsuario('HEMZT', 'admin');

        // Simula que el Admin había elegido una propiedad que después se
        // eliminó desde Admin > Propiedades: no debe romper la pantalla,
        // debe ignorar la selección obsoleta y regresar a su propiedad.
        session(['active_hotel_code' => 'YAOBSOLETO']);

        $this->assertSame('HEMZT', Hotel::activoParaUsuario($admin));
    }

    public function test_regular_users_are_unaffected_by_the_property_selector(): void
    {
        $this->crearHotel('HEMZT');
        $this->crearHotel('HECUL');
        $usuario = $this->crearUsuario('HEMZT', 'usuario');

        // Aunque hipotéticamente hubiera algo en sesión, un usuario no-admin
        // siempre debe quedar fijo a su propia propiedad asignada.
        session(['active_hotel_code' => 'HECUL']);

        $this->assertSame('HEMZT', Hotel::activoParaUsuario($usuario));
    }
}
