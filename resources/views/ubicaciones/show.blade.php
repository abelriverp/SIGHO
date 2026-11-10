@extends('layouts.app')

@section('content')
@php
    $badgeEstado = [
        'Activo'        => 'bg-green-100 text-green-800',
        'En Reparación' => 'bg-yellow-100 text-yellow-800',
        'En almacén'    => 'bg-slate-100 text-slate-700',
        'Baja'          => 'bg-red-100 text-red-800',
    ];
@endphp
<div class="max-w-6xl mx-auto mt-2">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <a href="{{ route('ubicaciones.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Ubicaciones</a>
            <h2 class="text-3xl font-bold text-[#041b32] mt-1">{{ $ubicacion->etiqueta }}</h2>
            <p class="text-slate-600">{{ $ubicacion->tipo_nombre }}{{ $ubicacion->piso !== null ? ' · Piso '.$ubicacion->piso : '' }}{{ $ubicacion->zona ? ' · '.$ubicacion->zona : '' }}{{ $ubicacion->activa ? '' : ' · INACTIVA' }}</p>
            @if ($ubicacion->notas)<p class="text-sm text-slate-500 mt-2 whitespace-pre-line">{{ $ubicacion->notas }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('assets.create', ['ubicacion_id' => $ubicacion->id]) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded shadow">+ Activo aquí</a>
            <a href="{{ route('ubicaciones.edit', $ubicacion) }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">Editar</a>
            @can('delete', $ubicacion)
                <form method="POST" action="{{ route('ubicaciones.destroy', $ubicacion) }}" onsubmit="return confirm('¿Eliminar {{ $ubicacion->etiqueta }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="border border-red-300 text-red-700 text-sm font-bold py-2 px-4 rounded">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow sm:rounded-lg mb-10">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-slate-700 text-lg">Tickets</h3>
            <span class="text-xs text-slate-500">Últimos {{ $tickets->count() }}</span>
        </div>
        @forelse($tickets as $t)
            <a href="{{ route('tickets.show', $t->id) }}" class="flex justify-between gap-4 px-6 py-3 border-b border-gray-50 hover:bg-slate-50 text-sm">
                <span><span class="font-bold text-[#041b32]">#{{ $t->id }}</span> {{ \Illuminate\Support\Str::limit($t->descripcion, 90) }}</span>
                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $t->created_at->format('d/m/Y') }} · {{ $t->status }}</span>
            </a>
        @empty
            <p class="px-6 py-6 text-sm text-slate-400">Sin tickets ligados.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-10">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">{{ $ubicacion->activos->count() }} activo(s) en esta ubicación</div>
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#041b32] text-white uppercase text-xs">
                    <th class="py-3 px-4">Nomenclatura</th>
                    <th class="py-3 px-4">Activo</th>
                    <th class="py-3 px-4">Categoría</th>
                    <th class="py-3 px-4">Serie</th>
                    <th class="py-3 px-4">IP / MAC</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600">
                @forelse ($ubicacion->activos as $a)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2.5 px-4 font-mono text-xs">
                            <a href="{{ route('assets.show', $a->id) }}" class="font-bold text-[#041b32] hover:underline">{{ $a->nomenclatura }}</a>
                            @if ($a->padre)<div class="text-[11px] text-slate-400">↳ {{ $a->padre->nomenclatura }}</div>@endif
                        </td>
                        <td class="py-2.5 px-4">{{ $a->tipo_equipo ?? $a->nombre }} <span class="text-xs text-slate-500">{{ $a->marca }} {{ $a->modelo }}</span></td>
                        <td class="py-2.5 px-4 text-xs">{{ $a->categoria }}</td>
                        <td class="py-2.5 px-4 font-mono text-xs">{{ $a->numero_serie }}</td>
                        <td class="py-2.5 px-4 font-mono text-xs">{{ $a->ip_address }}<div class="text-slate-400">{{ $a->mac_address }}</div></td>
                        <td class="py-2.5 px-4 text-center"><span class="py-1 px-2 rounded-full text-xs font-bold {{ $badgeEstado[$a->status] ?? 'bg-slate-100' }}">{{ $a->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">Sin activos. <a href="{{ route('assets.create', ['ubicacion_id' => $ubicacion->id]) }}" class="underline">Registrar el primero</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
