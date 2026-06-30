<?php
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }
 
    public function login(Request $request)
    { 
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate(); 
            
            $user = Auth::user();
 
            // Si requiere cambio de contraseña y no es admin, va a su formulario
            if ($user->must_change_password && !$user->isAdmin()) {
                return redirect()->route('password.change');
            }

            // Todos los usuarios autorizados entran ahora directo al Dashboard de Resumen
            return redirect()->route('dashboard');
        }  
 
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