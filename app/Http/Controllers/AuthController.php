<?php
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
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
 
            // Redirigir según rol
            if ($user->isAdmin()) {
                return redirect()->route('assets.index');
            } elseif ($user->isGerente() || $user->isTecnico()) {
                return redirect()->route('tickets.index');
            } else {
                return redirect()->route('tickets.create');
            }
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