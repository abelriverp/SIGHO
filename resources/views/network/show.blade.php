@extends('layouts.app')

@section('content')
@php
    // Datos de cada puerto para el panel lateral (Alpine), sin interpolar
    // texto libre dentro de atributos @click (antes se rompía con comillas).
    $puertos = $device->ports->mapWithKeys(fn ($p) => [$p->id => [
        'id' => $p->id, 'num' => $p->port_number, 'connected_to' => $p->connected_to ?? '',
        'asset_id' => $p->asset_id ? (string) $p->asset_id : '', 'vlan' => $p->vlan ?? '', 'notas' => $p->notas ?? '',
        'speed' => array_key_exists($p->speed, $velocidades) ? $p->speed : 'Gigabit',
    ]]);
@endphp
<style>
    .hardware-scroll::-webkit-scrollbar { height: 10px; }
    .hardware-scroll::-webkit-scrollbar-track { background: #1e293b; border-radius: 8px; }
    .hardware-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 8px; }
</style>

<div x-data="{
        panelOpen: false,
        puertos: @js($puertos),
        p: { id: null, num: null, connected_to: '', asset_id: '', vlan: '', notas: '', speed: 'Gigabit' },
        abrir(id) { this.p = Object.assign({}, this.puertos[id]); this.panelOpen = true; }
    }"
    class="max-w-7xl mx-auto px-4 mt-6 relative">

    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <a href="{{ route('network.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Red</a>
            <h2 class="text-3xl font-bold text-[#041b32] tracking-tight">{{ $device->nombre }}</h2>
            <p class="text-gray-500 font-medium">
                {{ $device->tipo }} · {{ $device->ubicacionCatalogo?->etiqueta ?? $device->ubicacion ?? 'sin ubicación' }}
                @if($device->ip_gestion) · <span class="font-mono">{{ $device->ip_gestion }}</span>@endif
                @if($device->asset) · <a href="{{ route('assets.show', $device->asset->id) }}" class="text-blue-700 underline">ficha {{ $device->asset->nomenclatura }}</a>@endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('network.edit', $device->id) }}" class="border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded text-sm">Editar</a>
            @can('delete', $device)
                <form action="{{ route('network.destroy', $device->id) }}" method="POST" onsubmit="return confirm('¿Borrar este dispositivo y todos sus puertos?');">
                    @csrf @method('DELETE')
                    <button class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 px-4 rounded text-sm">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-[#111827] rounded-xl p-6 md:p-8 shadow-2xl mb-8 border border-gray-800">
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
            <span class="text-gray-400 font-mono text-sm tracking-[0.2em] uppercase">{{ $device->tipo }} • {{ $device->total_ports }} PORTS</span>
            <div class="flex gap-4 text-xs font-mono tracking-wider text-gray-400">
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500"></span> OCUPADO</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-500"></span> EQUIPO CAÍDO</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-gray-600"></span> LIBRE</span>
            </div>
        </div>
        <div class="w-full overflow-x-auto pb-4 hardware-scroll">
            <div class="bg-[#0f172a] p-4 rounded-lg border border-[#1e293b] min-w-max mx-auto">
                <div class="grid grid-rows-2 grid-flow-col gap-x-2 gap-y-4">
                    @foreach($device->ports as $port)
                        @php
                            $caido = $port->asset?->estado_monitoreo === 'caido';
                            $clase = $caido ? 'bg-red-700 border-red-400' : ($port->status == 'Ocupado' ? 'bg-green-700 border-green-400' : 'bg-black border-gray-800 hover:border-gray-600');
                        @endphp
                        <button type="button" @click="abrir({{ $port->id }})" title="{{ $port->asset?->nomenclatura ?? $port->connected_to }}{{ $port->vlan ? ' · VLAN '.$port->vlan : '' }}"
                            class="relative w-12 h-12 border-2 rounded-md flex flex-col items-center justify-center transition {{ $clase }}">
                            <div class="w-7 h-5 bg-[#0a0f18] rounded-sm mb-1"></div>
                            <span class="text-[10px] font-mono font-bold {{ $port->status == 'Ocupado' ? 'text-white' : 'text-gray-500' }}">{{ str_pad($port->port_number, 2, '0', STR_PAD_LEFT) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-10">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700">Puertos ocupados</h3>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">{{ $device->ports->where('status', 'Ocupado')->count() }} / {{ $device->total_ports }}</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-gray-400">
                <tr><th class="py-3 px-6">Puerto</th><th class="py-3 px-6">Conectado a</th><th class="py-3 px-6">Ubicación</th><th class="py-3 px-6">VLAN</th><th class="py-3 px-6">Velocidad</th><th class="py-3 px-6">Notas</th></tr>
            </thead>
            <tbody>
                @forelse($device->ports->where('status', 'Ocupado') as $port)
                    <tr class="border-t border-gray-50 hover:bg-gray-50 cursor-pointer" @click="abrir({{ $port->id }})">
                        <td class="py-3 px-6 font-mono font-bold text-[#041b32]">{{ $port->port_number }}</td>
                        <td class="py-3 px-6">
                            @if($port->asset)
                                <a href="{{ route('assets.show', $port->asset->id) }}" @click.stop class="font-bold text-blue-800 hover:underline">{{ $port->asset->nomenclatura }}</a>
                                <span class="text-xs text-slate-500">{{ $port->asset->tipo_equipo }} {{ $port->asset->ip_address }}</span>
                                @if($port->asset->estado_monitoreo === 'caido')<span class="text-[10px] font-bold text-white bg-red-600 rounded px-1.5">CAÍDO</span>@endif
                            @else
                                <span class="font-bold text-slate-700">{{ $port->connected_to }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-xs">{{ $port->asset?->ubicacion?->etiqueta }}</td>
                        <td class="py-3 px-6 text-xs font-mono">{{ $port->vlan }}</td>
                        <td class="py-3 px-6 text-xs">{{ $velocidades[$port->speed] ?? $port->speed }}</td>
                        <td class="py-3 px-6 text-xs text-slate-500">{{ $port->notas }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 px-6 text-slate-400">Ningún puerto ocupado. Da clic en un puerto para documentarlo.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div x-show="panelOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/50" @click="panelOpen = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col">
            <div class="px-6 py-5 bg-[#041b32] text-white flex justify-between items-center">
                <div><h2 class="text-xl font-bold">Puerto <span x-text="p.num"></span></h2><p class="text-blue-200 text-sm">{{ $device->nombre }}</p></div>
                <button type="button" @click="panelOpen = false" class="text-blue-200 hover:text-white text-2xl">×</button>
            </div>
            <form :action="'{{ url('/network/port') }}/' + p.id" method="POST" class="flex-1 flex flex-col justify-between overflow-y-auto p-6">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Equipo del catálogo</label>
                        <select name="asset_id" x-model="p.asset_id" class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 text-sm">
                            <option value="">— Ninguno / no está en el catálogo —</option>
                            @foreach($activos as $a)
                                <option value="{{ $a->id }}">{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }}{{ $a->ubicacion ? ' · '.$a->ubicacion->etiqueta : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">O descripción libre</label>
                        <input type="text" name="connected_to" x-model="p.connected_to" placeholder="Ej: Uplink a SW-PISO3, cámara pasillo…" class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">VLAN</label>
                            <input type="text" name="vlan" x-model="p.vlan" placeholder="Ej: 10 o trunk" class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Velocidad</label>
                            <select name="speed" x-model="p.speed" class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 text-sm">
                                @foreach($velocidades as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Notas</label>
                        <input type="text" name="notas" x-model="p.notas" maxlength="255" placeholder="Ej: nodo 3B del patch panel" class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 text-sm">
                    </div>
                    <p class="text-xs text-slate-500">Para liberar el puerto deja vacíos el equipo y la descripción.</p>
                </div>
                <div class="pt-6 border-t border-gray-100 flex gap-3">
                    <button type="button" @click="panelOpen = false" class="flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 rounded-lg">Cancelar</button>
                    <button type="submit" class="flex-1 bg-[#041b32] text-white font-bold py-3 rounded-lg">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
