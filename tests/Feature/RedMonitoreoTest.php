<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\Modulo;
use App\Models\MonitoreoEvento;
use App\Models\NetworkDevice;
use App\Models\NetworkPort;
use App\Models\SegmentoRed;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Monitoreo\Pinger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Ping falso: responde solo las IPs de $vivas. */
class PingerFalso implements Pinger
{
    public array $vivas = [];

    public function pingVarias(array $ips): array
    {
        return collect($ips)->mapWithKeys(fn ($ip) => [$ip => in_array($ip, $this->vivas, true) ? 2 : null])->all();
    }
}

/** Fase 4 (nov 2026): red ligada a activos, segmentos y monitoreo. */
class RedMonitoreoTest extends TestCase
{
    use RefreshDatabase;

    private PingerFalso $ping;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
        Departamento::firstOrCreate(['nombre' => 'Sistemas'], ['activo' => true]);
        $this->ping = new PingerFalso();
        $this->app->instance(Pinger::class, $this->ping);
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

    private function activoMonitoreado(string $ip = '10.7.64.10'): Asset
    {
        $a = (new Asset)->forceFill(['hotel_code' => 'HECUN', 'nomenclatura' => 'AP-LOBBY', 'nombre' => 'AP', 'area' => 'Lobby',
            'categoria' => 'Red', 'tipo_equipo' => 'Access Point', 'ip_address' => $ip, 'monitorear' => true]);
        $a->save();
        return $a;
    }

    public function test_tres_fallas_abren_un_solo_ticket_y_la_recuperacion_deja_nota(): void
    {
        $this->usuario('admin');
        $ap = $this->activoMonitoreado();

        $this->artisan('sigho:monitorear')->assertSuccessful();
        $this->artisan('sigho:monitorear')->assertSuccessful();
        $this->assertSame(0, Ticket::count(), 'Con 2 fallas todavía no se abre ticket.');

        $this->artisan('sigho:monitorear')->assertSuccessful();
        $ap->refresh();
        $this->assertSame('caido', $ap->estado_monitoreo);
        $this->assertSame(1, Ticket::count());
        $ticket = Ticket::first();
        $this->assertSame('Alta', $ticket->prioridad);
        $this->assertSame($ap->id, $ticket->asset_id);

        $this->artisan('sigho:monitorear')->assertSuccessful();
        $this->assertSame(1, Ticket::count(), 'Mientras siga caído no se duplica el ticket.');

        $this->ping->vivas = ['10.7.64.10'];
        $this->artisan('sigho:monitorear')->assertSuccessful();
        $ap->refresh();
        $this->assertSame('activo', $ap->estado_monitoreo);
        $this->assertNull($ap->ticket_monitoreo_id);
        $this->assertSame('Abierto', $ticket->fresh()->status, 'La recuperación no resuelve el ticket sola.');
        $this->assertTrue($ticket->notas()->where('nota', 'like', '%volvió a responder%')->exists());
        $this->assertSame(['caida', 'recuperacion'], MonitoreoEvento::orderBy('id')->pluck('tipo')->all());
    }

    public function test_monitorear_exige_ip(): void
    {
        $this->actingAs($this->usuario());
        $this->post(route('assets.store'), ['nomenclatura' => 'SW-1', 'categoria' => 'Red', 'status' => 'Activo', 'monitorear' => 1])
            ->assertSessionHasErrors('monitorear');
    }

