<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Contrato;
use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\Modulo;
use App\Models\NetworkDevice;
use App\Models\OcupacionMensual;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\Ubicacion;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Fase 6 (nov 2026): lo que quedaba pendiente del análisis. */
class PendientesFase6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
        Departamento::firstOrCreate(['nombre' => 'Sistemas'], ['activo' => true]);
    }

    private function usuario(string $role = 'tecnico'): User
    {
        $u = User::factory()->create(['role' => $role, 'hotel_code' => 'HECUN', 'must_change_password' => false, 'departamento' => 'Sistemas']);
        foreach (['red_infraestructura', 'seguimiento_equipos_ti'] as $clave) {
            $m = Modulo::firstOrCreate(['clave' => $clave], ['nombre' => $clave, 'activo' => true]);
            $u->modulos()->syncWithoutDetaching([$m->id => ['activado_en' => now()]]);
        }
        return $u;
    }

    private function ticket(User $u, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'hotel_code' => 'HECUN', 'user_id' => $u->id, 'area' => 'Recepción', 'departamento_destino' => 'Sistemas',
            'descripcion' => 'Sin internet', 'prioridad' => 'Alta', 'status' => 'Abierto',
        ], $extra));
    }

    // ---------------- Primera respuesta ----------------

    public function test_primera_nota_de_soporte_marca_la_primera_respuesta(): void
    {
        $usuario = $this->usuario('usuario');
        $tec = $this->usuario();
        $t = $this->ticket($usuario);

        TicketNota::create(['ticket_id' => $t->id, 'user_id' => $usuario->id, 'nota' => 'sigue igual', 'tipo' => 'publica']);
        TicketNota::create(['ticket_id' => $t->id, 'user_id' => $tec->id, 'nota' => 'auto', 'tipo' => 'publica', 'es_automatica' => true]);
        $this->assertNull($t->fresh()->primera_respuesta_at, 'Ni el reportante ni las notas automáticas cuentan.');

        $this->travel(20)->minutes();
        TicketNota::create(['ticket_id' => $t->id, 'user_id' => $tec->id, 'nota' => 'Voy en camino', 'tipo' => 'publica']);
        $primera = $t->fresh()->primera_respuesta_at;
        $this->assertNotNull($primera);

        $this->travel(30)->minutes();
        TicketNota::create(['ticket_id' => $t->id, 'user_id' => $tec->id, 'nota' => 'otra', 'tipo' => 'publica']);
        $this->assertEquals($primera, $t->fresh()->primera_respuesta_at, 'Solo cuenta la primera.');

        $cal = app(DashboardMetricsService::class)->metricasPeriodo($tec, 'HECUN', now()->subDay(), now())['calidad'];
        $this->assertSame(20, $cal['primeraRespuestaMin']);
        $this->assertSame(100.0, (float) $cal['primeraRespuesta30Pct']);
    }

    public function test_resolver_sin_respuesta_previa_cuenta_como_primera_respuesta(): void
    {
        $t = $this->ticket($this->usuario('usuario'));
        $t->update(['status' => 'Resuelto', 'fecha_resolucion' => now()]);
        $this->assertNotNull($t->fresh()->primera_respuesta_at);
        $this->assertSame('3 h 10 min', DashboardMetricsService::duracion(190));
        $this->assertSame('2 d 4 h', DashboardMetricsService::duracion(2 * 1440 + 240));
    }

    // ---------------- Padre / hijo ----------------

    public function test_agrupar_y_resolver_el_padre_resuelve_los_hijos(): void
    {
        $tec = $this->usuario();
        $u1 = $this->usuario('usuario');
        $u2 = $this->usuario('usuario');
        $padre = $this->ticket($u1);
        $hijo = $this->ticket($u2);
        $otro = $this->ticket($u2);

        $this->actingAs($tec)->post(route('tickets.agrupar', $hijo->id), ['ticket_padre_id' => $padre->id])->assertSessionHas('success');
        $this->assertSame($padre->id, $hijo->fresh()->ticket_padre_id);

        // No se anida ni se agrupa bajo sí mismo.
        $this->post(route('tickets.agrupar', $otro->id), ['ticket_padre_id' => $hijo->id])->assertSessionHas('error');
        $this->post(route('tickets.agrupar', $padre->id), ['ticket_padre_id' => $otro->id])->assertSessionHas('error');
        $this->post(route('tickets.agrupar', $otro->id), ['ticket_padre_id' => $otro->id])->assertSessionHas('error');

        $this->get(route('tickets.show', $padre->id))->assertOk()->assertSee('Agrupa 1 reporte(s)');
        $this->get(route('tickets.show', $hijo->id))->assertOk()->assertSee('Agrupado bajo #' . $padre->id);

        $this->patch(route('tickets.resolve', $padre->id), ['notas_admin' => 'Se reinició el enlace'])->assertRedirect();
        $h = $hijo->fresh();
        $this->assertSame('Resuelto', $h->status);
        $this->assertStringContainsString('Se reinició el enlace', $h->notas_admin);
        $this->assertNotNull($h->token_resolucion);
        $this->assertSame('Abierto', $otro->fresh()->status);
    }

    public function test_desagrupar_y_usuario_normal_no_agrupa(): void
    {
        $tec = $this->usuario();
        $u = $this->usuario('usuario');
        $padre = $this->ticket($u);
        $hijo = $this->ticket($u, ['ticket_padre_id' => $padre->id]);

        $this->actingAs($u)->post(route('tickets.agrupar', $padre->id), ['ticket_padre_id' => $hijo->id])->assertForbidden();
        $this->actingAs($tec)->delete(route('tickets.desagrupar', $hijo->id))->assertSessionHas('success');
        $this->assertNull($hijo->fresh()->ticket_padre_id);
    }

    // ---------------- Escalar a proveedor ----------------

    public function test_escalar_ticket_a_proveedor(): void
    {
        $tec = $this->usuario();
        $prov = Proveedor::create(['hotel_code' => 'HECUN', 'nombre' => 'Single Digits', 'telefono' => '998 000 0000', 'activo' => true]);
        $t = $this->ticket($this->usuario('usuario'));

        $this->actingAs($tec)->post(route('tickets.escalar', $t->id), ['proveedor_id' => $prov->id, 'folio_proveedor' => 'SD-123'])->assertSessionHas('success');
        $t->refresh();
        $this->assertSame($prov->id, $t->proveedor_id);
        $this->assertSame('SD-123', $t->folio_proveedor);
        $this->assertNotNull($t->escalado_proveedor_at);

        $this->get(route('tickets.show', $t->id))->assertOk()->assertSee('Escalado a')->assertSee('998 000 0000')->assertSee('En espera de proveedor');
        $this->get(route('tickets.index'))->assertOk()->assertSee('Proveedor');
        $this->get(route('proveedores.index'))->assertOk()->assertSee('1 ticket(s) escalado(s) abierto(s)');

        $this->delete(route('tickets.escalar.quitar', $t->id))->assertSessionHas('success');
        $this->assertNull($t->fresh()->proveedor_id);
        $this->post(route('tickets.escalar', $t->id), ['proveedor_id' => 9999])->assertSessionHas('error');
    }

    // ---------------- Etiquetas QR y reportar falla ----------------

    public function test_etiquetas_qr_y_reportar_falla_desde_el_activo(): void
    {
        $tec = $this->usuario();
        $u = Ubicacion::create(['hotel_code' => 'HECUN', 'tipo' => 'oficina', 'codigo' => 'ADM-VENTAS', 'nombre' => 'Ventas']);
        $a = (new Asset)->forceFill(['hotel_code' => 'HECUN', 'nomenclatura' => 'CUN-PC-007', 'nombre' => 'PC', 'area' => 'Ventas', 'categoria' => 'Cómputo', 'ubicacion_id' => $u->id, 'numero_serie' => 'XYZ9']);
        $a->save();
        $this->actingAs($tec);

        $r = $this->get(route('assets.etiquetas', ['ids' => [$a->id]]))->assertOk()->assertSee('CUN-PC-007')->assertSee('S/N XYZ9');
        $r->assertSee(route('assets.show', $a->id), false);
        $this->get(route('assets.etiquetas', ['ubicacion_id' => $u->id]))->assertOk()->assertSee('CUN-PC-007');
        $this->get(route('assets.etiquetas'))->assertOk()->assertSee('No hay activos seleccionados');

        $this->get(route('assets.show', $a->id))->assertOk()->assertSee('Reportar falla');
        $this->get(route('tickets.create', ['asset_id' => $a->id, 'ubicacion_id' => $u->id]))->assertOk()->assertSee('"' . $a->id . '"', false);
    }

    // ---------------- Topología y rack ----------------

    public function test_topologia_y_rack(): void
    {
        $tec = $this->usuario();
        $mdf = Ubicacion::create(['hotel_code' => 'HECUN', 'tipo' => 'site', 'codigo' => 'MDF', 'nombre' => 'Site principal']);
        $idf = Ubicacion::create(['hotel_code' => 'HECUN', 'tipo' => 'site', 'codigo' => 'IDF-ADM', 'nombre' => 'IDF Administrativo']);
        $core = NetworkDevice::forceCreate(['hotel_code' => 'HECUN', 'nombre' => 'SW-CORE', 'tipo' => 'Switch', 'total_ports' => 24, 'ubicacion_id' => $mdf->id, 'posicion_rack' => 'U20']);
        $fw = NetworkDevice::forceCreate(['hotel_code' => 'HECUN', 'nombre' => 'FW-01', 'tipo' => 'Firewall', 'total_ports' => 8, 'ubicacion_id' => $mdf->id, 'posicion_rack' => 'U22']);
        $acc = NetworkDevice::forceCreate(['hotel_code' => 'HECUN', 'nombre' => 'SW-ADM', 'tipo' => 'Switch', 'total_ports' => 24, 'ubicacion_id' => $idf->id, 'uplink_id' => $core->id, 'uplink_puerto' => 'puerto 24']);
        $this->actingAs($tec);

        $this->get(route('network.topologia'))->assertOk()->assertSeeInOrder(['SW-CORE', 'SW-ADM'])->assertSee('uplink: puerto 24');
        $this->get(route('network.rack', $mdf))->assertOk()->assertSeeInOrder(['U22', 'FW-01', 'U20', 'SW-CORE']);
        $this->get(route('ubicaciones.show', $mdf))->assertOk()->assertSee('Vista de rack');

        // Sin ciclos: el core no puede colgar de un switch que cuelga de él.
        $this->put(route('network.update', $core->id), ['nombre' => 'SW-CORE', 'tipo' => 'Switch', 'uplink_id' => $acc->id])->assertSessionHasErrors('uplink_id');
        $this->put(route('network.update', $core->id), ['nombre' => 'SW-CORE', 'tipo' => 'Switch', 'uplink_id' => $core->id])->assertSessionHasErrors('uplink_id');
        $this->put(route('network.update', $core->id), ['nombre' => 'SW-CORE', 'tipo' => 'Switch', 'uplink_id' => $fw->id, 'posicion_rack' => 'U20'])->assertSessionHasNoErrors();
        $this->assertSame($fw->id, $core->fresh()->uplink_id);
        $this->get(route('network.edit', $core->id))->assertOk()->assertSee('Cuelga de (uplink)');
    }

    // ---------------- Costo de TI por cuarto-noche ----------------

    public function test_costo_de_ti_por_cuarto_noche(): void
    {
        $gerente = $this->usuario('gerente');
        $prov = Proveedor::create(['hotel_code' => 'HECUN', 'nombre' => 'Gignet', 'activo' => true]);
        foreach ([['mensual', 30000], ['anual', 120000], ['unico', 50000]] as [$per, $costo]) {
            Contrato::create(['hotel_code' => 'HECUN', 'proveedor_id' => $prov->id, 'nombre' => "C {$per}", 'tipo' => 'soporte', 'costo' => $costo,
                'moneda' => 'MXN', 'periodicidad_pago' => $per, 'activo' => true, 'fecha_inicio' => now()->subYear(), 'fecha_fin' => now()->addYear()]);
        }
        $mes = now()->format('Y-m');

        $this->actingAs($gerente)->post(route('direccion.ocupacion'), ['mes' => $mes, 'cuartos_noche' => 8000])->assertRedirect();
        $this->post(route('direccion.ocupacion'), ['mes' => $mes, 'cuartos_noche' => 10000]);
        $this->assertSame(1, OcupacionMensual::count());

        // 30,000 + 120,000/12 = 40,000 al mes; el pago único no se repite.
        $r = $this->get(route('direccion.index', ['mes' => $mes]))->assertOk();
        $r->assertSee('$40,000')->assertSee('$4.00');
        $this->get(route('direccion.pdf', ['mes' => $mes]))->assertOk();

        $this->actingAs($this->usuario())->post(route('direccion.ocupacion'), ['mes' => $mes, 'cuartos_noche' => 1])->assertForbidden();
    }
}
