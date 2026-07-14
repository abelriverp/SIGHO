@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4">

    <div class="flex justify-between items-center mb-6 mt-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Check-in / Estancias</h2>
            <p class="text-sm text-gray-500">Reservas, llegadas y salidas por unidad.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('condominios.unidades.index') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                Unidades
            </a>
            <a href="{{ route('condominios.condominos.index') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                Condóminos
            </a>
            <a href="{{ route('condominios.reportes') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                📊 Reportes
            </a>
            <a href="{{ route('condominios.estancias.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                + Nueva Estancia
            </a>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">En estancia ahora</p>
                <p class="text-2xl font-bold text-green-700">{{ $enCurso }}</p>
            </div>
            <span class="text-3xl">🏠</span>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Llegadas esperadas hoy</p>
                <p class="text-2xl font-bold text-blue-700">{{ $reservasHoy }}</p>
            </div>
            <span class="text-3xl">📅</span>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('condominios.estancias.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-3">
        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
            placeholder="Buscar visitante..."
            class="col-span-2 rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">

        <select name="unidad" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todas las unidades</option>
            @foreach($unidades as $u)
                <option value="{{ $u->id }}" {{ ($filtros['unidad'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->nombreCompleto() }}</option>
            @endforeach
        </select>

        <select name="estado" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todos los estados</option>
            @foreach($estados as $clave => $label)
                <option value="{{ $clave }}" {{ ($filtros['estado'] ?? '') == $clave ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-[#041b32] text-white text-sm font-bold py-2 px-3 rounded hover:opacity-90">Filtrar</button>
            <a href="{{ route('condominios.estancias.index') }}" class="flex-1 text-center bg-gray-100 text-gray-700 text-sm font-bold py-2 px-3 rounded hover:bg-gray-200">Limpiar</a>
        </div>
    </form>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#041b32] text-white uppercase text-xs">
                    <th class="py-3 px-4">Unidad</th>
                    <th class="py-3 px-4">Visitante</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Llegada</th>
                    <th class="py-3 px-4">Salida</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm divide-y divide-gray-100">
                @forelse($estancias as $e)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-bold text-[#041b32]">
                        <a href="{{ route('condominios.estancias.show', $e->id) }}" class="hover:underline">{{ $e->unidad->nombreCompleto() ?? '—' }}</a>
                    </td>
                    <td class="py-3 px-4">
                        {{ $e->nombre_visitante }}
                        @if($e->num_acompanantes > 0)<span class="text-xs text-gray-400">(+{{ $e->num_acompanantes }})</span>@endif
                    </td>
                    <td class="py-3 px-4 text-xs">{{ $e->tipoVisitanteLabel() }}</td>
                    <td class="py-3 px-4 text-xs">{{ $e->fecha_llegada_prevista->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 text-xs">{{ optional($e->fecha_salida_prevista)->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-3 px-4 text-center">
                        @php
                            $badge = match($e->estado) {
                                'reservada'  => 'bg-gray-100 text-gray-600',
                                'confirmada' => 'bg-indigo-100 text-indigo-800',
                                'checkin'    => 'bg-green-100 text-green-800',
                                'checkout'   => 'bg-blue-100 text-blue-800',
                                'cancelada'  => 'bg-red-100 text-red-800',
                                'no_show'    => 'bg-yellow-100 text-yellow-800',
                                default      => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badge }}">{{ $e->estadoLabel() }}</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if($e->puedeHacerCheckin())
                                <form action="{{ route('condominios.estancias.checkin', $e->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-green-700 hover:underline">Check-in</button>
                                </form>
                            @endif
                            @if($e->puedeHacerCheckout())
                                <form action="{{ route('condominios.estancias.checkout', $e->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-blue-700 hover:underline">Check-out</button>
                                </form>
                            @endif
                            <a href="{{ route('condominios.estancias.show', $e->id) }}" class="text-gray-400 hover:text-[#041b32]" title="Ver detalle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center text-gray-400">
                        <div class="text-4xl mb-2">📋</div>
                        <p class="font-medium">No hay estancias registradas</p>
                        <a href="{{ route('condominios.estancias.create') }}" class="text-blue-700 text-sm hover:underline mt-1 inline-block">+ Registrar la primera</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($estancias->hasPages())
        <div class="mt-4">{{ $estancias->links() }}</div>
    @endif
</div>
@endsection
