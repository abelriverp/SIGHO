<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Contraseña - Hotel Emporio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hotel-blue { background-color: #041b32 ; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">

    <div class="bg-white rounded-2xl shadow-2xl flex overflow-hidden max-w-4xl w-full h-[550px]">
        
        <!-- Panel Izquierdo Corporativo -->
        <div class="w-1/2 hotel-blue flex flex-col items-center justify-center text-white p-10 relative">
            <div class="mb-6">
                <img src="{{ asset('img/logo.jpg') }}" alt="Logo Hotel" class="w-40 h-auto object-contain">
            </div>
            <h1 class="text-4xl font-bold tracking-[0.2em] uppercase mb-2">{{ config('app.hotel_ciudad') }}</h1>
            <p class="text-sm opacity-70 tracking-widest">SISTEMA DE GESTIÓN HOTELERA</p>
        </div>

        <!-- Panel Derecho: Formulario de Actualización -->
        <div class="w-1/2 p-12 flex flex-col justify-center">
            
            <div class="text-center mb-6">
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ config('app.hotel_ubicacion') }}</h2>
                <h3 class="text-2xl font-bold mt-2 text-slate-800">Actualizar Contraseña</h3>
            </div>

            <!-- Alerta informativa del Middleware -->
            @if (session('upgrade_password'))
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-lg mb-4 text-xs">
                    {{ session('upgrade_password') }}
                </div>
            @endif

            <!-- Manejo de Errores -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-2 rounded-lg mb-4 text-xs">
                    @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <form action="{{ route('password.change.update') }}" method="POST">
                @csrf 

                @if (! auth()->user()->must_change_password)
                <div class="mb-4">
                    <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wide">Contraseña actual</label>
                    <input type="password" name="password_actual" autocomplete="current-password"
                        class="w-full bg-gray-100 text-gray-700 border border-gray-200 rounded-lg py-2.5 px-4 focus:outline-none focus:bg-white focus:border-slate-800 transition-colors" required>
                </div>
                @endif

                <div class="mb-4">
                    <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wide">Nueva Contraseña</label>
                    <input type="password" name="password" autocomplete="new-password" minlength="8"
                        placeholder="Mínimo 8, con letras y números" 
                        class="w-full bg-gray-100 text-gray-700 border border-gray-200 rounded-lg py-2.5 px-4 focus:outline-none focus:bg-white focus:border-slate-800 transition-colors" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-500 text-xs font-bold mb-1 uppercase tracking-wide">Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" 
                        placeholder="Repite tu contraseña exactamente" 
                        class="w-full bg-gray-100 text-gray-700 border border-gray-200 rounded-lg py-2.5 px-4 focus:outline-none focus:bg-white focus:border-slate-800 transition-colors" required>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-full shadow-lg hover:shadow-xl transition duration-200 uppercase text-sm tracking-wider">
                    Guardar y Continuar
                </button>
            </form>

            <!-- 🛠️ BOTÓN PARA CERRAR SESIÓN Y ESCAPAR DE LA PANTALLA -->
            <form action="{{ route('logout') }}" method="POST" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-xs text-gray-400 hover:text-red-500 underline tracking-wide uppercase transition-colors duration-200">
                    Cancelar y Cerrar Sesión
                </button>
            </form>
            
            <p class="text-center text-gray-300 text-[10px] mt-6 uppercase tracking-wider">
                © {{ date('Y') }} Departamento de Sistemas
            </p>
        </div> 
    </div>

</body>
</html>