<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_writes_an_audit_log_entry(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $admin = User::factory()->create([
            'role'                 => 'admin',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'       => 'Empleado de Prueba',
            'email'      => 'empleado.prueba@example.com',
            'password'   => 'password123',
            'role'       => 'usuario',
            'hotel_code' => 'HEMZT',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('audit_logs', [
            'accion'  => 'usuario.creado',
            'user_id' => $admin->id,
        ]);
    }

    public function test_deleting_a_user_logs_the_previous_data(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $admin = User::factory()->create([
            'role'                 => 'admin',
            'must_change_password' => false,
        ]);

        $victima = User::factory()->create([
            'name'                 => 'Por Eliminar',
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $victima));

        $this->assertDatabaseMissing('users', ['id' => $victima->id]);

        $log = AuditLog::where('accion', 'usuario.eliminado')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('Por Eliminar', $log->datos_anteriores['name']);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_changing_a_users_role_records_before_and_after_values(): void
    {
        Hotel::firstOrCreate(['hotel_code' => 'HEMZT'], ['nombre' => 'Hotel Emporio Mazatlán']);

        $admin = User::factory()->create([
            'role'                 => 'admin',
            'must_change_password' => false,
        ]);

        $empleado = User::factory()->create([
            'role'                 => 'usuario',
            'hotel_code'           => 'HEMZT',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $empleado), [
            'name'       => $empleado->name,
            'email'      => $empleado->email,
            'role'       => 'tecnico',
            'departamento' => 'Sistemas',
            'hotel_code' => 'HEMZT',
        ]);

        $log = AuditLog::where('accion', 'usuario.actualizado')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('usuario', $log->datos_anteriores['role']);
        $this->assertSame('tecnico', $log->datos_nuevos['role']);
    }
}
