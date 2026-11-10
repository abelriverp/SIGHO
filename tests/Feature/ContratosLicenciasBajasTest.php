<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\BajaActivo;
use App\Models\Contrato;
use App\Models\Hotel;
use App\Models\Licencia;
use App\Models\Modulo;
use App\Models\Proveedor;
use App\Models\User;
use App\Notifications\VencimientosProximosNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Fase 3 (nov 2026): proveedores, contratos, licencias, vencimientos, responsivas y bajas. */
class ContratosLicenciasBajasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
    }

    private function usuario(string $role = 'tecnico'): User
    {
        $u = User::factory()->create(['role' => $role, 'hotel_code' => 'HECUN', 'must_change_password' => false, 'departamento' => 'Sistemas']);
        $m = Modulo::firstOrCreate(['clave' => 'seguimiento_equipos_ti'], ['nombre' => 'Activos', 'activo' => true]);
        $u->modulos()->syncWithoutDetaching([$m->id => ['activado_en' => now()]]);
        return $u;
    }

    public function test_contrato_con_equipos_cubiertos_y_estado_de_vigencia(): void
    {
        $this->actingAs($this->usuario());
        $prov = Proveedor::create(['nombre' => 'Single Digits']);
        $sw = Asset::create(['nomenclatura' => 'SW-CORE', 'nombre' => 'Switch', 'area' => 'Site']);

        $this->post(route('contratos.store'), [
            'nombre' => 'Enlace 200 Mbps', 'tipo' => 'enlace', 'proveedor_id' => $prov->id, 'moneda' => 'MXN',
            'fecha_inicio' => now()->subYear()->toDateString(), 'fecha_fin' => now()->addDays(20)->toDateString(),
            'dias_aviso' => 30, 'costo' => 5000, 'periodicidad_pago' => 'mensual', 'activos' => [$sw->id],
        ])->assertRedirect();

        $c = Contrato::first();
        $this->assertSame('por_vencer', $c->estadoVigencia());
        $this->assertSame([$sw->id], $c->activos()->pluck('assets.id')->all());
        $this->get(route('contratos.index'))->assertOk()->assertSee('60,000.00');
        $this->get(route('assets.show', $sw->id))->assertOk()->assertSee('Enlace 200 Mbps');
    }

    public function test_usuario_sin_rol_tecnico_no_captura_contratos(): void
    {
        $this->actingAs($this->usuario('usuario'));
        $this->get(route('contratos.index'))->assertOk();
        $this->get(route('contratos.create'))->assertForbidden();
    }

    public function test_licencias_marcan_exceso_de_instalaciones(): void
    {
        $this->actingAs($this->usuario());
        $a = Asset::create(['nomenclatura' => 'PC-1', 'nombre' => 'PC', 'area' => 'x']);
        $b = Asset::create(['nomenclatura' => 'PC-2', 'nombre' => 'PC', 'area' => 'x']);

        $this->post(route('licencias.store'), ['software' => 'Office', 'tipo' => 'perpetua', 'cantidad' => 1, 'activos' => [$a->id, $b->id]])->assertRedirect();

        $this->assertSame(2, Licencia::first()->activos()->count());
        $this->get(route('licencias.index'))->assertOk()->assertSee('faltan 1');
    }

    public function test_vencimientos_junta_contratos_licencias_y_garantias(): void
    {
        $this->actingAs($this->usuario());
        Contrato::create(['nombre' => 'Póliza BMS', 'tipo' => 'poliza', 'fecha_fin' => now()->addDays(10), 'dias_aviso' => 30, 'activo' => true]);
        Licencia::create(['software' => 'Antivirus', 'tipo' => 'suscripcion', 'cantidad' => 10, 'fecha_vencimiento' => now()->addDays(40)]);
        Asset::create(['nomenclatura' => 'AP-LOBBY', 'nombre' => 'AP', 'area' => 'x', 'garantia_hasta' => now()->addDays(5)]);

        $this->get(route('vencimientos.index'))->assertOk()->assertSee('Póliza BMS')->assertSee('Antivirus')->assertSee('AP-LOBBY');
    }

    public function test_alerta_solo_en_dias_de_aviso(): void
    {
        Notification::fake();
        $gerente = $this->usuario('gerente');
        Contrato::create(['hotel_code' => 'HECUN', 'nombre' => 'A 15 días', 'tipo' => 'soporte', 'fecha_fin' => now()->addDays(15), 'dias_aviso' => 30, 'activo' => true]);
        Contrato::create(['hotel_code' => 'HECUN', 'nombre' => 'A 12 días', 'tipo' => 'soporte', 'fecha_fin' => now()->addDays(12), 'dias_aviso' => 30, 'activo' => true]);

        $this->artisan('sigho:alertas-vencimiento')->assertSuccessful();

        Notification::assertSentTo($gerente, VencimientosProximosNotification::class, function ($n) {
            return $n->items->count() === 1 && $n->items->first()['nombre'] === 'A 15 días';
        });
    }

    public function test_tecnico_no_da_de_baja_directo_y_flujo_de_aprobacion(): void
    {
        $tec = $this->usuario('tecnico');
        $this->actingAs($tec);
        $tv = Asset::create(['nomenclatura' => 'TV-1205', 'nombre' => 'TV', 'area' => 'x', 'status' => 'Activo']);

        $this->put(route('assets.update', $tv->id), ['nomenclatura' => 'TV-1205', 'categoria' => 'TV y entretenimiento', 'status' => 'Baja'])
            ->assertSessionHasErrors('status');

        $this->post(route('bajas.store', $tv->id), ['motivo' => 'Panel roto, sin reparación', 'destino' => 'reciclaje'])->assertRedirect();
        $baja = BajaActivo::first();
        $this->assertSame('pendiente', $baja->estado);
        $this->post(route('bajas.aprobar', $baja))->assertForbidden();

        $this->post(route('bajas.store', $tv->id), ['motivo' => 'Otra vez la misma baja'])->assertSessionHas('error');

        $this->actingAs($this->usuario('gerente'));
        $this->post(route('bajas.aprobar', $baja), ['comentario_revision' => 'Ok'])->assertSessionHas('success');

        $this->assertSame('aprobada', $baja->fresh()->estado);
        $this->assertSame('Baja', $tv->fresh()->status);
        $this->get(route('bajas.acta', $baja))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_rechazar_baja_exige_comentario_y_no_cambia_el_activo(): void
    {
        $this->actingAs($this->usuario('tecnico'));
        $pc = Asset::create(['nomenclatura' => 'PC-9', 'nombre' => 'PC', 'area' => 'x', 'status' => 'Activo']);
        $this->post(route('bajas.store', $pc->id), ['motivo' => 'Lento, quiero otro']);
        $baja = BajaActivo::first();

        $this->actingAs($this->usuario('gerente'));
        $this->post(route('bajas.rechazar', $baja), [])->assertSessionHasErrors('comentario_revision');
        $this->post(route('bajas.rechazar', $baja), ['comentario_revision' => 'Se puede ampliar RAM'])->assertSessionHas('success');

        $this->assertSame('rechazada', $baja->fresh()->estado);
        $this->assertSame('Activo', $pc->fresh()->status);
    }

    public function test_carta_responsiva_en_pdf(): void
    {
        $tec = $this->usuario('tecnico');
        $this->actingAs($tec);
        $recepcionista = User::factory()->create(['role' => 'usuario', 'hotel_code' => 'HECUN']);
        $pc = Asset::create(['nomenclatura' => 'PC-REC', 'nombre' => 'PC', 'area' => 'x', 'asignado_a' => $recepcionista->id]);
        $sin = Asset::create(['nomenclatura' => 'PC-SIN', 'nombre' => 'PC', 'area' => 'x']);

        $this->get(route('assets.responsiva', ['id' => $pc->id, 'todos' => 1]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('assets.responsiva', $sin->id))->assertSessionHas('error');
    }

    public function test_pantallas_de_fase3_cargan(): void
    {
        $this->actingAs($this->usuario('admin'));
        $p = Proveedor::create(['nombre' => 'Rowan']);
        $c = Contrato::create(['nombre' => 'BMS', 'tipo' => 'soporte', 'dias_aviso' => 30, 'proveedor_id' => $p->id]);
        $l = Licencia::create(['software' => 'Opera', 'tipo' => 'suscripcion', 'cantidad' => 5]);
        foreach ([route('proveedores.index'), route('contratos.index'), route('contratos.create'), route('contratos.show', $c), route('contratos.edit', $c),
                  route('licencias.index'), route('licencias.create'), route('licencias.edit', $l), route('vencimientos.index'), route('bajas.index'),
                  route('assets.create')] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
