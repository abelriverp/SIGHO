@extends('layouts.app')
@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="max-w-5xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Puntos QR (Portal de huésped)</h2>
            <p class="text-sm text-slate-500">Un código por habitación/unidad. El huésped lo escanea y puede reportar un problema o pedir artículos, sin iniciar sesión.</p>
        </div>
        <a href="{{ route('puntos-qr.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nuevo punto</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">QR</th>
                    <th class="py-3 px-6">Habitación / Unidad</th>
                    <th class="py-3 px-6">Enlace</th>
                    <th class="py-3 px-6">Estado</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($puntos as $punto)
                <tr class="border-b border-slate-50 hover:bg-slate-50 align-top">
                    <td class="py-4 px-6">
                        <a href="{{ route('puntos-qr.imprimir', $punto->id) }}" title="Ver en grande / descargar / imprimir">
                            <div id="qr-{{ $punto->id }}" class="qr-canvas"></div>
                        </a>
                    </td>
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $punto->identificador }}</td>
                    <td class="py-4 px-6 text-slate-500 text-xs break-all max-w-xs">{{ $punto->urlPublica() }}</td>
                    <td class="py-4 px-6">
                        @if($punto->activo)
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activo</span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactivo</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-right">
                        <div class="flex flex-col items-end gap-2">
                            <a href="{{ route('puntos-qr.imprimir', $punto->id) }}" class="text-[#041b32] hover:opacity-70 font-bold text-xs">🖨️ Ver / Descargar</a>
                            <form action="{{ route('puntos-qr.estado', $punto->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-blue-500 hover:text-blue-700 font-bold text-xs">
                                    {{ $punto->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                            <form action="{{ route('puntos-qr.regenerar-token', $punto->id) }}" method="POST" onsubmit="return confirm('El QR impreso actual dejará de funcionar. ¿Generar uno nuevo?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-amber-600 hover:text-amber-700 font-bold text-xs">Regenerar token</button>
                            </form>
                            <form action="{{ route('puntos-qr.destroy', $punto->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este punto QR? El código impreso dejará de funcionar.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 px-6 text-center text-slate-400">Todavía no hay puntos QR para esta propiedad.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var puntos = @json($puntos->map(fn ($p) => ['id' => $p->id, 'url' => $p->urlPublica()]));
        puntos.forEach(function (p) {
            var el = document.getElementById('qr-' + p.id);
            if (el && window.QRCode) {
                new QRCode(el, { text: p.url, width: 90, height: 90 });
            }
        });
    });
</script>
@endsection
