<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre CheckModuloAcceso: el control de qué usuario puede entrar a qué
 * módulo (Seguimiento de Equipos, Inventario, etc.) según lo que el
 * Administrador le haya activado.
 */
class ModuloAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_the_module_gets_403(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $usuario = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($usuario)->get(route('assets.index'));

        $response->assertForbidden();
    }

    public function test_user_with_the_module_assigned_can_enter(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $usuario = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $modulo = Modulo::create(['clave' => 'seguimiento_equipos_ti', 'nombre' => 'Seguimiento de Equipos']);
        $usuario->modulos()->attach($modulo->id, ['activado_en' => now()]);

        $response = $this->actingAs($usuario)->get(route('assets.index'));

        $response->assertOk();
    }

    public function test_admin_can_enter_any_module_without_explicit_assignment(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $admin = User::factory()->create([
            'role'                 => 'admin',
            'must_change_password' => false,
        ]);

        // A propósito NO le asignamos el módulo: Admin debe pasar de todos modos.
        $response = $this->actingAs($admin)->get(route('assets.index'));

        $response->assertOk();
    }
}
