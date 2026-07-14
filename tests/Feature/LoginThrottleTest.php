<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        $usuario = User::factory()->create([
            'password'              => 'password-correcto',
            'must_change_password'  => false,
        ]);

        // 5 intentos con contraseña incorrecta
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $usuario->email,
                'password' => 'password-incorrecto',
            ]);
        }

        // El 6to intento, aunque use la contraseña CORRECTA, debe quedar
        // bloqueado por el rate limiter antes de llegar a AuthController.
        $response = $this->post('/login', [
            'email'    => $usuario->email,
            'password' => 'password-correcto',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_correct_credentials_log_in_when_under_the_limit(): void
    {
        $usuario = User::factory()->create([
            'password'             => 'password-correcto',
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'email'    => $usuario->email,
            'password' => 'password-correcto',
        ]);

        $this->assertAuthenticatedAs($usuario);
        $response->assertRedirect(route('dashboard'));
    }
}
