@extends('layouts.app')

@section('content')
@php
    $device = $device ?? null;
    $in = 'w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
@endphp
<div class="max-w-3xl mx-auto mt-6 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $device ? 'Editar equipo de red' : 'Nuevo equipo de red' }}</h2>
        <a href="{{ $device ? route('network.show', $device->id) : route('network.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $device ? route('network.update', $device->id) : route('network.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($device) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div><label class="{{ $lbl }}">Nombre *</label><input name="nombre" value="{{ old('nombre', $device?->nombre) }}" required class="{{ $in }}" placeholder="Ej: SW-CORE Site PB"></div>
            <div><label class="{{ $lbl }}">Tipo *</label><select name="tipo" class="{{ $in }}">@foreach($tipos as $t)<option @selected(old('tipo', $device?->tipo) === $t)>{{ $t }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @unless($device)
                <div>
                    <label class="{{ $lbl }}">Puertos *</label>
                    <select name="total_ports" class="{{ $in }}">@foreach([8, 10, 16, 24, 28, 48, 52] as $n)<option value="{{ $n }}" @selected((int) old('total_ports', 24) === $n)>{{ $n }} puertos</option>@endforeach</select>
                </div>
            @endunless
            <div><label class="{{ $lbl }}">IP de administración</label><input name="ip_gestion" value="{{ old('ip_gestion', $device?->ip_gestion) }}" class="{{ $in }} font-mono" placeholder="10.7.64.2"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">Ubicación</label>
                <select name="ubicacion_id" class="{{ $in }}"><option value="">—</option>@foreach($ubicaciones as $u)<option value="{{ $u->id }}" @selected((string) old('ubicacion_id', $device?->ubicacion_id) === (string) $u->id)>{{ $u->etiqueta }}</option>@endforeach</select>
            </div>
            <div><label class="{{ $lbl }}">Ubicación (texto libre)</label><input name="ubicacion" value="{{ old('ubicacion', $device?->ubicacion) }}" class="{{ $in }}" placeholder="Rack 1, U12"></div>
        </div>
        <div>
            <label class="{{ $lbl }}">Ficha en el catálogo de Activos</label>
            <select name="asset_id" class="{{ $in }}"><option value="">— Sin ligar —</option>@foreach($activosRed as $a)<option value="{{ $a->id }}" @selected((string) old('asset_id', $device?->asset_id) === (string) $a->id)>{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }} {{ $a->ip_address }}</option>@endforeach</select>
            <p class="text-xs text-slate-500 mt-1">Así el switch tiene garantía, contrato, historial de tickets y monitoreo. Solo aparecen activos de categoría Red.</p>
        </div>
        {{-- Fase 6: rack y topología --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Posición en rack</label><input name="posicion_rack" value="{{ old('posicion_rack', $device?->posicion_rack) }}" maxlength="20" class="{{ $in }}" placeholder="U12"></div>
            <div><label class="{{ $lbl }}">Cuelga de (uplink)</label>
                <select name="uplink_id" class="{{ $in }}"><option value="">— Es el equipo principal —</option>@foreach($equiposRed as $e)@continue($device && $e->id === $device->id)<option value="{{ $e->id }}" @selected((string) old('uplink_id', $device?->uplink_id) === (string) $e->id)>{{ $e->nombre }} ({{ $e->tipo }})</option>@endforeach</select>
            </div>
            <div><label class="{{ $lbl }}">Puerto del uplink</label><input name="uplink_puerto" value="{{ old('uplink_puerto', $device?->uplink_puerto) }}" maxlength="50" class="{{ $in }}" placeholder="Ej: puerto 24 / SFP 1"></div>
        </div>
        <div class="flex justify-end border-t border-slate-200 pt-5"><button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button></div>
    </form>
</div>
@endsection
