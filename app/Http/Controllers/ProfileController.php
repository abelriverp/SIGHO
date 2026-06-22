<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();
        
        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ])->save();

        return $user->isAdmin() 
            ? redirect()->route('assets.index')->with('success', 'Contraseña actualizada correctamente.')
            : redirect()->route('tickets.create')->with('success', 'Contraseña actualizada correctamente.');
    }
}