@extends('layouts.app')

@section('content')
@php $gw = $segmento->gateway; @endphp
<div class="max-w-7xl mx-auto mb-10" x-data="{ soloUsadas: false }">
    <a href="{{ route('segmentos.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Segmentos</a>
    <div class="flex flex-wrap justify-between items-start gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $segmento->nombre }} {{ $segmento->vlan ? '· VLAN '.$segmento->vlan : '' }}</h2>
            <p class="text-sm text-slate-600 font-mono">{{ $segmento->red }} · GW {{ $gw ?? '—' }} · DHCP {{ $segmento->dhcp_inicio ? $segmento->dhcp_inicio.'–'.$segmento->dhcp_fin : 'no' }}{{ $segmento->dns ? ' · DNS '.$segmento->dns : '' }}</p>
            @if($segmento->proposito)<p class="text-sm text-slate-500">{{ $segmento->proposito }}</p>@endif
        </div>
        @if(auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos())
            <a href="{{ route('segmentos.edit', $segmento) }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">Editar</a>
        @endif
    </div>

    @if($conflictos->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="font-bold text-red-800 mb-1">{{ $conflictos->count() }} IP(s) asignadas a más de un equipo</p>
            @foreach($conflictos as $ip => $grupo)
                <p class="text-sm text-red-700"><span class="font-mono font-bold">{{ $ip }}</span>: {{ $grupo->pluck('nomenclatura')->join(', ') }}</p>
            @endforeach
        </div>
    @endif

    @if(empty($hosts))
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-sm text-slate-500">El mapa se dibuja para redes de /{{ \App\Models\SegmentoRed::PREFIJO_MINIMO_MAPA }} o más pequeñas. Esta red tiene {{ number_format($segmento->totalHosts()) }} direcciones; {{ $porIp->count() }} están en el catálogo.</div>
    @else
        <div class="flex flex-wrap items-center gap-4 mb-3 text-xs">
            <span><span class="inline-block w-3 h-3 rounded bg-blue-600 align-middle"></span> Equipo</span>
            <span><span class="inline-block w-3 h-3 rounded bg-purple-600 align-middle"></span> Gateway / gestión</span>
            <span><span class="inline-block w-3 h-3 rounded bg-red-600 align-middle"></span> Conflicto o caído</span>
            <span><span class="inline-block w-3 h-3 rounded bg-amber-100 border border-amber-300 align-middle"></span> Rango DHCP</span>
            <span><span class="inline-block w-3 h-3 rounded bg-slate-50 border border-slate-200 align-middle"></span> Libre</span>
            <span class="font-bold">{{ $usadas }} usadas de {{ count($hosts) }}</span>
            <label class="ml-auto flex items-center gap-1"><input type="checkbox" x-model="soloUsadas" class="rounded"> Solo usadas</label>
        </div>
        <div class="grid grid-cols-4 sm:grid-cols-8 lg:grid-cols-16 gap-1" style="grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));">
            @foreach($hosts as $long)
                @php
                    $ip = long2ip($long);
                    $equipos = $porIp->get($ip);
                    $esGw = $ip === $gw;
                    $dev = $gestion->get($ip);
                    $usada = $equipos || $esGw || $dev;
                    $conflicto = $equipos && $equipos->count() > 1;
                    $caido = $equipos && $equipos->contains('estado_monitoreo', 'caido');
                    $clase = $conflicto || $caido ? 'bg-red-600 text-white border-red-600'
                        : ($equipos ? 'bg-blue-600 text-white border-blue-600'
                        : ($esGw || $dev ? 'bg-purple-600 text-white border-purple-600'
                        : ($segmento->enDhcp($long) ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-400')));
                    $titulo = $esGw ? 'Gateway' : ($dev ? 'Gestión: '.$dev->nombre : ($equipos ? $equipos->map(fn ($e) => $e->nomenclatura.' '.$e->tipo_equipo.($e->ubicacion ? ' ('.$e->ubicacion->etiqueta.')' : ''))->join(' / ') : 'Libre'));
                @endphp
                @if($equipos && $equipos->count() === 1)
                    <a href="{{ route('assets.show', $equipos->first()->id) }}" title="{{ $titulo }}" x-show="!soloUsadas || {{ $usada ? 'true' : 'false' }}" class="border rounded px-1 py-1 text-center text-[10px] font-mono leading-tight {{ $clase }}">.{{ explode('.', $ip)[3] }}<span class="block truncate font-sans">{{ $equipos->first()->nomenclatura }}</span></a>
                @else
                    <div title="{{ $titulo }}" x-show="!soloUsadas || {{ $usada ? 'true' : 'false' }}" class="border rounded px-1 py-1 text-center text-[10px] font-mono leading-tight {{ $clase }}">.{{ explode('.', $ip)[3] }}<span class="block truncate font-sans">{{ $esGw ? 'GW' : ($dev ? 'GEST' : ($conflicto ? '¡'.$equipos->count().'!' : '')) }}&nbsp;</span></div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
