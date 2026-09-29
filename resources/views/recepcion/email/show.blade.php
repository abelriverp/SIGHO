@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="{{ route('recepcion.email.index') }}" class="text-xs text-blue-500 hover:underline">← Historial</a>
            <h2 class="text-2xl font-bold text-[#041b32] mt-1">{{ $validacion->archivo_nombre }}</h2>
            <p class="text-sm text-slate-500">
                Analizado {{ $validacion->created_at->format('d/m/Y H:i') }}
                por {{ $validacion->usuario->name ?? 'usuario eliminado' }}
            </p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('recepcion.email.pdf', array_merge(['validacion' => $validacion->id], array_filter($filtros ?? [], fn ($v) => $v !== null && $v !== ''))) }}"
                class="text-sm font-bold text-[#2980B9] hover:text-[#1f618d]">Descargar PDF</a>
            @can('delete', $validacion)
            <form action="{{ route('recepcion.email.destroy', $validacion) }}" method="POST"
                onsubmit="return confirm('¿Eliminar este análisis? No se puede deshacer.');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm font-bold text-red-500 hover:text-red-700">Eliminar análisis</button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-slate-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-slate-500">Total</p>
            <p class="text-xl font-bold text-slate-700">{{ $validacion->total }}</p>
        </div>
        <div class="bg-green-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-green-700">Correctos</p>
            <p class="text-xl font-bold text-green-700">{{ $validacion->ok }}</p>
        </div>
        <div class="bg-yellow-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-yellow-700">Errores tipográficos</p>
            <p class="text-xl font-bold text-yellow-700">{{ $validacion->typos }}</p>
        </div>
        <div class="bg-blue-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-blue-700">Genéricos (OTA)</p>
            <p class="text-xl font-bold text-blue-700">{{ $validacion->genericos }}</p>
        </div>
        <div class="bg-red-100 rounded-lg p-4 text-center">
            <p class="text-xs font-bold text-red-700">Falsos/Inválidos</p>
            <p class="text-xl font-bold text-red-700">{{ $validacion->falsos }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar correo</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="ej. juan@..."
                class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Estado</label>
            <select name="estado" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach(\App\Models\EmailValidacionItem::GRUPOS_FILTRO as $clave => $grupo)
                    <option value="{{ $clave }}" {{ ($filtros['estado'] ?? '') === $clave ? 'selected' : '' }}>{{ $grupo['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Filtrar</button>
        @if(($filtros['buscar'] ?? '') !== '' || ($filtros['estado'] ?? '') !== '')
            <a href="{{ route('recepcion.email.show', $validacion) }}" class="text-sm text-slate-500 hover:underline">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Correo electrónico</th>
                    <th class="py-3 px-6">Estado</th>
                    <th class="py-3 px-6 text-center">Puntaje</th>
                    <th class="py-3 px-6">Sugerencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-3 px-6 font-mono text-slate-800">{{ $item->email }}</td>
                    <td class="py-3 px-6">
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase {{ $item->badgeClass() }}">
                            {{ $item->estadoLabel() }}
                        </span>
                    </td>
                    <td class="py-3 px-6 text-center text-slate-600">{{ $item->score }}</td>
                    <td class="py-3 px-6 text-slate-500">{{ $item->sugerencia ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 px-6 text-center text-slate-400">Ningún correo coincide con el filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
