@extends('layouts.app')

@section('content')
@php
    $caidos = $equipos->where('estado_monitoreo', 'caido');
    $editor = auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos();
@endphp
<div class="max-w-7xl mx-auto mb-10">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Monitoreo de equipos</h2>
            <p class="text-gray-500 text-sm mt-1">Ping cada 5 minutos. Tras {{ config('app.monitoreo_fallas', 3) }} fallas seguidas el equipo se marca caído y se abre un ticket de prioridad Alta.</p>
        </div>
        <meta http-equiv="refresh" content="120">
        <span class="text-xs text-slate-400">Se actualiza solo cada 2 minutos</span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Monitoreados</p><p class="text-2xl font-bold text-slate-800">{{ $equipos->count() }}</p></div>
        <div class="bg-white rounded-lg border {{ $caidos->count() ? 'border-red-300 bg-red-50' : 'border-slate-200' }} p-4"><p class="text-xs font-bold text-slate-400 uppercase">Caídos</p><p class="text-2xl font-bold {{ $caidos->count() ? 'text-red-700' : 'text-slate-800' }}">{{ $caidos->count() }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">En línea</p><p class="text-2xl font-bold text-green-700">{{ $equipos->where('estado_monitoreo', 'activo')->count() }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Sin datos aún</p><p class="text-2xl font-bold text-slate-500">{{ $equipos->whereNull('estado_monitoreo')->count() }}</p></div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">Estado</th><th class="py-3 px-4">Equipo</th><th class="py-3 px-4">IP</th><th class="py-3 px-4">Ubicación</th>
                <th class="py-3 px-4 text-right">Latencia</th><th class="py-3 px-4">Último ping</th><th class="py-3 px-4 text-right">Disponibilidad 30 d</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($equipos as $a)
                    <tr class="border-b border-gray-100 {{ $a->estado_monitoreo === 'caido' ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                        <td class="py-3 px-4">
                            @if($a->estado_monitoreo === 'caido')
                                <span class="text-xs font-bold text-white bg-red-600 rounded px-2 py-0.5">CAÍDO</span>
                                <div class="text-[11px] text-red-700 mt-1">desde {{ $a->caido_desde?->format('d/m H:i') }}</div>
                                @if($a->ticket_monitoreo_id)<a href="{{ route('tickets.show', $a->ticket_monitoreo_id) }}" class="text-[11px] underline text-red-700">ticket #{{ $a->ticket_monitoreo_id }}</a>@endif
                            @elseif($a->estado_monitoreo === 'activo')
                                <span class="text-xs font-bold text-green-800 bg-green-100 rounded px-2 py-0.5">En línea</span>
                                @if($a->fallas_consecutivas)<div class="text-[11px] text-orange-700 mt-1">{{ $a->fallas_consecutivas }} falla(s)</div>@endif
                            @else
                                <span class="text-xs font-bold text-slate-500 bg-slate-100 rounded px-2 py-0.5">Pendiente</span>
                            @endif
                        </td>
                        <td class="py-3 px-4"><a href="{{ route('assets.show', $a->id) }}" class="font-bold text-[#041b32] hover:underline">{{ $a->nomenclatura }}</a><div class="text-xs text-slate-500">{{ $a->tipo_equipo ?: $a->nombre }}</div></td>
                        <td class="py-3 px-4 font-mono text-xs">{{ $a->ip_address }}</td>
                        <td class="py-3 px-4 text-xs">{{ $a->ubicacion?->etiqueta ?? $a->area }}</td>
                        <td class="py-3 px-4 text-right text-xs">{{ $a->latencia_ms !== null ? $a->latencia_ms.' ms' : '—' }}</td>
                        <td class="py-3 px-4 text-xs">{{ $a->ultimo_ping_at?->diffForHumans() ?? '—' }}</td>
                        <td class="py-3 px-4 text-right text-xs font-bold {{ ($disponibilidad[$a->id] ?? 100) < 99 ? 'text-orange-700' : 'text-slate-700' }}">{{ number_format($disponibilidad[$a->id] ?? 100, 2) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-gray-400">Ningún equipo monitoreado. Actívalo en la ficha del activo (requiere IP) o con la lista de abajo.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 font-bold text-slate-700 text-sm">Caídas y recuperaciones (30 días)</div>
            @forelse($eventos as $e)
                <div class="px-5 py-2.5 border-b border-gray-50 text-sm flex justify-between gap-3">
                    <span>
                        <span class="text-xs font-bold {{ $e->tipo === 'caida' ? 'text-red-700' : 'text-green-700' }}">{{ $e->tipo === 'caida' ? '▼ Caída' : '▲ Recuperado' }}</span>
                        <a href="{{ route('assets.show', $e->asset_id) }}" class="font-bold text-[#041b32] hover:underline">{{ $e->asset?->nomenclatura }}</a>
                        @if($e->duracion_min !== null)<span class="text-xs text-slate-500">tras {{ $e->duracion_min }} min</span>@endif
                        @if($e->ticket_id)<a href="{{ route('tickets.show', $e->ticket_id) }}" class="text-xs underline text-slate-500">#{{ $e->ticket_id }}</a>@endif
                    </span>
                    <span class="text-xs text-slate-400 whitespace-nowrap">{{ $e->ocurrido_at->format('d/m H:i') }}</span>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400">Sin eventos.</p>
            @endforelse
        </div>

        @if($editor && $candidatos->isNotEmpty())
            <form method="POST" action="{{ route('monitoreo.activar') }}" class="bg-white rounded-lg shadow overflow-hidden">
                @csrf
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-slate-700 text-sm">Equipos con IP sin monitorear</span>
                    <button class="bg-[#041b32] text-white text-xs font-bold px-3 py-1.5 rounded">Monitorear seleccionados</button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @foreach($candidatos as $c)
                        <label class="flex items-center gap-3 px-5 py-2 border-b border-gray-50 text-sm hover:bg-slate-50">
                            <input type="checkbox" name="ids[]" value="{{ $c->id }}" class="rounded" @checked($c->categoria === 'Red')>
                            <span class="font-bold text-[#041b32]">{{ $c->nomenclatura }}</span>
                            <span class="text-xs text-slate-500">{{ $c->tipo_equipo ?: $c->categoria }}</span>
                            <span class="ml-auto font-mono text-xs">{{ $c->ip_address }}</span>
                        </label>
                    @endforeach
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
