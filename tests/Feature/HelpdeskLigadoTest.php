<?php

namespace Tests\Feature;

use App\Models\ArticuloConocimiento;
use App\Models\Asset;
use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\InventarioItem;
use App\Models\Modulo;
use App\Models\RespuestaPredefinida;
use App\Models\Ticket;
use App\Models\TicketRecurrente;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Fase 2 (nov 2026): helpdesk ligado a activos y ubicaciones. */
class HelpdeskLigadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
        Departamento::firstOrCreate(['nombre' => 'Sistemas'], ['activo' => true]);
        // Sin catálogo de categorías: el ticket se levanta solo con descripción.
        Departamento::firstOrCreate(['nombre' => 'Mantenimiento'], ['activo' => true]);
    }

    private function usuario(string $role = 'usuario', array $extra = []): User
    {
        $u = User::factory()->create(array_merge([
            'role' => $role, 'hotel_code' => 'HECUN', 'must_change_password' => false, 'area' => 'Recepción',
            'departamento' => $role === 'tecnico' ? 'Sistemas' : null,
        ], $extra));
        foreach (['seguimiento_equipos_ti', 'inventario_suministros'] as $clave) {
            $m = Modulo::firstOrCreate(['clave' => $clave], ['nombre' => $clave, 'activo' => true]);
            $u->modulos()->syncWithoutDetaching([$m->id => ['activado_en' => now()]]);
        }
        return $u;
    }

    private function ticketBase(User $u, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'hotel_code' => 'HECUN', 'user_id' => $u->id, 'area' => 'Recepción', 'departamento_destino' => 'Sistemas',
            'descripcion' => 'No hay internet', 'prioridad' => 'Media', 'status' => 'Abierto',
        ], $extra));
    }

    public function test_el_ticket_toma_la_ubicacion_del_equipo_elegido(): void
    {
        $u = $this->usuario();
        $this->actingAs($u);
        $hab = Ubicacion::create(['tipo' => 'habitacion', 'codigo' => '1205', 'piso' => 12]);
        $ap = Asset::create(['nomenclatura' => 'AP-1205', 'nombre' => 'AP', 'area' => 'x', 'categoria' => 'Red', 'ubicacion_id' => $hab->id]);

        $this->post(route('tickets.store'), [
            'departamento_destino' => 'Mantenimiento', 'descripcion' => 'Sin wifi en la 1205', 'prioridad' => 'Alta', 'asset_id' => $ap->id,
        ])->assertRedirect(route('tickets.index'));

        $t = Ticket::latest('id')->first();
        $this->assertSame($ap->id, $t->asset_id);
        $this->assertSame($hab->id, $t->ubicacion_id);
    }

    public function test_no_acepta_equipo_de_otra_propiedad(): void
    {
        // hotel_code no es fillable en Asset (lo pone BelongsToHotel): forceFill.
        $ajeno = (new Asset)->forceFill(['hotel_code' => 'HEMZT', 'nomenclatura' => 'MZT-1', 'nombre' => 'x', 'area' => 'x']);
        $ajeno->save();
        $this->actingAs($this->usuario());

        $this->post(route('tickets.store'), [
            'departamento_destino' => 'Mantenimiento', 'descripcion' => 'x', 'prioridad' => 'Baja', 'asset_id' => $ajeno->id,
        ])->assertSessionHasErrors('asset_id');
        $this->assertSame(0, Ticket::count());
    }

    public function test_confirmar_exige_calificacion_y_la_guarda(): void
    {
        $u = $this->usuario();
        $t = $this->ticketBase($u, ['status' => 'Resuelto', 'notas_admin' => 'Se reinició el AP', 'fecha_resolucion' => now()]);
        $this->actingAs($u);

        $this->post(route('tickets.confirmar', $t->id), [])->assertSessionHasErrors('csat_calificacion');
        $this->assertSame('Resuelto', $t->fresh()->status);

        $this->post(route('tickets.confirmar', $t->id), ['csat_calificacion' => 4, 'csat_comentario' => 'Rápido'])->assertRedirect();
        $t->refresh();
        $this->assertSame('Cerrado', $t->status);
        $this->assertSame(4, $t->csat_calificacion);
        $this->assertNotNull($t->csat_at);
    }

    public function test_material_usado_descuenta_stock_y_queda_en_el_ticket(): void
    {
        $tec = $this->usuario('tecnico');
        $item = InventarioItem::create(['hotel_code' => 'HECUN', 'user_id' => $tec->id, 'nombre' => 'Cable UTP', 'cantidad' => 10, 'unidad' => 'pza', 'area' => 'Sistemas']);
        $t = $this->ticketBase($this->usuario());
        $this->actingAs($tec);

        $this->post(route('tickets.material.store', $t->id), ['inventario_item_id' => $item->id, 'cantidad' => 3])->assertSessionHas('success');
        $this->assertEquals(7, (float) $item->fresh()->cantidad);
        $this->assertSame(1, $t->materiales()->count());

        $this->post(route('tickets.material.store', $t->id), ['inventario_item_id' => $item->id, 'cantidad' => 50])->assertSessionHasErrors('cantidad');
        $this->assertEquals(7, (float) $item->fresh()->cantidad);
    }

    public function test_un_usuario_normal_no_registra_material(): void
    {
        $u = $this->usuario();
        $t = $this->ticketBase($u);
        $this->actingAs($u);
        $this->post(route('tickets.material.store', $t->id), ['inventario_item_id' => 1, 'cantidad' => 1])->assertForbidden();
    }

    public function test_convertir_solucion_en_articulo_y_sugerirlo(): void
    {
        $tec = $this->usuario('tecnico');
        $t = $this->ticketBase($this->usuario(), [
            'status' => 'Resuelto', 'notas_admin' => '1. Reiniciar spooler', 'campos_extra' => ['categoria_label' => 'Impresoras', 'campos' => []],
        ]);
        $this->actingAs($tec);

        $this->get(route('conocimiento.create', ['ticket' => $t->id]))->assertOk()->assertSee('Reiniciar spooler');
        $this->post(route('conocimiento.store'), [
            'titulo' => 'Impresora no imprime', 'departamento' => 'Sistemas', 'categoria' => 'Impresoras',
            'solucion' => '1. Reiniciar spooler', 'publicado' => 1, 'ticket_origen_id' => $t->id,
        ])->assertRedirect();

        $nuevo = $this->ticketBase($this->usuario(), ['campos_extra' => ['categoria_label' => 'Impresoras', 'campos' => []]]);
        $this->get(route('tickets.show', $nuevo->id))->assertOk()->assertSee('Impresora no imprime');
    }

    public function test_usuario_normal_lee_pero_no_escribe_articulos(): void
    {
        ArticuloConocimiento::create(['hotel_code' => 'HECUN', 'titulo' => 'Cómo conectar al wifi', 'solucion' => 'Pasos', 'publicado' => true]);
        ArticuloConocimiento::create(['hotel_code' => 'HECUN', 'titulo' => 'Borrador interno', 'solucion' => 'x', 'publicado' => false]);
        $this->actingAs($this->usuario());

        $this->get(route('conocimiento.index'))->assertOk()->assertSee('Cómo conectar al wifi')->assertDontSee('Borrador interno');
        $this->get(route('conocimiento.create'))->assertForbidden();
    }

    public function test_respuestas_predefinidas_solo_supervisor_y_aparecen_al_tecnico(): void
    {
        $this->actingAs($this->usuario('tecnico'));
        $this->get(route('tickets.respuestas.index'))->assertForbidden();

        $this->actingAs($this->usuario('gerente'));
        $this->post(route('tickets.respuestas.store'), ['titulo' => 'Reinicio', 'texto' => 'Se reinició el equipo y quedó funcionando.', 'departamento' => 'Sistemas'])->assertRedirect();

        $tec = $this->usuario('tecnico');
        $t = $this->ticketBase($this->usuario());
        $this->actingAs($tec);
        $this->get(route('tickets.show', $t->id))->assertOk()->assertSee('Insertar respuesta predefinida')->assertSee('Reinicio');
        $this->assertSame(1, RespuestaPredefinida::count());
    }

    public function test_recurrente_genera_ticket_y_avanza_la_fecha(): void
    {
        $gerente = $this->usuario('gerente');
        $r = TicketRecurrente::create([
            'hotel_code' => 'HECUN', 'titulo' => 'Revisión de UPS', 'descripcion' => 'Probar baterías',
            'departamento_destino' => 'Sistemas', 'prioridad' => 'Media', 'frecuencia' => 'mensual',
            'proxima_fecha' => '2026-11-10', 'activo' => true, 'creado_por' => $gerente->id,
        ]);

        $this->artisan('tickets:generar-recurrentes', ['--fecha' => '2026-11-10'])->assertSuccessful();
        $this->assertSame(1, Ticket::count());
        $this->assertSame('2026-12-10', $r->fresh()->proxima_fecha->toDateString());

        // Mismo día otra vez: no duplica.
        $this->artisan('tickets:generar-recurrentes', ['--fecha' => '2026-11-10'])->assertSuccessful();
        $this->assertSame(1, Ticket::count());
    }

    public function test_recurrente_atrasado_no_genera_varios_tickets(): void
    {
        $gerente = $this->usuario('gerente');
        TicketRecurrente::create([
            'hotel_code' => 'HECUN', 'titulo' => 'Respaldo', 'descripcion' => 'x', 'departamento_destino' => 'Sistemas',
            'prioridad' => 'Baja', 'frecuencia' => 'semanal', 'proxima_fecha' => '2026-10-01', 'activo' => true, 'creado_por' => $gerente->id,
        ]);

        $this->artisan('tickets:generar-recurrentes', ['--fecha' => '2026-11-10'])->assertSuccessful();
        $this->assertSame(1, Ticket::count());
        $this->assertTrue(TicketRecurrente::first()->proxima_fecha->gt(Carbon::parse('2026-11-10')));
    }

    public function test_reportes_muestran_calidad_y_top_equipos(): void
    {
        $admin = $this->usuario('admin');
        $u = $this->usuario();
        $ap = Asset::create(['hotel_code' => 'HECUN', 'nomenclatura' => 'AP-LOBBY', 'nombre' => 'AP', 'area' => 'x', 'tipo_equipo' => 'Access Point']);
        $this->ticketBase($u, ['asset_id' => $ap->id]);
        $this->ticketBase($u, ['asset_id' => $ap->id, 'status' => 'Cerrado', 'fecha_resolucion' => now(), 'csat_calificacion' => 5, 'csat_at' => now()]);
        $this->actingAs($admin);

        $this->get(route('tickets.reportes'))->assertOk()->assertSee('AP-LOBBY')->assertSee('5 / 5');
    }

    public function test_pantallas_nuevas_cargan(): void
    {
        $this->actingAs($this->usuario('admin'));
        foreach (['conocimiento.index', 'conocimiento.create', 'tickets.respuestas.index', 'tickets.recurrentes.index', 'tickets.recurrentes.create', 'tickets.create'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }
}
