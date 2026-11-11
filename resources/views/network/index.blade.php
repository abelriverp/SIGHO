@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 mt-6">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Red e infraestructura</h2>
            <p class="text-gray-500 text-sm">Switches, routers y patch panels con sus puertos</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('monitoreo.index') }}" class="border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded text-sm">📡 Monitoreo</a>
            <a href="{{ route('network.topologia') }}" class="border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded text-sm">Topología</a>
            <a href="{{ route('segmentos.index') }}" class="border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded text-sm">Segmentos / VLANs</a>
            <a href="{{ route('network.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow text-sm">+ Nuevo equipo de red</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($devices as $device)
            @php $pct = $device->ports_count ? round($device->ocupados_count / $device->ports_count * 100) : 0; $mon = $device->asset?->monitorear ? $device->asset->estado_monitoreo : null; @endphp
            <a href="{{ route('network.show', $device->id) }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-lg hover:border-blue-400 transition">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">{{ $device->nombre }}</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-800">{{ $device->tipo }}</span>
                        @if($device->ip_gestion)<span class="text-xs font-mono text-slate-500 ml-1">{{ $device->ip_gestion }}</span>@endif
                    </div>
                    @if($mon === 'caido')<span class="text-xs font-bold text-white bg-red-600 rounded px-2 py-0.5">CAÍDO</span>
                    @elseif($mon === 'activo')<span class="text-xs font-bold text-green-800 bg-green-100 rounded px-2 py-0.5">En línea</span>@endif
                </div>
                <p class="text-sm text-gray-600">{{ $device->ubicacionCatalogo?->etiqueta ?? $device->ubicacion ?? 'Ubicación no especificada' }}</p>
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-slate-500 mb-1"><span>{{ $device->ocupados_count }} / {{ $device->ports_count }} puertos ocupados</span><span>{{ $pct }}%</span></div>
                    <div class="h-2 bg-slate-100 rounded"><div class="h-2 rounded {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 75 ? 'bg-orange-400' : 'bg-blue-600') }}" style="width: {{ $pct }}%"></div></div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400 bg-white rounded-lg shadow-sm border border-gray-100">No hay equipos de red registrados aún.</div>
        @endforelse
    </div>
</div>
@endsection
