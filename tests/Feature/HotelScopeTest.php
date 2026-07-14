<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el hueco de seguridad original: antes, cualquier usuario veía el
 * inventario de TODAS las propiedades. Estas pruebas validan que HotelScope
 * (app/Models/Scopes/HotelScope.php) filtre correctamente por defecto.
 */
class HotelScopeTest extends TestCase
{
    use RefreshDatabase;

    private function crearAssetParaHotel(string $hotelCode, string $nomenclatura): Asset
    {
        $asset = new Asset([
            'nombre'       => $nomenclatura,
            'nomenclatura' => $nomenclatura,
            'area'         => 'Área de prueba',
        ]);
        // Asset::$fillable no incluye hotel_code a propósito (se asigna solo
        // vía el trait BelongsToHotel); lo forzamos aquí para armar el
        // escenario de prueba con dos propiedades distintas.
        $asset->hotel_code = $hotelCode;
        $asset->save();

        return $asset;
    }

    public function test_regular_user_only_sees_assets_from_their_own_hotel(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);
        Hotel::create(['hotel_code' => 'TEST2', 'nombre' => 'Otra Propiedad']);

        $this->crearAssetParaHotel('HEMZT', 'PC-HEMZT-01');
        $this->crearAssetParaHotel('TEST2', 'PC-TEST2-01');

        $usuario = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $this->actingAs($usuario);
        $visibles = Asset::pluck('nomenclatura')->all();

        $this->assertContains('PC-HEMZT-01', $visibles);
        $this->assertNotContains('PC-TEST2-01', $visibles);
    }

    public function test_admin_sees_assets_from_every_property(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);
        Hotel::create(['hotel_code' => 'TEST2', 'nombre' => 'Otra Propiedad']);

        $this->crearAssetParaHotel('HEMZT', 'PC-HEMZT-01');
        $this->crearAssetParaHotel('TEST2', 'PC-TEST2-01');

        $admin = User::factory()->create([
            'role'                 => 'admin',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin);
        $visibles = Asset::pluck('nomenclatura')->all();

        $this->assertContains('PC-HEMZT-01', $visibles);
        $this->assertContains('PC-TEST2-01', $visibles);
    }

    public function test_creating_an_asset_auto_assigns_the_current_users_hotel(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $usuario = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $this->actingAs($usuario);

        Asset::create([
            'nombre'       => 'PC Automático',
            'nomenclatura' => 'PC-AUTO-01',
            'area'         => 'Recepción',
        ]);

        $creado = Asset::where('nomenclatura', 'PC-AUTO-01')->first();

        $this->assertNotNull($creado);
        $this->assertSame('HEMZT', $creado->hotel_code);
    }
}
