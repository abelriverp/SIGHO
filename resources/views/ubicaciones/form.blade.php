@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $ubicacion->exists;
@endphp
<div class="max-w-3xl mx-auto mt-6 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar ubicación' : 'Nueva ubicación' }}</h2>
        <a href="{{ $editar ? route('ubicaciones.show', $ubicacion) : route('ubicaciones.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium">Volver</a>
    </div>

    <form method="POST" action="{{ $editar ? route('ubicaciones.update', $ubicacion) : route('ubicaciones.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-6">
        @csrf
        @if ($editar) @method('PUT') @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="{{ $lbl }}">Tipo *</label>
                <select name="tipo" class="{{ $in }}">
                    @foreach ($tipos as $clave => $nombre)
                        <option value="{{ $clave }}" @selected(old('tipo', $ubicacion->tipo) === $clave)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Código *</label>
                <input type="text" name="codigo" value="{{ old('codigo', $ubicacion->codigo) }}" required class="{{ $in }} font-mono uppercase" placeholder="1205, LOBBY, SITE-PB">
            </div>
        </div>
        <div>
            <label class="{{ $lbl }}">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $ubicacion->nombre) }}" class="{{ $in }}" placeholder="Ej: Lobby principal">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="{{ $lbl }}">Piso</label>
                <input type="number" name="piso" value="{{ old('piso', $ubicacion->piso) }}" class="{{ $in }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Torre / zona</label>
                <input type="text" name="zona" value="{{ old('zona', $ubicacion->zona) }}" class="{{ $in }}">
            </div>
        </div>
        <div>
            <label class="{{ $lbl }}">Notas</label>
            <textarea name="notas" rows="3" class="{{ $in }}">{{ old('notas', $ubicacion->notas) }}</textarea>
        </div>
        <label class="flex items-center gap-2">
            <input type="hidden" name="activa" value="0">
            <input type="checkbox" name="activa" value="1" @checked(old('activa', $ubicacion->activa ?? true)) class="rounded">
            <span class="text-sm font-bold text-slate-700">Activa (aparece al registrar activos)</span>
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
            <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button>
        </div>
    </form>
</div>
@endsection
