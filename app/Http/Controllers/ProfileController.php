<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\AuditLog;

class ProfileController extends Controller
{
    public function showChangePasswordForm()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Endurecimiento (nov 2026): si no es el cambio obligatorio del primer
        // ingreso, se pide la contraseña actual. Así alguien que encuentre una
        // sesión abierta en una PC compartida no puede quedarse con la cuenta.
        $reglas = ['password' => ['required', 'string', Password::defaults(), 'confirmed']];
        if (! $user->must_change_password) {
            $reglas['password_actual'] = ['required', 'string', 'current_password'];
        }
        $request->validate($reglas, [
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
        ]);

        if (Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'La nueva contraseña debe ser distinta a la actual.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ])->save();

        // Cierra las demás sesiones abiertas de este usuario (otras PCs).
        $user->cerrarSesiones($request->session()->getId());
        AuditLog::registrar('usuario.password', "{$user->name} cambió su contraseña.", $user);

        return $user->isAdmin() 
            ? redirect()->route('assets.index')->with('success', 'Contraseña actualizada correctamente.')
            : redirect()->route('tickets.create')->with('success', 'Contraseña actualizada correctamente.');
    }
}
