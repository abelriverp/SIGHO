@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="{{ route('recepcion.perfiles.index') }}" class="text-xs text-blue-500 hover:underline">← Historial</a>
            <h2 class="text-2xl font-bold text-[#041b32] mt-1">{{ $validacion->archivo_nombre }}</h2>
            <p class="text-sm text-slate-500">
                Analizado {{ $validacion->created_at->format('d/m/Y H:i') }}
                por {{ $validacion->usuario->name ?? 'usuario eliminado' }}
            </p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('recepcion.perfiles.pdf', array_merge(['validacion' => $validacion->id], array_filter($filtros ?? [], fn ($v) => $v !== null && $v !== ''))) }}"
                class="text-sm font-bold text-[#2980B9] hover:text-[#1f618d]">Descargar PDF</a>
            @can('delete', $validacion)
            <form action="{{ route('recepcion.perfiles.destroy', $validacion) }}" method="POST"
                onsubmit="return confirm('¿Eliminar este análisis? No se puede deshacer.');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm font-bold text-red-500 hover:text-red-700">Eliminar análisis</button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Tarjetas resumen: mismas 6 categorías que el filtro de abajo
         (PerfilCorreoValidacionItem::GRUPOS_FILTRO) --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        <div class="bg-slate-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-slate-500">Total</p>
            <p class="text-xl font-bold text-slate-700">{{ $validacion->total }}</p>
        </div>
        <div class="bg-green-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-green-700">OK</p>
            <p class="text-xl font-bold text-green-700">{{ $validacion->ok }}</p>
        </div>
        <div class="bg-amber-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-amber-700">Sin correo</p>
            <p class="text-xl font-bold text-amber-700">{{ $validacion->sin_correo }}</p>
        </div>
        <div class="bg-yellow-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-yellow-700">Sufijo inválido</p>
            <p class="text-xl font-bold text-yellow-700">{{ $validacion->sufijo_invalido }}</p>
        </div>
        <div class="bg-red-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-red-700">Dominio/formato</p>
            <p class="text-xl font-bold text-red-700">{{ $validacion->dominio_invalido }}</p>
        </div>
        <div class="bg-red-200 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-red-800">Rechazados</p>
            <p class="text-xl font-bold text-red-800">{{ $validacion->rechazado_smtp }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="correo, huésped o # de reserva"
                class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="min-w-[220px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Estado</label>
            <select name="estado" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach(\App\Models\PerfilCorreoValidacionItem::GRUPOS_FILTRO as $clave => $grupo)
                    <option value="{{ $clave }}" {{ ($filtros['estado'] ?? '') === $clave ? 'selected' : '' }}>{{ $grupo['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Filtrar</button>
        @if(($filtros['buscar'] ?? '') !== '' || ($filtros['estado'] ?? '') !== '')
            <a href="{{ route('recepcion.perfiles.show', $validacion) }}" class="text-sm text-slate-500 hover:underline">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-4"># Reserva</th>
                    <th class="py-3 px-4">Huésped</th>
                    <th class="py-3 px-4">Llegada</th>
                    <th class="py-3 px-4">Salida</th>
                    <th class="py-3 px-4">Correo</th>
                    <th class="py-3 px-4">Estado</th>
                    <th class="py-3 px-4">Motivo</th>
                    <th class="py-3 px-4">Verificación</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-slate-50 hover:bg-slate-50 align-top">
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $item->numero_reserva }}</td>
                    <td class="py-3 px-4 text-slate-800">{{ $item->huesped }}</td>
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $item->fecha_llegada ?? '—' }}</td>
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $item->fecha_salida ?? '—' }}</td>
                    <td class="py-3 px-4 font-mono text-slate-800">{{ $item->email ?? '—' }}</td>
                    <td class="py-3 px-4">
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase whitespace-nowrap {{ $item->badgeClass() }}">
                            {{ $item->estadoLabel() }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-slate-500 max-w-xs">
                        {{ $item->motivo ?? '—' }}
                        @if($item->sugerencia)
                            <div class="text-xs text-slate-400 mt-1">Sugerencia: <span class="font-mono">{{ $item->sugerencia }}</span></div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $item->verificacionSmtpLabel() }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-6 px-6 text-center text-slate-400">Ninguna reserva coincide con el filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
