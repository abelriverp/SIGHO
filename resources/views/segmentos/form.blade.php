@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $segmento->exists;
@endphp
<div class="max-w-3xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar segmento' : 'Nuevo segmento' }}</h2>
        <a href="{{ $editar ? route('segmentos.show', $segmento) : route('segmentos.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('segmentos.update', $segmento) : route('segmentos.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="md:col-span-2"><label class="{{ $lbl }}">Nombre *</label><input name="nombre" value="{{ old('nombre', $segmento->nombre) }}" required class="{{ $in }}" placeholder="Administrativa"></div>
            <div><label class="{{ $lbl }}">VLAN</label><input type="number" min="1" max="4094" name="vlan" value="{{ old('vlan', $segmento->vlan) }}" class="{{ $in }}"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div><label class="{{ $lbl }}">Red (CIDR) *</label><input name="red" value="{{ old('red', $segmento->red) }}" required class="{{ $in }} font-mono" placeholder="10.7.64.0/24"></div>
            <div><label class="{{ $lbl }}">Gateway</label><input name="gateway" value="{{ old('gateway', $segmento->gateway) }}" class="{{ $in }} font-mono" placeholder="10.7.64.1"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">DHCP desde</label><input name="dhcp_inicio" value="{{ old('dhcp_inicio', $segmento->dhcp_inicio) }}" class="{{ $in }} font-mono"></div>
            <div><label class="{{ $lbl }}">DHCP hasta</label><input name="dhcp_fin" value="{{ old('dhcp_fin', $segmento->dhcp_fin) }}" class="{{ $in }} font-mono"></div>
            <div><label class="{{ $lbl }}">DNS</label><input name="dns" value="{{ old('dns', $segmento->dns) }}" class="{{ $in }} font-mono" placeholder="8.8.8.8, 1.1.1.1"></div>
        </div>
        <div><label class="{{ $lbl }}">Propósito</label><input name="proposito" value="{{ old('proposito', $segmento->proposito) }}" class="{{ $in }}" placeholder="PCs de oficinas y Opera"></div>
        <div><label class="{{ $lbl }}">Notas</label><textarea name="notas" rows="3" class="{{ $in }}">{{ old('notas', $segmento->notas) }}</textarea></div>
        <div class="flex justify-between border-t border-slate-200 pt-5">
            @if($editar && auth()->user()->puedeVerTodos())
                <button form="borrar-seg" class="border border-red-300 text-red-700 font-bold py-2 px-4 rounded text-sm" onclick="return confirm('¿Eliminar este segmento?')">Eliminar</button>
            @else <span></span> @endif
            <button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button>
        </div>
    </form>
    @if($editar)<form id="borrar-seg" method="POST" action="{{ route('segmentos.destroy', $segmento) }}">@csrf @method('DELETE')</form>@endif
</div>
@endsection
