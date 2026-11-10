<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Hotel;
use App\Models\Modulo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Fase 1 (nov 2026): catalogo unico de activos + ubicaciones.
 */
class ActivosUbicacionesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'usuario', string $hotel = 'HECUN'): User
    {
        Hotel::firstOrCreate(['hotel_code' => $hotel], ['nombre' => "Hotel {$hotel}"]);

        $u = User::factory()->create([
            'role'                 => $role,
            'hotel_code'           => $hotel,
            'must_change_password' => false,
        ]);

        $modulo = Modulo::firstOrCreate(['clave' => 'seguimiento_equipos_ti'], ['nombre' => 'Activos y Ubicaciones', 'activo' => true]);
        $u->modulos()->attach($modulo->id, ['activado_en' => now()]);

        return $u;
    }

    public function test_genera_habitaciones_de_un_piso_y_omite_existentes(): void
    {
        $this->actingAs($this->usuario());
        Ubicacion::create(['tipo' => 'habitacion', 'codigo' => '1203', 'piso' => 12]);

        $this->post(route('ubicaciones.generar'), ['piso' => 12, 'desde' => 1, 'hasta' => 5, 'digitos' => 2])
            ->assertRedirect();

        $codigos = Ubicacion::where('piso', 12)->orderBy('codigo')->pluck('codigo')->all();
        $this->assertSame(['1201', '1202', '1203', '1204', '1205'], $codigos);
        $this->assertSame(5, Ubicacion::count(), 'La 1203 ya existía y no se duplica.');
        $this->assertSame('HECUN', Ubicacion::first()->hotel_code);
    }

    public function test_codigo_de_ubicacion_es_unico_y_se_guarda_en_mayusculas(): void
    {
        $this->actingAs($this->usuario());

        $this->post(route('ubicaciones.store'), ['tipo' => 'area', 'codigo' => 'lobby', 'nombre' => 'Lobby'])->assertRedirect();
        $this->assertDatabaseHas('ubicaciones', ['codigo' => 'LOBBY']);

        $this->post(route('ubicaciones.store'), ['tipo' => 'area', 'codigo' => 'LOBBY'])->assertSessionHasErrors('codigo');
    }

    public function test_registra_activo_con_ubicacion_y_periferico_ligado(): void
    {
        $this->actingAs($this->usuario());
        $site = Ubicacion::create(['tipo' => 'site', 'codigo' => 'SITE-PB']);

        $this->post(route('assets.store'), [
            'nomenclatura' => 'CUN-PC-001',
            'nombre'       => 'PC Recepción',
            'categoria'    => 'Cómputo',
            'status'       => 'Activo',
            'ubicacion_id' => $site->id,
            'mac_address'  => 'AA:BB:CC:DD:EE:FF',
            'garantia_hasta' => now()->addDays(10)->toDateString(),
        ])->assertRedirect();

        $pc = Asset::where('nomenclatura', 'CUN-PC-001')->firstOrFail();
        $this->assertSame($site->id, $pc->ubicacion_id);
        $this->assertSame('por_vencer', $pc->estadoGarantia());

        $this->post(route('assets.store'), [
            'nomenclatura' => 'CUN-MON-001',
            'categoria'    => 'Periférico',
            'tipo_equipo'  => 'Monitor',
            'status'       => 'Activo',
            'parent_id'    => $pc->id,
        ])->assertRedirect();

        $this->assertSame(1, $pc->componentes()->count());
        $this->get(route('assets.show', $pc->id))->assertOk()->assertSee('CUN-MON-001');
        $this->get(route('ubicaciones.show', $site))->assertOk()->assertSee('CUN-PC-001');
        $this->get(route('assets.index', ['garantia' => 'por_vencer']))->assertOk()->assertSee('CUN-PC-001');
    }

    public function test_no_acepta_ubicacion_de_otra_propiedad(): void
    {
        $otra = Ubicacion::create(['hotel_code' => 'HEMZT', 'tipo' => 'area', 'codigo' => 'LOBBY']);
        $this->actingAs($this->usuario());

        $this->post(route('assets.store'), [
            'nomenclatura' => 'CUN-PC-002',
            'categoria'    => 'Cómputo',
            'status'       => 'Activo',
            'ubicacion_id' => $otra->id,
        ])->assertSessionHasErrors('ubicacion_id');

        $this->assertDatabaseMissing('assets', ['nomenclatura' => 'CUN-PC-002']);
    }

    public function test_un_activo_no_puede_ligarse_a_si_mismo(): void
    {
        $this->actingAs($this->usuario());
        $pc = Asset::create(['nomenclatura' => 'PC-1', 'nombre' => 'PC', 'area' => 'x']);

        $this->put(route('assets.update', $pc->id), [
            'nomenclatura' => 'PC-1', 'categoria' => 'Cómputo', 'status' => 'Activo', 'parent_id' => $pc->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_dar_de_baja_registra_fecha_y_motivo(): void
    {
        $this->actingAs($this->usuario());
        $tv = Asset::create(['nomenclatura' => 'TV-1205', 'nombre' => 'TV', 'categoria' => 'TV y entretenimiento', 'area' => 'x']);

        $this->put(route('assets.update', $tv->id), [
            'nomenclatura' => 'TV-1205', 'categoria' => 'TV y entretenimiento', 'status' => 'Baja', 'motivo_baja' => 'Panel dañado',
        ])->assertRedirect();

        $tv->refresh();
        $this->assertSame('Baja', $tv->status);
        $this->assertSame(now()->toDateString(), $tv->fecha_baja->toDateString());
        $this->assertSame('Panel dañado', $tv->motivo_baja);
    }

    public function test_no_se_elimina_una_ubicacion_con_activos(): void
    {
        $this->actingAs($this->usuario('gerente'));
        $hab = Ubicacion::create(['tipo' => 'habitacion', 'codigo' => '1205', 'piso' => 12]);
        Asset::create(['nomenclatura' => 'TV-1205', 'nombre' => 'TV', 'area' => 'x', 'ubicacion_id' => $hab->id]);

        $this->delete(route('ubicaciones.destroy', $hab))->assertSessionHas('error');
        $this->assertDatabaseHas('ubicaciones', ['id' => $hab->id]);
    }

    public function test_mover_en_lote_a_una_ubicacion(): void
    {
        $this->actingAs($this->usuario());
        $site = Ubicacion::create(['tipo' => 'site', 'codigo' => 'SITE-1']);
        $a = Asset::create(['nomenclatura' => 'SW-1', 'nombre' => 'SW', 'area' => 'x']);
        $b = Asset::create(['nomenclatura' => 'SW-2', 'nombre' => 'SW', 'area' => 'x']);

        $this->post(route('assets.moverLote'), ['ids' => [$a->id, $b->id], 'ubicacion_id' => $site->id])->assertRedirect();

        $this->assertSame(2, $site->activos()->count());
    }

    public function test_importa_csv_crea_ubicaciones_y_resuelve_ligas(): void
    {
        $this->actingAs($this->usuario());

        $csv = "\xEF\xBB\xBF" . implode("\n", [
            'Nomenclatura,Nombre,Categoria,Tipo,Estado,Ubicacion (codigo),Ligado a (nomenclatura),Garantia hasta (AAAA-MM-DD)',
            'CUN-PC-010,PC Caja,Cómputo,CPU,Activo,RECEPCION,,2027-01-31',
            'CUN-MON-010,Monitor Caja,Periférico,Monitor,Activo,RECEPCION,CUN-PC-010,',
            'CUN-TV-1205,TV,TV y entretenimiento,TV,Activo,1205,,',
            'MALA-1,X,Categoria Inventada,,,,,',
        ]);
        $archivo = UploadedFile::fake()->createWithContent('activos.csv', $csv);

        $this->post(route('assets.import'), ['archivo' => $archivo])->assertRedirect(route('assets.index'));

        $this->assertSame(3, Asset::count());
        $pc = Asset::where('nomenclatura', 'CUN-PC-010')->first();
        $this->assertSame('2027-01-31', $pc->garantia_hasta->toDateString());
        $this->assertSame($pc->id, Asset::where('nomenclatura', 'CUN-MON-010')->value('parent_id'));
        $this->assertSame('habitacion', Ubicacion::where('codigo', '1205')->value('tipo'));
        $this->assertSame('area', Ubicacion::where('codigo', 'RECEPCION')->value('tipo'));
        $this->assertSame(2, Ubicacion::count());
    }

    public function test_exporta_csv_con_las_columnas_de_la_plantilla(): void
    {
        $this->actingAs($this->usuario());
        Asset::create(['nomenclatura' => 'CUN-PC-099', 'nombre' => 'PC', 'area' => 'x']);

        $respuesta = $this->get(route('assets.export'));
        $respuesta->assertOk();
        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Nomenclatura,Nombre,Categoria', $contenido);
        $this->assertStringContainsString('CUN-PC-099', $contenido);
    }

    public function test_ubicaciones_de_otra_propiedad_no_se_ven(): void
    {
        Ubicacion::create(['hotel_code' => 'HEMZT', 'tipo' => 'area', 'codigo' => 'MZT-LOBBY']);
        $this->actingAs($this->usuario());

        $this->get(route('ubicaciones.index'))->assertOk()->assertDontSee('MZT-LOBBY');
    }

    public function test_pantallas_de_activos_y_ubicaciones_cargan(): void
    {
        $this->actingAs($this->usuario('admin'));
        $u = Ubicacion::create(['tipo' => 'area', 'codigo' => 'LOBBY']);
        $a = Asset::create(['nomenclatura' => 'PC-1', 'nombre' => 'PC', 'area' => 'x', 'ubicacion_id' => $u->id]);

        foreach ([
            route('assets.index'), route('assets.create'), route('assets.edit', $a->id), route('assets.plantilla'),
            route('ubicaciones.index'), route('ubicaciones.create'), route('ubicaciones.edit', $u), route('dashboard'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
