<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de servicio — Habitación {{ $punto->identificador }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        .hotel-blue { background-color: #041b32; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <div class="hotel-blue text-white text-center py-6 px-4">
        <img src="{{ asset('img/logo.jpg') }}" alt="Logo" class="w-16 h-16 mx-auto rounded-full object-contain bg-white mb-2">
        <h1 class="text-lg font-bold uppercase tracking-wide">¿En qué te podemos ayudar?</h1>
        <p class="text-xs opacity-70 mt-1">Habitación / Unidad {{ $punto->identificador }}</p>
    </div>

    <div class="max-w-md mx-auto p-4 -mt-4">
        <div class="bg-white rounded-2xl shadow-xl p-6" x-data="{ tipo: 'problema' }">

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-5 rounded text-sm">
                    <ul class="list-disc ml-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('guest.store', $punto->token) }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="tipo = 'problema'"
                        :class="tipo === 'problema' ? 'hotel-blue text-white' : 'bg-gray-100 text-gray-500'"
                        class="rounded-lg py-3 text-sm font-bold transition">
                        🛠️ Reportar un problema
                    </button>
                    <button type="button" @click="tipo = 'amenidad'"
                        :class="tipo === 'amenidad' ? 'hotel-blue text-white' : 'bg-gray-100 text-gray-500'"
                        class="rounded-lg py-3 text-sm font-bold transition"
                        {{ $articulos->isEmpty() ? 'disabled' : '' }}>
                        🧴 Pedir artículos
                    </button>
                </div>
                <input type="hidden" name="tipo_solicitud" x-model="tipo">

                @if ($articulos->isEmpty())
                    <p class="text-xs text-slate-400 italic -mt-3">Por ahora no hay artículos disponibles para pedir desde aquí; puedes reportar un problema o llamar a recepción.</p>
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Tu nombre (opcional)</label>
                    <input type="text" name="nombre_huesped" value="{{ old('nombre_huesped') }}"
                        placeholder="Para que sepamos a quién atender"
                        class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2.5 px-3 text-sm">
                </div>

                <div x-show="tipo === 'problema'" x-cloak>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Cuéntanos qué pasa</label>
                    <textarea name="descripcion" rows="4" placeholder="Ej. el aire acondicionado no enfría"
                        class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2.5 px-3 text-sm">{{ old('descripcion') }}</textarea>
                </div>

                @if ($articulos->isNotEmpty())
                    <div x-show="tipo === 'amenidad'" x-cloak class="space-y-3">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide">¿Qué necesitas?</label>
                        @foreach ($articulos as $articulo)
                            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg py-2 px-3">
                                <span class="text-sm text-slate-700">{{ $articulo->nombre }}</span>
                                <input type="number" name="articulos[{{ $articulo->id }}]" min="0" max="5"
                                    value="{{ old('articulos.' . $articulo->id, 0) }}"
                                    class="w-16 text-center bg-white border border-gray-300 rounded py-1 text-sm">
                            </div>
                        @endforeach
                        <p class="text-[10px] text-slate-400 italic">Máximo 5 por artículo.</p>
                    </div>
                @endif

                <button type="submit" class="w-full hotel-blue text-white font-bold py-3 rounded-full shadow-lg hover:opacity-90 transition uppercase text-sm tracking-wider">
                    Enviar solicitud
                </button>
            </form>
        </div>

        <p class="text-center text-gray-400 text-xs mt-6 pb-6">
            © {{ date('Y') }} Departamento de Sistemas
        </p>
    </div>

</body>
</html>
