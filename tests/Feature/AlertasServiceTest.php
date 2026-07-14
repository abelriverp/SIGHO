<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\InventarioItem;
use App\Models\Modulo;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AlertasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre AlertasService: antes de esto, los contadores de stock bajo y de
 * tickets abiertos vivían como DB::table sueltos en el layout, con
 * env('HOTEL_CODE', 'MAZ') hardcodeado -- ignoraban tanto el hotel real del
 * usuario como el umbral de "vencido" por prioridad.
 */
class AlertasServiceTest extends TestCase
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

    public function test_stock_bajo_only_counts_items_from_the_users_own_hotel(): void
    {
        Hotel::create(['hotel_code' => 'TEST2', 'nombre' => 'Otra Propiedad']);

        $usuario = $this->crearUsuario('HEMZT');
        $modulo  = Modulo::create(['clave' => 'inventario_suministros', 'nombre' => 'Inventario']);
        $usuario->modulos()->attach($modulo->id, ['activado_en' => now()]);

        $this->actingAs($usuario);

        InventarioItem::create([
            'hotel_code'   => 'HEMZT',
            'user_id'      => $usuario->id,
            'nombre'       => 'Papel higiénico',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 2,
            'stock_minimo' => 5,
            'status'       => 'Disponible',
            'activo'       => true,
        ]);

        $otro = new InventarioItem([
            'user_id'      => $usuario->id,
            'nombre'       => 'Jabón',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 1,
            'stock_minimo' => 5,
            'status'       => 'Disponible',
            'activo'       => true,
        ]);
        $otro->hotel_code = 'TEST2';
        $otro->save();

        $servicio = app(AlertasService::class);

        $this->assertSame(1, $servicio->stockBajoCount($usuario));
        $this->assertSame('Papel higiénico', $servicio->stockBajo($usuario)->first()->nombre);
    }

    public function test_stock_bajo_is_empty_without_the_inventory_module(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        InventarioItem::create([
            'hotel_code'   => 'HEMZT',
            'user_id'      => $usuario->id,
            'nombre'       => 'Papel higiénico',
            'area'         => 'Ama de Llaves',
            'cantidad'     => 1,
            'stock_minimo' => 5,
            'status'       => 'Disponible',
            'activo'       => true,
        ]);

        $servicio = app(AlertasService::class);

        $this->assertSame(0, $servicio->stockBajoCount($usuario));
    }

    public function test_ticket_alta_prioridad_is_overdue_after_24_hours(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        $vencido = Ticket::create([
            'user_id'    => $usuario->id,
            'area'       => 'Recepción',
            'descripcion'=> 'Falla de prueba',
            'prioridad'  => 'Alta',
            'status'     => 'Abierto',
        ]);
        $vencido->forceFill(['created_at' => now()->subHours(25)])->save();

        $reciente = Ticket::create([
            'user_id'    => $usuario->id,
            'area'       => 'Recepción',
            'descripcion'=> 'Falla reciente',
            'prioridad'  => 'Alta',
            'status'     => 'Abierto',
        ]);
        $reciente->forceFill(['created_at' => now()->subHours(2)])->save();

        $servicio = app(AlertasService::class);
        $ids = $servicio->ticketsVencidos($usuario)->pluck('id')->all();

        $this->assertContains($vencido->id, $ids);
        $this->assertNotContains($reciente->id, $ids);
    }

    public function test_ticket_media_prioridad_is_not_overdue_before_72_hours(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $this->actingAs($usuario);

        $ticket = Ticket::create([
            'user_id'    => $usuario->id,
            'area'       => 'Recepción',
            'descripcion'=> 'Falla de prueba',
            'prioridad'  => 'Media',
            'status'     => 'Abierto',
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(48)])->save();

        $servicio = app(AlertasService::class);

        $this->assertSame(0, $servicio->ticketsVencidosCount($usuario));
    }

    public function test_regular_user_only_sees_their_own_overdue_tickets(): void
    {
        $usuario = $this->crearUsuario('HEMZT');
        $otroUsuario = $this->crearUsuario('HEMZT', 'usuario');

        $this->actingAs($otroUsuario);
        $ajeno = Ticket::create([
            'user_id'    => $otroUsuario->id,
            'area'       => 'Cocina',
            'descripcion'=> 'Falla de otro usuario',
            'prioridad'  => 'Alta',
            'status'     => 'Abierto',
        ]);
        $ajeno->forceFill(['created_at' => now()->subHours(48)])->save();

        $servicio = app(AlertasService::class);

        // La consulta subyacente respeta al usuario ACTUALMENTE autenticado
        // (HotelScope usa auth()->user()), así que hay que alternar el actor
        // para que coincida con el $user que le pasamos al servicio.
        $this->actingAs($usuario);
        $this->assertSame(0, $servicio->ticketsVencidosCount($usuario));

        $this->actingAs($otroUsuario);
        $this->assertSame(1, $servicio->ticketsVencidosCount($otroUsuario));
    }

    public function test_gerente_sees_all_overdue_tickets_regardless_of_reporter(): void
    {
        $gerente = $this->crearUsuario('HEMZT', 'gerente');
        $empleado = $this->crearUsuario('HEMZT', 'usuario');

        $this->actingAs($empleado);
        $ticket = Ticket::create([
            'user_id'    => $empleado->id,
            'area'       => 'Cocina',
            'descripcion'=> 'Falla de prueba',
            'prioridad'  => 'Baja',
            'status'     => 'Abierto',
        ]);
        $ticket->forceFill(['created_at' => now()->subDays(8)])->save();

        $servicio = app(AlertasService::class);

        $this->actingAs($gerente);
        $this->assertSame(1, $servicio->ticketsVencidosCount($gerente));
    }
}
