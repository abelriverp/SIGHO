<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Asset;
use App\Models\Cambio;
use App\Models\Departamento;
use App\Models\Hotel;
use App\Models\Problema;
use App\Models\Ticket;
use App\Models\TicketNota;
use App\Models\User;
use App\Notifications\ReporteMensualTINotification;
use App\Services\Auth\DirectorioActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** AD falso: responde siempre $resultado. */
class DirectorioFalso extends DirectorioActivo
{
    public function __construct(public string $resultado = self::OK, public bool $activo = true)
    {
    }

    public function habilitado(): bool
    {
        return $this->activo;
    }

    public function autenticar(string $usuarioAd, string $password): string
    {
        return $this->resultado;
    }
}

/** Fase 5 (nov 2026): problemas, cambios, tablero de dirección, API y Active Directory. */
class GestionFase5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
        Departamento::firstOrCreate(['nombre' => 'Sistemas'], ['activo' => true]);
    }

    private function usuario(string $role = 'tecnico', array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'hotel_code' => 'HECUN', 'must_change_password' => false, 'departamento' => 'Sistemas'], $extra));
    }

    private function activo(string $nom = 'PC-REC-01'): Asset
    {
        $a = (new Asset)->forceFill(['hotel_code' => 'HECUN', 'nomenclatura' => $nom, 'nombre' => 'PC', 'area' => 'Recepción', 'categoria' => 'Cómputo', 'tipo_equipo' => 'Desktop']);
        $a->save();
        return $a;
    }

    private function ticket(User $u, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'hotel_code' => 'HECUN', 'user_id' => $u->id, 'area' => 'Recepción', 'departamento_destino' => 'Sistemas',
            'descripcion' => 'La PC no enciende', 'prioridad' => 'Media', 'status' => 'Abierto',
        ], $extra));
    }

    private function cambio(User $solicita, array $extra = []): Cambio
    {
        return Cambio::create(array_merge([
            'hotel_code' => 'HECUN', 'titulo' => 'Actualizar firewall', 'descripcion' => 'Firmware nuevo', 'tipo' => 'normal', 'riesgo' => 'Media',
            'plan_implementacion' => 'Paso 1', 'plan_rollback' => 'Restaurar respaldo', 'estado' => 'borrador', 'solicitado_por' => $solicita->id,
        ], $extra));
    }

    // ---------------- Problemas ----------------

    public function test_equipo_con_tres_tickets_aparece_como_candidato_y_se_abre_problema(): void
    {
        $tec = $this->usuario();
        $pc = $this->activo();
        $tickets = collect(range(1, 3))->map(fn () => $this->ticket($tec, ['asset_id' => $pc->id]));

        $this->actingAs($tec)->get(route('problemas.index'))->assertOk()->assertSee('PC-REC-01')->assertSee('Posibles problemas');
        $this->get(route('problemas.create', ['asset_id' => $pc->id]))->assertOk()->assertSee('Fallas repetidas en PC-REC-01');

        $this->post(route('problemas.store'), [
            'titulo' => 'Fuente dañada', 'descripcion' => 'Se apaga', 'prioridad' => 'Alta', 'estado' => 'abierto',
            'asset_id' => $pc->id, 'tickets' => $tickets->pluck('id')->all(),
        ])->assertRedirect();

        $p = Problema::first();
        $this->assertSame(3, $p->tickets()->count());
        $this->assertSame($tec->id, $p->creado_por);
        // Ya tiene problema abierto: deja de ser candidato.
        $this->get(route('problemas.index'))->assertDontSee('Posibles problemas');
        $this->get(route('problemas.show', $p))->assertOk()->assertSee('3 ticket(s) ligado(s)');
    }

    public function test_resolver_problema_exige_causa_raiz_y_deja_nota_en_tickets_abiertos(): void
    {
        $tec = $this->usuario();
        $t = $this->ticket($tec);
        $p = Problema::create(['hotel_code' => 'HECUN', 'titulo' => 'Caídas de red', 'descripcion' => 'x', 'prioridad' => 'Media', 'estado' => 'abierto', 'creado_por' => $tec->id]);
        $p->tickets()->attach($t->id);
        $base = ['titulo' => 'Caídas de red', 'descripcion' => 'x', 'prioridad' => 'Media', 'estado' => 'resuelto', 'tickets' => [$t->id]];

        $this->actingAs($tec)->put(route('problemas.update', $p), $base)->assertSessionHasErrors('causa_raiz');
        $this->assertTrue($p->fresh()->abierto());

        $this->put(route('problemas.update', $p), $base + ['causa_raiz' => 'Switch con loop'])->assertRedirect();
        $this->assertNotNull($p->fresh()->resuelto_at);
        $this->assertTrue(TicketNota::where('ticket_id', $t->id)->where('nota', 'like', '%Switch con loop%')->exists());
    }

    public function test_ligar_ticket_desde_su_detalle_y_usuario_normal_no_entra(): void
    {
        $tec = $this->usuario();
        $t = $this->ticket($tec);
        $p = Problema::create(['hotel_code' => 'HECUN', 'titulo' => 'Impresoras', 'descripcion' => 'x', 'prioridad' => 'Media', 'estado' => 'abierto', 'creado_por' => $tec->id]);

        $this->actingAs($tec)->get(route('tickets.show', $t->id))->assertOk()->assertSee('Ligar a problema abierto');
        $this->post(route('problemas.ligar', $t->id), ['problema_id' => $p->id])->assertRedirect();
        $this->assertTrue($p->tickets()->whereKey($t->id)->exists());

        $this->get(route('problemas.create', ['ticket' => $t->id]))->assertOk()->assertSee('La PC no enciende');
        $this->get(route('problemas.edit', $p))->assertOk()->assertSee('Impresoras');
        $this->get(route('problemas.show', $p))->assertOk()->assertSee('Registrar cambio');
        $this->get(route('cambios.create', ['problema_id' => $p->id]))->assertOk();

        $this->actingAs($this->usuario('usuario'))->get(route('problemas.index'))->assertForbidden();
    }

    // ---------------- Cambios ----------------

    public function test_cambio_normal_requiere_aprobacion_de_otro_supervisor(): void
    {
        $tec = $this->usuario();
        $this->actingAs($tec)->post(route('cambios.store'), [
            'titulo' => 'Mover rack', 'descripcion' => 'x', 'tipo' => 'normal', 'riesgo' => 'Alta',
            'plan_implementacion' => 'a', 'plan_rollback' => 'b', 'activos' => [$this->activo()->id],
        ])->assertRedirect();
        $c = Cambio::first();
        $this->assertSame('borrador', $c->estado);
        $this->assertSame(1, $c->activos()->count());

        $this->post(route('cambios.transicion', $c), ['estado' => 'en_ejecucion'])->assertSessionHas('error');
        $this->post(route('cambios.transicion', $c), ['estado' => 'pendiente_aprobacion']);
        $this->assertSame('pendiente_aprobacion', $c->fresh()->estado);

        // Técnico no aprueba.
        $this->post(route('cambios.transicion', $c), ['estado' => 'aprobado'])->assertForbidden();

        $gerente = $this->usuario('gerente');
        $this->actingAs($gerente)->post(route('cambios.transicion', $c), ['estado' => 'rechazado'])->assertSessionHas('error');
        $this->post(route('cambios.transicion', $c), ['estado' => 'aprobado', 'comentario' => 'Adelante'])->assertSessionHas('success');
        $c->refresh();
        $this->assertSame('aprobado', $c->estado);
        $this->assertSame($gerente->id, $c->aprobado_por);

        $this->actingAs($tec)->post(route('cambios.transicion', $c), ['estado' => 'en_ejecucion']);
        $this->post(route('cambios.transicion', $c), ['estado' => 'completado'])->assertSessionHas('error');
        $this->post(route('cambios.transicion', $c), ['estado' => 'completado', 'comentario' => 'Sin incidentes']);
        $c->refresh();
        $this->assertSame('completado', $c->estado);
        $this->assertSame('Sin incidentes', $c->resultado);
    }

    public function test_gerente_no_aprueba_su_propio_cambio_pero_admin_si(): void
    {
        $gerente = $this->usuario('gerente');
        $c = $this->cambio($gerente, ['estado' => 'pendiente_aprobacion']);
        $this->actingAs($gerente)->post(route('cambios.transicion', $c), ['estado' => 'aprobado'])->assertSessionHas('error');
        $this->assertSame('pendiente_aprobacion', $c->fresh()->estado);

        $admin = $this->usuario('admin');
        $c2 = $this->cambio($admin, ['estado' => 'pendiente_aprobacion']);
        $this->actingAs($admin)->post(route('cambios.transicion', $c2), ['estado' => 'aprobado']);
        $this->assertSame('aprobado', $c2->fresh()->estado);
    }

    public function test_estandar_se_preaprueba_y_emergencia_se_ejecuta_desde_borrador(): void
    {
        $tec = $this->usuario();
        $std = $this->cambio($tec, ['tipo' => 'estandar']);
        $this->actingAs($tec)->post(route('cambios.transicion', $std), ['estado' => 'pendiente_aprobacion']);
        $this->assertSame('aprobado', $std->fresh()->estado);

        $em = $this->cambio($tec, ['tipo' => 'emergencia']);
        $this->post(route('cambios.transicion', $em), ['estado' => 'en_ejecucion']);
        $this->assertSame('en_ejecucion', $em->fresh()->estado);
    }

    public function test_cambio_aprobado_no_se_edita_y_pantallas_cargan(): void
    {
        $tec = $this->usuario();
        $c = $this->cambio($tec, ['estado' => 'aprobado']);
        $this->actingAs($tec)->get(route('cambios.edit', $c))->assertRedirect(route('cambios.show', $c));
        $this->get(route('cambios.index'))->assertOk()->assertSee('Actualizar firewall');
        $this->get(route('cambios.index', ['vista' => 'pendientes']))->assertOk();
        $this->get(route('cambios.create'))->assertOk();
        $this->get(route('cambios.show', $c))->assertOk()->assertSee('Iniciar ejecución');
        $this->get(route('cambios.edit', $this->cambio($tec)))->assertOk();
    }

    // ---------------- Tablero de dirección ----------------

    public function test_tablero_de_direccion_y_pdf_solo_para_supervisores(): void
    {
        $tec = $this->usuario();
        $this->ticket($tec, ['status' => 'Resuelto', 'fecha_resolucion' => now(), 'created_at' => now()->subHours(3)]);
        $this->ticket($tec);

        $this->actingAs($tec)->get(route('direccion.index'))->assertForbidden();

        $gerente = $this->usuario('gerente');
        $this->actingAs($gerente)->get(route('direccion.index'))->assertOk()->assertSee('Tablero de dirección')->assertSee('Disponibilidad');
        $this->get(route('direccion.index', ['mes' => now()->subMonth()->format('Y-m')]))->assertOk();
        $pdf = $this->get(route('direccion.pdf'));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_reporte_mensual_se_envia_a_gerentes_y_admins_con_pdf(): void
    {
        $admin = $this->usuario('admin');
        $gerente = $this->usuario('gerente');
        $tec = $this->usuario();

        $this->artisan('sigho:reporte-mensual', ['--mes' => now()->subMonth()->format('Y-m')])->assertSuccessful();

        Notification::assertSentTo([$admin, $gerente], ReporteMensualTINotification::class);
        Notification::assertNotSentTo($tec, ReporteMensualTINotification::class);
    }

    // ---------------- API ----------------

    public function test_api_sin_token_o_invalido_da_401(): void
    {
        $this->getJson('/api/v1/activos')->assertUnauthorized();
        $this->getJson('/api/v1/activos', ['Authorization' => 'Bearer sigho_falso'])->assertUnauthorized();

        [, $plano] = ApiToken::generar(['nombre' => 'Vencido', 'user_id' => $this->usuario('gerente')->id, 'hotel_code' => 'HECUN', 'expira_at' => now()->subDay()]);
        $this->getJson('/api/v1/activos', ['Authorization' => "Bearer {$plano}"])->assertUnauthorized();
    }

    public function test_api_lee_activos_y_kpis_y_solo_escritura_crea_tickets(): void
    {
        $gerente = $this->usuario('gerente');
        $this->activo('SRV-01');
        [$token, $plano] = ApiToken::generar(['nombre' => 'BI', 'user_id' => $gerente->id, 'hotel_code' => 'HECUN']);
        $h = ['Authorization' => "Bearer {$plano}"];

        $this->getJson('/api/v1/activos', $h)->assertOk()->assertJsonFragment(['nomenclatura' => 'SRV-01']);
        $this->getJson('/api/v1/activos/SRV-01', $h)->assertOk()->assertJsonPath('nomenclatura', 'SRV-01');
        $this->getJson('/api/v1/activos/NOEXISTE', $h)->assertNotFound();
        $this->getJson('/api/v1/kpis', $h)->assertOk()->assertJsonStructure(['periodo', 'tickets' => ['creados', 'sla_pct'], 'activos', 'monitoreo']);
        $this->getJson('/api/v1/monitoreo', $h)->assertOk();
        $this->assertNotNull($token->fresh()->ultimo_uso_at);

        $ticket = ['departamento_destino' => 'Sistemas', 'descripcion' => 'Respaldo falló', 'prioridad' => 'Alta', 'activo' => 'SRV-01'];
        $this->postJson('/api/v1/tickets', $ticket, $h)->assertForbidden();

        [, $escritura] = ApiToken::generar(['nombre' => 'Script', 'user_id' => $gerente->id, 'hotel_code' => 'HECUN', 'puede_escribir' => true]);
        $this->postJson('/api/v1/tickets', $ticket, ['Authorization' => "Bearer {$escritura}"])->assertCreated();
        $t = Ticket::latest('id')->first();
        $this->assertSame('Respaldo falló', $t->descripcion);
        $this->assertSame(Asset::where('nomenclatura', 'SRV-01')->value('id'), $t->asset_id);
        $this->getJson('/api/v1/tickets', $h)->assertOk()->assertJsonFragment(['descripcion' => 'Respaldo falló']);
    }

    public function test_admin_genera_token_que_se_muestra_una_vez_y_lo_revoca(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->get(route('admin.api-tokens.index'))->assertOk()->assertSee('Cómo se usa');

        $this->post(route('admin.api-tokens.store'), ['nombre' => 'Power BI', 'user_id' => $admin->id])
            ->assertRedirect()->assertSessionHas('token_nuevo');
        $tk = ApiToken::first();
        $this->assertStringStartsWith('sigho_', session('token_nuevo'));
        $this->assertSame(hash('sha256', session('token_nuevo')), $tk->token_hash);

        $this->delete(route('admin.api-tokens.destroy', $tk))->assertRedirect();
        $this->assertSame(0, ApiToken::count());

        $this->actingAs($this->usuario('gerente'))->get(route('admin.api-tokens.index'))->assertForbidden();
    }

    // ---------------- Active Directory ----------------

    private function conAd(string $resultado, bool $activo = true): void
    {
        $this->app->instance(DirectorioActivo::class, new DirectorioFalso($resultado, $activo));
    }

    public function test_login_con_usuario_de_red_valido_en_ad(): void
    {
        $u = $this->usuario('tecnico', ['usuario_ad' => 'arivera', 'password' => 'local123', 'must_change_password' => true]);
        $this->conAd(DirectorioActivo::OK);

        $this->post(route('login.post'), ['email' => 'arivera', 'password' => 'windows'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_ad_rechaza_aunque_la_contrasena_local_coincida(): void
    {
        $this->usuario('tecnico', ['usuario_ad' => 'arivera', 'password' => 'local123']);
        $this->conAd(DirectorioActivo::RECHAZADO);

        $this->post(route('login.post'), ['email' => 'arivera', 'password' => 'local123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sin_conexion_a_ad_usa_la_contrasena_local(): void
    {
        $u = $this->usuario('tecnico', ['usuario_ad' => 'arivera', 'password' => 'local123']);
        $this->conAd(DirectorioActivo::SIN_CONEXION);

        $this->post(route('login.post'), ['email' => 'arivera', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->post(route('login.post'), ['email' => $u->email, 'password' => 'local123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_con_ad_apagado_el_login_por_correo_sigue_igual(): void
    {
        $u = $this->usuario('tecnico', ['usuario_ad' => 'arivera', 'password' => 'local123']);
        $this->conAd(DirectorioActivo::RECHAZADO, false);

        $this->post(route('login.post'), ['email' => $u->email, 'password' => 'local123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_admin_captura_usuario_de_red_unico(): void
    {
        $admin = $this->usuario('admin');
        $otro = $this->usuario('tecnico', ['usuario_ad' => 'jperez']);
        $u = $this->usuario('tecnico');
        $base = ['name' => $u->name, 'email' => $u->email, 'role' => 'tecnico', 'hotel_code' => 'HECUN', 'departamento' => 'Sistemas'];

        $this->actingAs($admin)->put(route('admin.users.update', $u->id), $base + ['usuario_ad' => 'jperez'])->assertSessionHasErrors('usuario_ad');
        $this->put(route('admin.users.update', $u->id), $base + ['usuario_ad' => 'mlopez'])->assertSessionHasNoErrors();
        $this->assertSame('mlopez', $u->fresh()->usuario_ad);
        $this->assertSame('jperez', $otro->fresh()->usuario_ad);
    }
}
