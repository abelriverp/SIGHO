@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $articulo->exists;
@endphp
<div class="max-w-4xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar artículo' : 'Nuevo artículo' }}</h2>
        <a href="{{ $editar ? route('conocimiento.show', $articulo) : route('conocimiento.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('conocimiento.update', $articulo) : route('conocimiento.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if(!$editar && $articulo->ticket_origen_id)
            <input type="hidden" name="ticket_origen_id" value="{{ $articulo->ticket_origen_id }}">
            <p class="text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded p-2">Precargado desde el ticket #{{ $articulo->ticket_origen_id }}. Quita datos personales o específicos antes de publicar.</p>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div>
            <label class="{{ $lbl }}">Título *</label>
            <input type="text" name="titulo" value="{{ old('titulo', $articulo->titulo) }}" required maxlength="200" class="{{ $in }}" placeholder="Ej: Impresora de Recepción no imprime después de cambio de tóner">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">Departamento</label>
                <select name="departamento" class="{{ $in }}">
                    <option value="">General (todos)</option>
                    @foreach($departamentos as $d)<option value="{{ $d }}" @selected(old('departamento', $articulo->departamento) === $d)>{{ $d }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Categoría (tipo de solicitud)</label>
                <input type="text" name="categoria" value="{{ old('categoria', $articulo->categoria) }}" class="{{ $in }}" placeholder="Ej: Impresoras">
            </div>
        </div>
        <div>
            <label class="{{ $lbl }}">Síntoma / cómo lo reporta el usuario</label>
            <textarea name="sintoma" rows="3" class="{{ $in }}">{{ old('sintoma', $articulo->sintoma) }}</textarea>
        </div>
        <div>
            <label class="{{ $lbl }}">Solución paso a paso *</label>
            <textarea name="solucion" rows="10" required class="{{ $in }}" placeholder="1. ...&#10;2. ...">{{ old('solucion', $articulo->solucion) }}</textarea>
        </div>
        <div>
            <label class="{{ $lbl }}">Etiquetas</label>
            <input type="text" name="etiquetas" value="{{ old('etiquetas', $articulo->etiquetas) }}" class="{{ $in }}" placeholder="impresora, tóner, recepción">
        </div>
        <label class="flex items-center gap-2">
            <input type="hidden" name="publicado" value="0">
            <input type="checkbox" name="publicado" value="1" @checked(old('publicado', $articulo->publicado ?? true)) class="rounded">
            <span class="text-sm font-bold text-slate-700">Publicado (visible para todos los usuarios)</span>
        </label>
        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button>
        </div>
    </form>
</div>
@endsection
