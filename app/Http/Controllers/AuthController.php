<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\DirectorioActivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Fase 5: se puede entrar con correo o con usuario de red. Si Active
     * Directory está habilitado y el usuario tiene `usuario_ad`, la
     * contraseña se valida contra AD; si AD no responde, se intenta la
     * contraseña local (para no dejar a nadie fuera si se cae el servidor).
     */
    public function login(Request $request, DirectorioActivo $ad)
    {
        $data = $request->validate([
            'email'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $identificador = trim($data['email']);

        $user = User::where('email', $identificador)
            ->orWhere(fn ($q) => $q->whereNotNull('usuario_ad')->where('usuario_ad', $identificador))
            ->first();

        if ($user && $user->usuario_ad && $ad->habilitado()) {
            $resultado = $ad->autenticar($user->usuario_ad, $data['password']);

            if ($resultado === DirectorioActivo::OK) {
                return $this->entrar($request, $user);
            }
            if ($resultado === DirectorioActivo::RECHAZADO) {
                return $this->fallo($identificador, 'Active Directory rechazó la contraseña');
            }
            Log::warning('Active Directory sin respuesta; se intenta contraseña local.', ['user_id' => $user->id]);
        }

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $data['password']])) {
            return $this->entrar($request, Auth::user(), false);
        }

        return $this->fallo($identificador, $user ? 'contraseña incorrecta' : 'usuario no existe');
    }

    private function entrar(Request $request, User $user, bool $loguear = true)
    {
        if ($loguear) {
            Auth::login($user);
        }
        $request->session()->regenerate();
        \App\Models\AuditLog::registrar('login.exitoso', "{$user->name} inició sesión.", $user);

        // Quien entra con AD no maneja contraseña local.
        if ($user->must_change_password && ! $user->isAdmin() && ! ($user->usuario_ad && app(DirectorioActivo::class)->habilitado())) {
            return redirect()->route('password.change');
        }

        return redirect()->route('dashboard');
    }

    private function fallo(string $identificador = '', string $motivo = '')
    {
        // Endurecimiento (nov 2026): los intentos fallidos quedan en bitácora
        // con IP, para detectar a alguien probando contraseñas.
        \App\Models\AuditLog::registrar('login.fallido', 'Intento fallido para "' . mb_substr($identificador, 0, 100) . '"' . ($motivo ? " ({$motivo})." : '.'));

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
