<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    // =====================================
    // MÓDULO DE USUARIOS
    // =====================================
    public function usersIndex() {
        $users = User::latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function usersCreate() {
        return view('admin.users.form');
    }

    public function usersStore(Request $request) {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:6',
            'role'         => 'required|in:admin,usuario,tecnico,gerente',
            'departamento' => 'nullable|string|required_if:role,tecnico',
        ]);

        $data['must_change_password'] = true;

        User::create($data);
        
        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function usersEdit(User $user) {
        return view('admin.users.form', compact('user'));
    }

    public function usersUpdate(Request $request, User $user) {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'         => 'required|in:admin,usuario,tecnico,gerente',
            'departamento' => 'nullable|string|required_if:role,tecnico',
            'password'     => 'nullable|string|min:6'
        ]);
        
        // Si el rol ya no es técnico, limpiamos el departamento
        if ($data['role'] !== 'tecnico') {
            $data['departamento'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        
        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function usersDestroy(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta mientras estés en sesión.');
        }

        $user->delete();
        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    // =====================================
    // MÓDULO DE ÁREAS
    // =====================================
    public function areasIndex() {
        $areas = Area::latest()->get();
        return view('admin.areas.index', compact('areas'));
    }

    public function areasCreate() {
        return view('admin.areas.form');
    }

    public function areasStore(Request $request) {
        $request->validate([
            'nombre' => 'required|string|unique:areas,nombre'
        ]);

        Area::create(['nombre' => $request->nombre]);
        return redirect()->route('admin.areas.index')->with('success', 'Área agregada correctamente.');
    }

    public function areasEdit($id) {
        $area = Area::findOrFail($id);
        return view('admin.areas.form', compact('area'));
    }

    public function areasUpdate(Request $request, $id) {
        $area = Area::findOrFail($id);
        
        $request->validate([
            'nombre' => ['required', 'string', Rule::unique('areas')->ignore($area->id)]
        ]);

        $area->update(['nombre' => $request->nombre]);
        return redirect()->route('admin.areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function areasDestroy($id) {
        $area = Area::findOrFail($id);
        $area->delete();
        return back()->with('success', 'Área eliminada correctamente.');
    }
}