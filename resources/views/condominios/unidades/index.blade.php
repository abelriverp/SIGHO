@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4">

    <div class="flex justify-between items-center mb-6 mt-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Unidades</h2>
            <p class="text-sm text-gray-500">Cuartos / departamentos de la propiedad.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('condominios.estancias.index') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                ← Estancias
            </a>
            <a href="{{ route('condominios.unidades.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                + Nueva Unidad
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('condominios.unidades.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-3">
        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
            placeholder="Buscar identificador..."
            class="col-span-2 rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">

        <select name="torre" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todas las torres</option>
            @foreach($torres as $t)
                <option value="{{ $t }}" {{ ($filtros['torre'] ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>

        <select name="estatus" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todos los estatus</option>
            @foreach($estatus as $s)
                <option value="{{ $s }}" {{ ($filtros['estatus'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-[#041b32] text-white text-sm font-bold py-2 px-3 rounded hover:opacity-90">Filtrar</button>
            <a href="{{ route('condominios.unidades.index') }}" class="flex-1 text-center bg-gray-100 text-gray-700 text-sm font-bold py-2 px-3 rounded hover:bg-gray-200">Limpiar</a>
        </div>
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
                    <th class="py-3 px-4">Unidad</th>
                    <th class="py-3 px-4">Torre / Piso</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Condómino</th>
                    <th class="py-3 px-4 text-center">Estatus</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm divide-y divide-gray-100">
                @forelse($unidades as $u)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-bold text-[#041b32]">{{ $u->identificador }}</td>
                    <td class="py-3 px-4">{{ $u->torre ?? '—' }} @if($u->piso) <span class="text-gray-400">/ piso {{ $u->piso }}</span> @endif</td>
                    <td class="py-3 px-4">{{ $u->tipo_unidad ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $u->condomino->nombre ?? '— sin asignar —' }}</td>
                    <td class="py-3 px-4 text-center">
                        @php
                            $badge = match($u->estatus) {
                                'Vacia'        => 'bg-gray-100 text-gray-600',
                                'Ocupada'      => 'bg-green-100 text-green-800',
                                'Rentada'      => 'bg-blue-100 text-blue-800',
                                'Mantenimiento'=> 'bg-yellow-100 text-yellow-800',
                                default        => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badge }}">{{ $u->estatus }}</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('condominios.estancias.create', ['unidad_id' => $u->id]) }}" class="text-gray-400 hover:text-green-700 text-xs font-bold" title="Registrar estancia">
                                + Estancia
                            </a>
                            <a href="{{ route('condominios.unidades.edit', $u->id) }}" class="text-gray-400 hover:text-blue-900" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('condominios.unidades.destroy', $u->id) }}" method="POST" onsubmit="return confirm('¿Eliminar la unidad {{ addslashes($u->identificador) }}?');" class="inline">
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
                    <td colspan="6" class="py-16 text-center text-gray-400">
                        <div class="text-4xl mb-2">🏢</div>
                        <p class="font-medium">No hay unidades registradas</p>
                        <a href="{{ route('condominios.unidades.create') }}" class="text-blue-700 text-sm hover:underline mt-1 inline-block">+ Registrar la primera</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($unidades->hasPages())
        <div class="mt-4">{{ $unidades->links() }}</div>
    @endif

    <div class="text-xs text-gray-400 mt-3 text-right">
        {{ $unidades->total() }} unidad(es) total — Hotel: {{ config('app.hotel_code') }}
    </div>
</div>
@endsection
