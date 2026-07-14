<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Hotel;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la función de "mover en lote" del módulo de Periféricos: seleccionar
 * varios equipos y cambiarles Área y/o División de un jalón.
 */
class AssetMoverLoteTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioConModulo(): User
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $usuario = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $modulo = Modulo::create(['clave' => 'seguimiento_equipos_ti', 'nombre' => 'Seguimiento de Equipos']);
        $usuario->modulos()->attach($modulo->id, ['activado_en' => now()]);

        return $usuario;
    }

    public function test_mover_lote_updates_area_for_all_selected_assets(): void
    {
        $usuario = $this->crearUsuarioConModulo();
        $this->actingAs($usuario);

        $asset1 = Asset::create(['nombre' => 'PC1', 'nomenclatura' => 'PC-001', 'area' => 'Recepción']);
        $asset2 = Asset::create(['nombre' => 'PC2', 'nomenclatura' => 'PC-002', 'area' => 'Recepción']);
        $sinTocar = Asset::create(['nombre' => 'PC3', 'nomenclatura' => 'PC-003', 'area' => 'Recepción']);

        $response = $this->postJson(route('assets.moverLote'), [
            'ids'  => [$asset1->id, $asset2->id],
            'area' => 'Contraloría',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true, 'actualizados' => 2]);

        $this->assertEquals('Contraloría', $asset1->fresh()->area);
        $this->assertEquals('Contraloría', $asset2->fresh()->area);
        $this->assertEquals('Recepción', $sinTocar->fresh()->area, 'Un equipo no seleccionado no debe cambiar.');
    }

    public function test_mover_lote_can_update_division_and_area_together(): void
    {
        $usuario = $this->crearUsuarioConModulo();
        $this->actingAs($usuario);

        $asset = Asset::create(['nombre' => 'PC1', 'nomenclatura' => 'PC-010', 'area' => 'Recepción']);

        $response = $this->postJson(route('assets.moverLote'), [
            'ids'      => [$asset->id],
            'area'     => 'Caja 2',
            'division' => 'Contraloría',
        ]);

        $response->assertOk();

        $asset->refresh();
        $this->assertEquals('Caja 2', $asset->area);
        $this->assertEquals('Contraloría', $asset->division);
    }

    public function test_mover_lote_requires_at_least_one_target_field(): void
    {
        $usuario = $this->crearUsuarioConModulo();
        $this->actingAs($usuario);

        $asset = Asset::create(['nombre' => 'PC1', 'nomenclatura' => 'PC-020', 'area' => 'Recepción']);

        $response = $this->postJson(route('assets.moverLote'), [
            'ids' => [$asset->id],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['ok' => false]);

        $this->assertEquals('Recepción', $asset->fresh()->area, 'Sin campos de destino, nada debe cambiar.');
    }
}
