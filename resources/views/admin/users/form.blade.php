@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($user) ? 'Editar Usuario' : 'Registrar Nuevo Usuario' }}</h3>
        </div>
        
        <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                <input type="text" name="name" value="{{ $user->name ?? old('name') }}" required 
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" value="{{ $user->email ?? old('email') }}" required 
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">
                    {{ isset($user) ? 'Nueva Contraseña (dejar en blanco para no cambiar)' : 'Contraseña Temporal' }}
                </label>
                <input type="password" name="password" {{ isset($user) ? '' : 'required' }} minlength="6" 
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                @if(!isset($user))
                    <p class="text-[10px] text-slate-400 mt-1 italic">El usuario deberá cambiarla en su primer inicio de sesión.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Rol en el Sistema</label>
                <select name="role" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="usuario" {{ (isset($user) && $user->role == 'usuario') ? 'selected' : '' }}>
                        👤 Empleado — Solo crear y ver sus tickets
                    </option>
                    <option value="tecnico" {{ (isset($user) && $user->role == 'tecnico') ? 'selected' : '' }}>
                        🔧 Técnico — Ver y resolver tickets de su departamento
                    </option>
                    <option value="gerente" {{ (isset($user) && $user->role == 'gerente') ? 'selected' : '' }}>
                        📊 Gerente — Ver todos los tickets (solo lectura)
                    </option>
                    <option value="admin" {{ (isset($user) && $user->role == 'admin') ? 'selected' : '' }}>
                        ⚙️ Administrador TI — Acceso total al sistema
                    </option>
                </select>
            </div>

            {{-- Campo de departamento solo para técnicos --}}
            <div id="campo-departamento" style="{{ (isset($user) && $user->role == 'tecnico') ? '' : 'display:none' }}">
                <label class="block text-sm font-bold text-slate-700 mb-1">Departamento que atiende</label>
                <select name="departamento" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="">Selecciona...</option>
                    <option value="Sistemas" {{ (isset($user) && $user->departamento == 'Sistemas') ? 'selected' : '' }}>Sistemas / IT</option>
                    <option value="Mantenimiento" {{ (isset($user) && $user->departamento == 'Mantenimiento') ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="Seguridad" {{ (isset($user) && $user->departamento == 'Seguridad') ? 'selected' : '' }}>Seguridad</option>
                    <option value="Ama de Llaves" {{ (isset($user) && $user->departamento == 'Ama de Llaves') ? 'selected' : '' }}>Ama de Llaves</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">Solo verá tickets enviados a este departamento.</p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.users.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mostrar/ocultar campo departamento según el rol seleccionado
    document.querySelector('select[name="role"]').addEventListener('change', function() {
        const campoDpto = document.getElementById('campo-departamento');
        campoDpto.style.display = this.value === 'tecnico' ? 'block' : 'none';
    });
</script>
@endsection