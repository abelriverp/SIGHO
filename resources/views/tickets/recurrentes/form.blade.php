@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $recurrente->exists;
@endphp
<div class="max-w-3xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar mantenimiento' : 'Programar mantenimiento' }}</h2>
        <a href="{{ route('tickets.recurrentes.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('tickets.recurrentes.update', $recurrente) : route('tickets.recurrentes.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div><label class="{{ $lbl }}">Título *</label><input name="titulo" value="{{ old('titulo', $recurrente->titulo) }}" required maxlength="200" class="{{ $in }}" placeholder="Ej: Limpieza y revisión de PCs de Recepción"></div>
        <div><label class="{{ $lbl }}">Qué hay que hacer *</label><textarea name="descripcion" rows="4" required class="{{ $in }}">{{ old('descripcion', $recurrente->descripcion) }}</textarea></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="{{ $lbl }}">Departamento *</label>
                <select name="departamento_destino" required class="{{ $in }}">@foreach($departamentos as $d)<option value="{{ $d }}" @selected(old('departamento_destino', $recurrente->departamento_destino ?? 'Sistemas') === $d)>{{ $d }}</option>@endforeach</select>
            </div>
            <div>
                <label class="{{ $lbl }}">Frecuencia *</label>
                <select name="frecuencia" required class="{{ $in }}">@foreach($frecuencias as $k => $v)<option value="{{ $k }}" @selected(old('frecuencia', $recurrente->frecuencia) === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div>
                <label class="{{ $lbl }}">Prioridad *</label>
                <select name="prioridad" class="{{ $in }}">@foreach(['Baja','Media','Alta'] as $p)<option value="{{ $p }}" @selected(old('prioridad', $recurrente->prioridad) === $p)>{{ $p }}</option>@endforeach</select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">Equipo (opcional)</label>
                <select name="asset_id" class="{{ $in }}"><option value="">—</option>@foreach($activos as $a)<option value="{{ $a->id }}" @selected((string) old('asset_id', $recurrente->asset_id) === (string) $a->id)>{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }}</option>@endforeach</select>
            </div>
            <div>
                <label class="{{ $lbl }}">Ubicación (opcional)</label>
                <select name="ubicacion_id" class="{{ $in }}"><option value="">—</option>@foreach($ubicaciones as $u)<option value="{{ $u->id }}" @selected((string) old('ubicacion_id', $recurrente->ubicacion_id) === (string) $u->id)>{{ $u->etiqueta }}</option>@endforeach</select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-end">
            <div><label class="{{ $lbl }}">Próxima fecha *</label><input type="date" name="proxima_fecha" value="{{ old('proxima_fecha', $recurrente->proxima_fecha?->format('Y-m-d')) }}" required class="{{ $in }}"></div>
            <label class="flex items-center gap-2 pb-2"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked(old('activo', $recurrente->activo ?? true)) class="rounded"><span class="text-sm font-bold text-slate-700">Activo</span></label>
        </div>
        <div class="flex justify-between border-t border-slate-200 pt-5">
            @if($editar)
                <button form="borrar-rec" class="border border-red-300 text-red-700 font-bold py-2 px-4 rounded text-sm" onclick="return confirm('¿Eliminar este mantenimiento programado?')">Eliminar</button>
            @else <span></span> @endif
            <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button>
        </div>
    </form>
    @if($editar)<form id="borrar-rec" method="POST" action="{{ route('tickets.recurrentes.destroy', $recurrente) }}">@csrf @method('DELETE')</form>@endif
</div>
@endsection
