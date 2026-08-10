<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud recibida</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hotel-blue { background-color: #041b32; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans p-4">

    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-sm w-full text-center">
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-xl font-bold text-slate-800 mb-2">¡Solicitud recibida!</h1>

        @if ($folio)
            <p class="text-sm text-slate-500 mb-1">Tu folio es</p>
            <p class="text-2xl font-bold hotel-blue text-white rounded-lg py-2 px-4 inline-block mb-4">#{{ str_pad($folio, 5, '0', STR_PAD_LEFT) }}</p>
        @endif

        <p class="text-sm text-slate-600 mb-6">
            Nuestro equipo fue notificado y atenderá tu solicitud lo antes posible.
            Si necesitas algo urgente, comunícate directamente a recepción.
        </p>

        <a href="{{ route('guest.show', $punto->token) }}" class="inline-block hotel-blue text-white font-bold py-2.5 px-6 rounded-full shadow hover:opacity-90 transition text-sm uppercase tracking-wide">
            Hacer otra solicitud
        </a>

        <p class="text-center text-gray-400 text-xs mt-8">
            © {{ date('Y') }} Departamento de Sistemas
        </p>
    </div>

</body>
</html>
