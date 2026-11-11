@extends('layouts.app')

@section('content')
@php $punto = fn ($a) => ! ($a?->monitorear) ? 'bg-slate-300' : ($a->estado_monitoreo === 'caido' ? 'bg-red-500' : ($a->estado_monitoreo === 'activo' ? 'bg-green-500' : 'bg-amber-400')); @endphp
<div class="max-w-5xl mx-auto mb-10">
    <a href="{{ route('ubicaciones.show', $ubicacion) }}" class="text-sm text-gray-500 hover:text-[#041b32]">← {{ $ubicacion->etiqueta }}</a>
    <div class="flex flex-wrap justify-between items-end gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Rack · {{ $ubicacion->nombre ?: $ubicacion->codigo }}</h2>
            <p class="text-gray-500 text-sm">{{ $ubicacion->zona }}{{ $ubicacion->piso ? ' · piso '.$ubicacion->piso : '' }}. Ordenado por posición (U) de arriba hacia abajo.</p>
        </div>
        <a href="{{ route('network.topologia') }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">Topología completa</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-slate-800 rounded-lg p-3 shadow-inner">
            @forelse($equipos as $d)
                <a href="{{ route('network.show', $d->id) }}" class="flex items-center gap-3 bg-slate-700 hover:bg-slate-600 text-white rounded px-3 py-2.5 mb-1.5">
                    <span class="font-mono text-xs bg-slate-900 rounded px-1.5 py-0.5 w-12 text-center">{{ $d->posicion_rack ?: '—' }}</span>
                    <span class="w-2.5 h-2.5 rounded-full {{ $punto($d->asset) }}"></span>
                    <span class="font-bold text-sm flex-1 truncate">{{ $d->nombre }}</span>
                    <span class="text-xs text-slate-300">{{ $d->tipo }} · {{ $d->ocupados_count }}/{{ $d->total_ports }}</span>
                </a>
                @if($d->uplink)<p class="text-[10px] text-slate-400 -mt-1 mb-1.5 ml-16">cuelga de {{ $d->uplink->nombre }}{{ $d->uplink_puerto ? ' · '.$d->uplink_puerto : '' }}</p>@endif
            @empty
                <p class="text-slate-300 text-sm p-4 text-center">No hay equipos de red con esta ubicación. Edita cada switch y elige "{{ $ubicacion->codigo }}" en Ubicación.</p>
            @endforelse
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden h-fit">
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">Otros equipos en este cuarto ({{ $otros->count() }})</div>
            @forelse($otros as $a)
                <a href="{{ route('assets.show', $a->id) }}" class="flex items-center gap-2 px-5 py-2 border-b border-gray-50 hover:bg-slate-50 text-sm">
                    <span class="w-2 h-2 rounded-full {{ $punto($a) }}"></span>
                    <span class="font-mono text-xs">{{ $a->nomenclatura }}</span>
                    <span class="text-slate-500 flex-1 truncate">{{ $a->tipo_equipo ?: $a->nombre }}</span>
                    <span class="text-xs font-mono text-slate-400">{{ $a->ip_address }}</span>
                </a>
            @empty
                <p class="px-5 py-4 text-sm text-slate-400">Servidores, UPS, NVR y demás activos con esta ubicación aparecen aquí.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