    public function test_puerto_ligado_a_activo_y_validacion_de_propiedad(): void
    {
        $this->actingAs($this->usuario());
        $sw = NetworkDevice::create(['nombre' => 'SW-CORE', 'tipo' => 'Switch', 'total_ports' => 8]);
        $puerto = NetworkPort::create(['network_device_id' => $sw->id, 'port_number' => 3, 'status' => 'Libre', 'speed' => 'Gigabit']);
        $ap = $this->activoMonitoreado();
        $ajeno = (new Asset)->forceFill(['hotel_code' => 'HEMZT', 'nomenclatura' => 'MZT', 'nombre' => 'x', 'area' => 'x']);
        $ajeno->save();

        $this->post(route('network.port.update', $puerto->id), ['asset_id' => $ajeno->id])->assertSessionHasErrors('asset_id');

        $this->post(route('network.port.update', $puerto->id), ['asset_id' => $ap->id, 'vlan' => '64', 'speed' => 'PoE'])->assertSessionHas('success');
        $puerto->refresh();
        $this->assertSame('Ocupado', $puerto->status);
        $this->assertSame('AP-LOBBY', $puerto->connected_to);
        $this->assertSame($sw->id, $puerto->device->id);
        $this->get(route('assets.show', $ap->id))->assertOk()->assertSee('SW-CORE · puerto 3');

        $this->post(route('network.port.update', $puerto->id), ['speed' => 'Gigabit'])->assertSessionHas('success');
        $this->assertSame('Libre', $puerto->fresh()->status);
    }

    public function test_segmento_valida_cidr_y_detecta_conflictos(): void
    {
        $this->actingAs($this->usuario());

        $this->post(route('segmentos.store'), ['nombre' => 'Mal', 'red' => '10.7.64.0/40'])->assertSessionHasErrors('red');
        $this->post(route('segmentos.store'), ['nombre' => 'Admin', 'red' => '10.7.64.0/24', 'gateway' => '10.7.65.1'])->assertSessionHasErrors('gateway');

        $this->post(route('segmentos.store'), ['nombre' => 'Administrativa', 'vlan' => 64, 'red' => '10.7.64.77/24', 'gateway' => '10.7.64.1',
            'dhcp_inicio' => '10.7.64.100', 'dhcp_fin' => '10.7.64.200'])->assertRedirect();
        $seg = SegmentoRed::first();
        $this->assertSame('10.7.64.0/24', $seg->red);
        $this->assertCount(254, $seg->hosts());

        Asset::create(['nomenclatura' => 'PC-1', 'nombre' => 'PC', 'area' => 'x', 'ip_address' => '10.7.64.50']);
        Asset::create(['nomenclatura' => 'PC-2', 'nombre' => 'PC', 'area' => 'x', 'ip_address' => '10.7.64.50']);
        Asset::create(['nomenclatura' => 'PC-3', 'nombre' => 'PC', 'area' => 'x', 'ip_address' => '192.168.1.5']);

        $this->get(route('segmentos.show', $seg))->assertOk()->assertSee('1 IP(s) asignadas a más de un equipo')->assertSee('PC-1, PC-2');
        $this->get(route('segmentos.index'))->assertOk()->assertSee('2 de 254');
    }

    public function test_pantallas_de_fase4_cargan(): void
    {
        $this->actingAs($this->usuario('admin'));
        $this->activoMonitoreado();
        $sw = NetworkDevice::create(['nombre' => 'SW-1', 'tipo' => 'Switch', 'total_ports' => 8]);
        NetworkPort::create(['network_device_id' => $sw->id, 'port_number' => 1, 'status' => 'Libre', 'speed' => 'Fast Ethernet']);
        $seg = SegmentoRed::create(['nombre' => 'CCTV', 'red' => '10.7.70.0/24']);
        foreach ([route('network.index'), route('network.create'), route('network.show', $sw->id), route('network.edit', $sw->id),
                  route('segmentos.index'), route('segmentos.create'), route('segmentos.show', $seg), route('segmentos.edit', $seg),
                  route('monitoreo.index'), route('contratos.index', ['tipo' => 'enlace'])] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_alta_de_switch_con_ubicacion_e_ip(): void
    {
        $this->actingAs($this->usuario());
        $this->post(route('network.store'), ['nombre' => 'SW-P3', 'tipo' => 'Switch', 'total_ports' => 24, 'ip_gestion' => '10.7.64.3'])->assertRedirect();
        $sw = NetworkDevice::first();
        $this->assertSame(24, $sw->ports()->count());
        $this->assertSame('10.7.64.3', $sw->ip_gestion);
    }
}
