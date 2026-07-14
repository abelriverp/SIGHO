<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\InventarioItem;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStockTest extends TestCase
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

        $modulo = Modulo::create(['clave' => 'inventario_suministros', 'nombre' => 'Inventario de Suministros']);
        $usuario->modulos()->attach($modulo->id, ['activado_en' => now()]);

        return $usuario;
    }

    public function test_salida_movement_decreases_stock_and_records_the_movement(): void
    {
        $usuario = $this->crearUsuarioConModulo();

        $item = InventarioItem::create([
            'hotel_code'   => 'HEMZT',
            'user_id'      => $usuario->id,
            'nombre'       => 'Papel higiénico',
            'categoria'    => 'Limpieza',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 10,
            'stock_minimo' => 5,
            'status'       => 'Disponible',
        ]);

        $response = $this->actingAs($usuario)->post(route('inventory.movimiento', $item->id), [
            'tipo'        => 'salida',
            'tipo_salida' => 'consumo_area',
            'cantidad'    => 7,
            'motivo'      => 'Consumo de prueba',
        ]);

        $response->assertRedirect();

        $item->refresh();
        $this->assertEquals(3, $item->cantidad);
        $this->assertEquals('Disponible', $item->status);

        $this->assertDatabaseHas('inventario_movimientos', [
            'inventario_item_id' => $item->id,
            'tipo'               => 'salida',
            'cantidad'           => 7,
            'cantidad_anterior'  => 10,
            'cantidad_nueva'     => 3,
        ]);
    }

    public function test_salida_cannot_exceed_available_stock(): void
    {
        $usuario = $this->crearUsuarioConModulo();

        $item = InventarioItem::create([
            'hotel_code'   => 'HEMZT',
            'user_id'      => $usuario->id,
            'nombre'       => 'Jabón',
            'categoria'    => 'Amenidades',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 5,
            'stock_minimo' => 2,
            'status'       => 'Disponible',
        ]);

        $response = $this->actingAs($usuario)->post(route('inventory.movimiento', $item->id), [
            'tipo'        => 'salida',
            'tipo_salida' => 'consumo_area',
            'cantidad'    => 10,
            'motivo'      => 'Intento de sacar más de lo disponible',
        ]);

        $response->assertSessionHasErrors('cantidad');

        $item->refresh();
        $this->assertEquals(5, $item->cantidad, 'El stock no debe cambiar cuando la salida excede lo disponible.');
    }

    public function test_stock_reaching_zero_marks_item_as_agotado(): void
    {
        $usuario = $this->crearUsuarioConModulo();

        $item = InventarioItem::create([
            'hotel_code'   => 'HEMZT',
            'user_id'      => $usuario->id,
            'nombre'       => 'Shampoo',
            'categoria'    => 'Amenidades',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 3,
            'stock_minimo' => 1,
            'status'       => 'Disponible',
        ]);

        $this->actingAs($usuario)->post(route('inventory.movimiento', $item->id), [
            'tipo'        => 'salida',
            'tipo_salida' => 'consumo_area',
            'cantidad'    => 3,
            'motivo'      => 'Se agota el producto',
        ]);

        $item->refresh();
        $this->assertEquals(0, $item->cantidad);
        $this->assertEquals('Agotado', $item->status);
    }
}
