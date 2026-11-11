<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Endurecimiento de seguridad (nov 2026). */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Hotel::firstOrCreate(['hotel_code' => 'HECUN'], ['nombre' => 'Hotel Emporio Cancún']);
    }

    private function usuario(string $role = 'tecnico', array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'hotel_code' => 'HECUN', 'must_change_password' => false, 'departamento' => 'Sistemas'], $extra));
    }

    public function test_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $r = $this->get('/login');
        $r->assertOk();
        $r->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $r->headers->get('Content-Security-Policy'));
        $r->assertHeaderMissing('X-Powered-By');

        // Con sesión, las páginas no se guardan en caché del navegador.
        $this->actingAs($this->usuario())->get(route('dashboard'))->assertHeader('Pragma', 'no-cache');
        $this->getJson('/api/v1/activos')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_archivos_subidos_requieren_sesion(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tickets/foto.jpg', 'contenido');
        Storage::disk('public')->put('contratos/contrato.txt', 'secreto');

        $this->get('/archivos/tickets/foto.jpg')->assertRedirect('/login');

        $this->actingAs($this->usuario());
        $this->get('/archivos/tickets/foto.jpg')->assertOk();
        $txt = $this->get('/archivos/contratos/contrato.txt');
        $txt->assertOk();
        $this->assertStringContainsString('attachment', $txt->headers->get('Content-Disposition'));

        $this->get('/archivos/../.env')->assertNotFound();
        $this->get('/archivos/tickets/..%2F..%2F.env')->assertNotFound();
        $this->get('/archivos/.gitignore')->assertNotFound();
        $this->get('/archivos/no-existe.pdf')->assertNotFound();

        $this->assertStringEndsWith('/archivos', config('filesystems.disks.public.url'));
    }

    public function test_cambiar_contrasena_pide_la_actual_y_cumple_la_politica(): void
    {
        $u = $this->usuario('tecnico', ['password' => 'Actual123']);
        DB::table('sessions')->insert(['id' => 'otra-pc', 'user_id' => $u->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($u);
        $url = route('password.change.update');

        $this->post($url, ['password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])->assertSessionHasErrors('password_actual');
        $this->post($url, ['password_actual' => 'mala', 'password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])->assertSessionHasErrors('password_actual');
        $this->post($url, ['password_actual' => 'Actual123', 'password' => 'corta1', 'password_confirmation' => 'corta1'])->assertSessionHasErrors('password');
        $this->post($url, ['password_actual' => 'Actual123', 'password' => 'sololetras', 'password_confirmation' => 'sololetras'])->assertSessionHasErrors('password');
        $this->post($url, ['password_actual' => 'Actual123', 'password' => 'Actual123', 'password_confirmation' => 'Actual123'])->assertSessionHasErrors('password');

        $this->post($url, ['password_actual' => 'Actual123', 'password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nueva2026x', $u->fresh()->password));
        $this->assertFalse(DB::table('sessions')->where('id', 'otra-pc')->exists(), 'Las otras sesiones se cierran.');
    }

    public function test_primer_ingreso_no_pide_contrasena_actual(): void
    {
        $u = $this->usuario('tecnico', ['password' => 'Temporal99', 'must_change_password' => true]);
        $this->actingAs($u)->post(route('password.change.update'), ['password' => 'MiClave2026', 'password_confirmation' => 'MiClave2026'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($u->fresh()->must_change_password);
    }

    public function test_admin_no_puede_asignar_contrasena_debil_y_al_cambiarla_cierra_sesiones(): void
    {
        $admin = $this->usuario('admin');
        $u = $this->usuario('tecnico');
        DB::table('sessions')->insert(['id' => 'sesion-tecnico', 'user_id' => $u->id, 'payload' => '', 'last_activity' => time()]);
        $base = ['name' => $u->name, 'email' => $u->email, 'role' => 'tecnico', 'hotel_code' => 'HECUN', 'departamento' => 'Sistemas'];

        $this->actingAs($admin)->put(route('admin.users.update', $u->id), $base + ['password' => 'abcdef'])->assertSessionHasErrors('password');
        $this->put(route('admin.users.update', $u->id), $base + ['password' => 'Temporal2026'])->assertSessionHasNoErrors();
        $this->assertFalse(DB::table('sessions')->where('id', 'sesion-tecnico')->exists());
    }

    public function test_login_exitoso_y_fallido_quedan_en_bitacora(): void
    {
        $u = $this->usuario('tecnico', ['password' => 'Clave2026']);

        $this->post(route('login.post'), ['email' => $u->email, 'password' => 'mala']);
        $this->post(route('login.post'), ['email' => 'nadie@x.com', 'password' => 'x']);
        $this->assertSame(2, AuditLog::where('accion', 'login.fallido')->count());
        $this->assertNotNull(AuditLog::where('accion', 'login.fallido')->value('ip'));

        $this->post(route('login.post'), ['email' => $u->email, 'password' => 'Clave2026'])->assertRedirect(route('dashboard'));
        $this->assertTrue(AuditLog::where('accion', 'login.exitoso')->where('user_id', $u->id)->exists());
    }

    public function test_login_tiene_tope_por_ip_aunque_cambie_el_usuario(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('login.post'), ['email' => "u{$i}@x.com", 'password' => 'x']);
        }
        $this->post(route('login.post'), ['email' => 'otro@x.com', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.']);
    }

    public function test_exportar_csv_neutraliza_formulas(): void
    {
        $a = (new Asset)->forceFill(['hotel_code' => 'HECUN', 'nomenclatura' => 'PC-1', 'nombre' => '=HYPERLINK("http://malo")', 'area' => '@SUM(1)', 'categoria' => 'Cómputo']);
        $a->save();
        $admin = $this->usuario('admin');

        $csv = $this->actingAs($admin)->get(route('assets.export'))->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@SUM(1)", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }
}
