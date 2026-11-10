@extends('layouts.app')

@section('content')
@php $in = 'w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:border-[#041b32]'; $editor = auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos(); @endphp
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Proveedores</h2>
            <p class="text-gray-500 text-sm mt-1">Contactos de venta y soporte de cada proveedor de TI</p>
        </div>
        <form method="GET" class="flex gap-2"><input name="q" value="{{ $q }}" placeholder="Buscar proveedor" class="{{ $in }}"><button class="bg-[#041b32] text-white font-bold px-4 rounded-md text-sm">Buscar</button></form>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm mb-4">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    @if($editor)
        <details class="bg-white rounded-lg shadow-sm border border-slate-200 mb-6" @if($errors->any()) open @endif>
            <summary class="p-4 cursor-pointer font-bold text-[#041b32]">+ Agregar proveedor</summary>
            <form method="POST" action="{{ route('proveedores.store') }}" class="px-4 pb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <input name="nombre" required maxlength="150" placeholder="Nombre / razón social *" class="{{ $in }}">
                <input name="rfc" maxlength="20" placeholder="RFC" class="{{ $in }}">
                <input name="contacto" maxlength="150" placeholder="Contacto (ejecutivo)" class="{{ $in }}">
                <input name="telefono" maxlength="50" placeholder="Teléfono" class="{{ $in }}">
                <input name="email" type="email" maxlength="150" placeholder="Correo" class="{{ $in }}">
                <input name="soporte" maxlength="255" placeholder="Soporte técnico (teléfono, portal, correo)" class="{{ $in }}">
                <textarea name="notas" rows="2" placeholder="Notas" class="{{ $in }} md:col-span-2"></textarea>
                <button class="bg-[#041b32] text-white font-bold py-2 rounded-md text-sm">Guardar</button>
            </form>
        </details>
    @endif

    <div class="space-y-3">
        @forelse($proveedores as $p)
            <details class="bg-white rounded-lg shadow-sm border border-slate-200 {{ $p->activo ? '' : 'opacity-60' }}">
                <summary class="p-4 cursor-pointer flex flex-wrap justify-between gap-3">
                    <span><span class="font-bold text-slate-800">{{ $p->nombre }}</span> <span class="text-xs text-slate-500">{{ $p->contacto }} {{ $p->telefono }}</span></span>
                    <span class="text-xs text-slate-500">{{ $p->contratos_count }} contrato(s) · {{ $p->licencias_count }} licencia(s) · {{ $p->activos_count }} activo(s){{ $p->activo ? '' : ' · inactivo' }}</span>
                </summary>
                <div class="px-4 pb-4">
                    @if($p->soporte)<p class="text-sm mb-3"><span class="font-bold text-slate-600">Soporte:</span> {{ $p->soporte }}</p>@endif
                    @if($editor)
                        <form method="POST" action="{{ route('proveedores.update', $p) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @csrf @method('PUT')
                            <input name="nombre" value="{{ $p->nombre }}" required class="{{ $in }}">
                            <input name="rfc" value="{{ $p->rfc }}" placeholder="RFC" class="{{ $in }}">
                            <input name="contacto" value="{{ $p->contacto }}" placeholder="Contacto" class="{{ $in }}">
                            <input name="telefono" value="{{ $p->telefono }}" placeholder="Teléfono" class="{{ $in }}">
                            <input name="email" type="email" value="{{ $p->email }}" placeholder="Correo" class="{{ $in }}">
                            <input name="soporte" value="{{ $p->soporte }}" placeholder="Soporte técnico" class="{{ $in }}">
                            <textarea name="notas" rows="2" class="{{ $in }} md:col-span-2">{{ $p->notas }}</textarea>
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked($p->activo) class="rounded"> Activo</label>
                                <div class="flex gap-2">
                                    <button class="bg-[#041b32] text-white font-bold py-1.5 px-4 rounded text-sm">Guardar</button>
                                    @if(auth()->user()->puedeVerTodos())<button form="borrar-prov-{{ $p->id }}" class="border border-red-300 text-red-700 font-bold py-1.5 px-3 rounded text-sm" onclick="return confirm('¿Eliminar proveedor?')">Eliminar</button>@endif
                                </div>
                            </div>
                        </form>
                        <form id="borrar-prov-{{ $p->id }}" method="POST" action="{{ route('proveedores.destroy', $p) }}">@csrf @method('DELETE')</form>
                    @else
                        <p class="text-sm text-slate-600">{{ $p->email }} {{ $p->rfc ? '· RFC '.$p->rfc : '' }}</p>
                    @endif
                </div>
            </details>
        @empty
            <p class="text-center text-slate-400 py-8">Sin proveedores. Ejemplos: Single Digits, Gignet, Rowan, dormakaba, ENSEO.</p>
        @endforelse
    </div>
</div>
@endsection
