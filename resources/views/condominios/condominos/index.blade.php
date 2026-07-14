@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4">

    <div class="flex justify-between items-center mb-6 mt-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Condóminos</h2>
            <p class="text-sm text-gray-500">Propietarios registrados en la propiedad.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('condominios.estancias.index') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                ← Estancias
            </a>
            <a href="{{ route('condominios.condominos.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                + Nuevo Condómino
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('condominios.condominos.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-5 flex gap-3">
        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
            placeholder="Buscar nombre..."
            class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        <button type="submit" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded hover:opacity-90">Buscar</button>
        <a href="{{ route('condominios.condominos.index') }}" class="bg-gray-100 text-gray-700 text-sm font-bold py-2 px-4 rounded hover:bg-gray-200">Limpiar</a>
    </form>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#041b32] text-white uppercase text-xs">
                    <th class="py-3 px-4">Nombre</th>
                    <th class="py-3 px-4">Contacto</th>
                    <th class="py-3 px-4 text-center">Unidades</th>
                    <th class="py-3 px-4">Desde</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm divide-y divide-gray-100">
                @forelse($condominos as $c)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4">
                        <a href="{{ route('condominios.condominos.show', $c->id) }}" class="font-bold text-[#041b32] hover:underline">{{ $c->nombre }}</a>
                    </td>
                    <td class="py-3 px-4 text-xs">
                        {{ $c->telefono ?? '—' }}<br>
                        <span class="text-gray-400">{{ $c->email ?? '' }}</span>
                    </td>
                    <td class="py-3 px-4 text-center font-bold">{{ $c->unidades_count }}</td>
                    <td class="py-3 px-4 text-xs">{{ $c->fecha_alta?->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('condominios.condominos.show', $c->id) }}" class="text-gray-400 hover:text-[#041b32]" title="Ver detalle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </a>
                            <a href="{{ route('condominios.condominos.edit', $c->id) }}" class="text-gray-400 hover:text-blue-900" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('condominios.condominos.destroy', $c->id) }}" method="POST" onsubmit="return confirm('¿Eliminar a {{ addslashes($c->nombre) }}?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-16 text-center text-gray-400">
                        <div class="text-4xl mb-2">👤</div>
                        <p class="font-medium">No hay condóminos registrados</p>
                        <a href="{{ route('condominios.condominos.create') }}" class="text-blue-700 text-sm hover:underline mt-1 inline-block">+ Registrar el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($condominos->hasPages())
        <div class="mt-4">{{ $condominos->links() }}</div>
    @endif
</div>
@endsection
