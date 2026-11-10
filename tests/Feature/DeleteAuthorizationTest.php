<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Hotel;
use App\Models\InventarioItem;
use App\Models\NetworkDevice;
use App\Models\Ubicacion;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cubre la regla agregada 2026-07-30 (ver App\Policies\Concerns\
 * RestringeEliminacionASupervisor): antes de esto, AssetController,
 * InventoryController, NetworkController, UnidadController,
 * CondominoController, RevisionController y ActivoItemController no tenían
 * NINGUNA verificación de rol en destroy() — solo el middleware de módulo,
 * que no distingue roles. Cualquiera con el módulo activado podía eliminar
 * cualquier registro.
 *
 * delete() en estas Policies solo depende del rol del usuario, no de datos
 * del modelo en sí, así que basta con instancias sin guardar (new Asset(),
 * etc.) para probar tanto la regla como que Laravel resuelve la Policy
 * correcta por convención de nombres (Asset -> AssetPolicy, etc.).
 */
class DeleteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $role): User
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel HEMZT']);

        return User::factory()->create([
            'role'                 => $role,
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);
    }

    public static function modelosProtegidos(): array
    {
        return [
            'Asset'          => [Asset::class],
            'Revision'       => [Revision::class],
            'InventarioItem' => [InventarioItem::class],
            'NetworkDevice'  => [NetworkDevice::class],
            'Ubicacion'      => [Ubicacion::class],
        ];
    }

    #[DataProvider('modelosProtegidos')]
    public function test_admin_and_gerente_can_delete_but_tecnico_and_usuario_cannot(string $modeloClass): void
    {
        $admin   = $this->crearUsuario('admin');
        $gerente = $this->crearUsuario('gerente');
        $tecnico = $this->crearUsuario('tecnico');
        $usuario = $this->crearUsuario('usuario');

        $instancia = new $modeloClass();

        $this->assertTrue($admin->can('delete', $instancia), "{$modeloClass}: admin debería poder eliminar.");
        $this->assertTrue($gerente->can('delete', $instancia), "{$modeloClass}: gerente debería poder eliminar.");
        $this->assertFalse($tecnico->can('delete', $instancia), "{$modeloClass}: tecnico NO debería poder eliminar.");
        $this->assertFalse($usuario->can('delete', $instancia), "{$modeloClass}: usuario NO debería poder eliminar.");
    }
}
