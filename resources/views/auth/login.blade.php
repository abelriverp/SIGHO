<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Emporio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hotel-blue { background-color: #041b32 ; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">

    <div class="bg-white rounded-2xl shadow-2xl flex overflow-hidden max-w-4xl w-full h-[550px]">
        
        <div class="w-1/2 hotel-blue flex flex-col items-center justify-center text-white p-10 relative">
        <div class="mb-6">
        <img src="{{ asset('img/logo.jpg') }}" alt="Logo Hotel" class="w-40 h-auto object-contain">
        </div>
        <h1 class="text-4xl font-bold tracking-[0.2em] uppercase mb-2">Mazatlán</h1>
        <p class="text-sm opacity-70 tracking-widest">SISTEMA DE GESTIÓN TI</p>
        </div>

        <div class="w-1/2 p-12 flex flex-col justify-center">
            
            <div class="text-center mb-10">
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wide">Hotel Emporio, Mazatlán, Sin.</h2>
                <h3 class="text-2xl font-bold mt-2 text-slate-800">BIENVENIDO</h3>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm">
                    <strong>¡Error!</strong> Verifica tus datos.
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf <div class="mb-5">
                    <label class="block text-gray-500 text-xs font-bold mb-2 uppercase tracking-wide">Correo Electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" 
                        placeholder="ejemplo@emporio.com" 
                        class="w-full bg-gray-100 text-gray-700 border border-gray-200 rounded-lg py-3 px-4 focus:outline-none focus:bg-white focus:border-slate-800 transition-colors" required>
                    @error('email')
                        <span class="text-red-500 text-xs italic mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-8">
                    <label class="block text-gray-500 text-xs font-bold mb-2 uppercase tracking-wide">Contraseña</label>
                    <input type="password" name="password" 
                        placeholder="Ingresa tu contraseña" 
                        class="w-full bg-gray-100 text-gray-700 border border-gray-200 rounded-lg py-3 px-4 focus:outline-none focus:bg-white focus:border-slate-800 transition-colors" required>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-full shadow-lg hover:shadow-xl transition duration-200 uppercase text-sm tracking-wider">
                    Iniciar Sesión
                </button>
            </form>
            
            <p class="text-center text-gray-400 text-xs mt-6">
                © {{ date('Y') }} Departamento de Sistemas
            </p>
        </div> 
    </div>

</body>
</html>