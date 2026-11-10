@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="{{ route('recepcion.extensiones.index') }}" class="text-xs text-blue-500 hover:underline">← Historial</a>
            <h2 class="text-2xl font-bold text-[#041b32] mt-1">{{ $deteccion->archivo_salidas }} vs {{ $deteccion->archivo_llegadas }}</h2>
            <p class="text-sm text-slate-500">
                Comparado {{ $deteccion->created_at->format('d/m/Y H:i') }}
                por {{ $deteccion->usuario->name ?? 'usuario eliminado' }}
                — {{ $deteccion->total_coincidencias }} posible(s) extensión(es)
            </p>
        </div>
        @can('delete', $deteccion)
        <form action="{{ route('recepcion.extensiones.destroy', $deteccion) }}" method="POST"
            onsubmit="return confirm('¿Eliminar esta comparación? No se puede deshacer.');">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm font-bold text-red-500 hover:text-red-700">Eliminar comparación</button>
        </form>
        @endcan
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[240px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar huésped</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="ej. García"
                class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Filtrar</button>
        @if(($filtros['buscar'] ?? '') !== '')
            <a href="{{ route('recepcion.extensiones.show', $deteccion) }}" class="text-sm text-slate-500 hover:underline">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Huésped saliendo</th>
                    <th class="py-3 px-6">Huésped llegando</th>
                    <th class="py-3 px-6 text-center">Confianza</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $item->confianzaAlta() ? 'bg-green-50' : 'bg-yellow-50' }}">
                    <td class="py-3 px-6 text-slate-800">{{ $item->huesped_salida }}</td>
                    <td class="py-3 px-6 text-slate-800">{{ $item->huesped_llegada }}</td>
                    <td class="py-3 px-6 text-center font-bold {{ $item->confianzaAlta() ? 'text-green-700' : 'text-yellow-700' }}">
                        {{ $item->similitud }}%
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-6 px-6 text-center text-slate-400">
                    @if(($filtros['buscar'] ?? '') !== '')
                        Ningún huésped coincide con el filtro.
                    @else
                        No se encontraron posibles extensiones de estancia en esta comparación.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
