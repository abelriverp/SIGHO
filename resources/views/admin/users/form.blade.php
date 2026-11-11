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

            {{-- Fase 5: login con Active Directory --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Usuario de red (Active Directory)</label>
                <input type="text" name="usuario_ad" value="{{ old('usuario_ad', $user->usuario_ad ?? '') }}" maxlength="100"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 font-mono" placeholder="Ej: arivera">
                <p class="text-[10px] text-slate-400 mt-1 italic">Opcional. Si Active Directory está habilitado, este usuario entra con su contraseña de Windows.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">
                    {{ isset($user) ? 'Nueva Contraseña (dejar en blanco para no cambiar)' : 'Contraseña Temporal' }}
                </label>
                <div class="flex gap-2">
                    <input type="password" id="campo-password" name="password" {{ isset($user) ? '' : 'required' }} minlength="8"
                        autocomplete="new-password"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 font-mono">
                    <button type="button" onclick="togglePasswordVisible()"
                        class="whitespace-nowrap bg-slate-200 text-slate-700 font-bold text-xs px-3 rounded-md hover:bg-slate-300">
                        👁 Ver
                    </button>
                    <button type="button" onclick="generarPasswordAleatoria()"
                        class="whitespace-nowrap bg-slate-200 text-slate-700 font-bold text-xs px-3 rounded-md hover:bg-slate-300">
                        🎲 Generar
                    </button>
                </div>
                @if(!isset($user))
                    <p class="text-[10px] text-slate-400 mt-1 italic">El usuario deberá cambiarla en su primer inicio de sesión.</p>
                @else
                    <p class="text-[10px] text-slate-400 mt-1 italic">Si capturas una contraseña aquí, se le pedirá cambiarla en su próximo inicio de sesión.</p>
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
                        ⚙️ Administrador — Acceso total al sistema
                    </option>
                </select>
            </div>

            <div id="campo-hotel" style="{{ (isset($user) && $user->role == 'admin') ? 'display:none' : '' }}">
                <label class="block text-sm font-bold text-slate-700 mb-1">Propiedad</label>
                <select name="hotel_code" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="">Selecciona...</option>
                    @foreach($hoteles as $hotel)
                        <option value="{{ $hotel->hotel_code }}" {{ (isset($user) && $user->hotel_code == $hotel->hotel_code) ? 'selected' : '' }}>
                            {{ $hotel->nombre }} ({{ $hotel->hotel_code }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">El usuario solo verá los datos de esta propiedad. No aplica a Administradores, que ven todas.</p>
            </div>

            {{-- Campo de departamento solo para técnicos --}}
            <div id="campo-departamento" style="{{ (isset($user) && $user->role == 'tecnico') ? '' : 'display:none' }}">
                <label class="block text-sm font-bold text-slate-700 mb-1">Departamento que atiende</label>
                <select name="departamento" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="">Selecciona...</option>
                    @php
                        // Se listan los departamentos activos; si el usuario ya tenía
                        // asignado uno que después se desactivó, se agrega igual para
                        // no perder de vista su valor actual (pero tendrá que cambiarlo
                        // si quiere guardar el formulario, ya que no seguirá activo).
                        $departamentosForm = \App\Models\Departamento::activos()->orderBy('nombre')->pluck('nombre');
                        if (isset($user) && $user->departamento && !$departamentosForm->contains($user->departamento)) {
                            $departamentosForm->push($user->departamento);
                        }
                    @endphp
                    @foreach($departamentosForm as $depto)
                        <option value="{{ $depto }}" {{ (isset($user) && $user->departamento == $depto) ? 'selected' : '' }}>
                            {{ $depto === 'Sistemas' ? 'Sistemas / IT' : $depto }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">Solo verá tickets enviados a este departamento.</p>
            </div>

            {{-- Área de origen del usuario: de dónde reporta (recepción, ama de
                 llaves, sistemas, etc.). Se fija aquí en vez de preguntarla en
                 cada ticket, para que quede consistente en reportes y filtros.
                 No aplica a Administradores (ven todo, igual que Propiedad). --}}
            <div id="campo-area" style="{{ (isset($user) && $user->role == 'admin') ? 'display:none' : '' }}">
                <label class="block text-sm font-bold text-slate-700 mb-1">Área</label>
                <select name="area" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="">Selecciona...</option>
                    @foreach(\App\Models\Area::orderBy('nombre')->get() as $areaOpcion)
                        <option value="{{ $areaOpcion->nombre }}" {{ (isset($user) && $user->area == $areaOpcion->nombre) ? 'selected' : '' }}>
                            {{ $areaOpcion->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">Área desde la que reporta fallas/requerimientos. Se prellenará sola en su formulario de tickets.</p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.users.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Genera una contraseña temporal segura y la coloca en el campo de contraseña
    function generarPasswordAleatoria() {
        // 10 caracteres con letras y al menos 2 números (política: mín. 8, letras y números).
        // Sin O/0, l/1/I para que se pueda dictar sin confusiones.
        const letras = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        const numeros = '23456789';
        const azar = (n) => { const a = new Uint32Array(1); crypto.getRandomValues(a); return a[0] % n; };
        let chars = [];
        for (let i = 0; i < 8; i++) chars.push(letras.charAt(azar(letras.length)));
        for (let i = 0; i < 2; i++) chars.push(numeros.charAt(azar(numeros.length)));
        for (let i = chars.length - 1; i > 0; i--) { const j = azar(i + 1); [chars[i], chars[j]] = [chars[j], chars[i]]; }
        const pass = chars.join('');

        const campo = document.getElementById('campo-password');
        campo.value = pass;
        campo.type = 'text'; // se revela para que el admin pueda copiarla y dársela al usuario

        // Intenta copiarla al portapapeles como ayuda extra
        if (navigator.clipboard) {
            navigator.clipboard.writeText(pass).catch(() => {});
        }
    }

    // Muestra/oculta manualmente el contenido del campo de contraseña
    function togglePasswordVisible() {
        const campo = document.getElementById('campo-password');
        campo.type = campo.type === 'password' ? 'text' : 'password';
    }

    // Mostrar/ocultar campo departamento según el rol seleccionado
    document.querySelector('select[name="role"]').addEventListener('change', function() {
        const campoDpto = document.getElementById('campo-departamento');
        campoDpto.style.display = this.value === 'tecnico' ? 'block' : 'none';

        // El Administrador ve todas las propiedades: no necesita elegir una
        const campoHotel = document.getElementById('campo-hotel');
        campoHotel.style.display = this.value === 'admin' ? 'none' : 'block';

        // El Administrador tampoco necesita área fija: ve todo sin importar
        // desde dónde reporte.
        const campoArea = document.getElementById('campo-area');
        campoArea.style.display = this.value === 'admin' ? 'none' : 'block';
    });
</script>
@endsection