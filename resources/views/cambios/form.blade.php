@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $cambio->exists;
    $sel = collect(old('activos', $editar ? $cambio->activos->pluck('id')->all() : []))->map(fn ($v) => (int) $v)->all();
@endphp
<div class="max-w-4xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar cambio C-'.$cambio->id : 'Nuevo cambio' }}</h2>
        <a href="{{ $editar ? route('cambios.show', $cambio) : route('cambios.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('cambios.update', $cambio) : route('cambios.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5" x-data="{ filtro: '' }">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div><label class="{{ $lbl }}">Título *</label><input name="titulo" value="{{ old('titulo', $cambio->titulo) }}" required maxlength="200" class="{{ $in }}" placeholder="Ej: Actualizar firmware del firewall"></div>
        <div><label class="{{ $lbl }}">Qué se va a hacer y por qué *</label><textarea name="descripcion" rows="3" required class="{{ $in }}">{{ old('descripcion', $cambio->descripcion) }}</textarea></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="md:col-span-2"><label class="{{ $lbl }}">Tipo</label><select name="tipo" class="{{ $in }}">@foreach($tipos as $k => $v)<option value="{{ $k }}" @selected(old('tipo', $cambio->tipo) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="{{ $lbl }}">Riesgo</label><select name="riesgo" class="{{ $in }}">@foreach(['Baja','Media','Alta'] as $r)<option @selected(old('riesgo', $cambio->riesgo) === $r)>{{ $r }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Inicio de ventana</label><input type="datetime-local" name="ventana_inicio" value="{{ old('ventana_inicio', $cambio->ventana_inicio?->format('Y-m-d\TH:i')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Fin de ventana</label><input type="datetime-local" name="ventana_fin" value="{{ old('ventana_fin', $cambio->ventana_fin?->format('Y-m-d\TH:i')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Problema relacionado</label><select name="problema_id" class="{{ $in }}"><option value="">—</option>@foreach($problemas as $p)<option value="{{ $p->id }}" @selected((string) old('problema_id', $cambio->problema_id) === (string) $p->id)>P-{{ $p->id }} · {{ \Illuminate\Support\Str::limit($p->titulo, 40) }}</option>@endforeach</select></div>
        </div>
        <div><label class="{{ $lbl }}">Impacto en la operación</label><textarea name="impacto" rows="2" class="{{ $in }}" placeholder="Qué áreas o servicios se afectan y por cuánto tiempo (ej: Recepción sin PMS 20 min)">{{ old('impacto', $cambio->impacto) }}</textarea></div>
        <div><label class="{{ $lbl }}">Plan de implementación *</label><textarea name="plan_implementacion" rows="4" required class="{{ $in }}" placeholder="Pasos en orden">{{ old('plan_implementacion', $cambio->plan_implementacion) }}</textarea></div>
        <div><label class="{{ $lbl }}">Plan de regreso (rollback) *</label><textarea name="plan_rollback" rows="3" required class="{{ $in }}" placeholder="Cómo volver al estado anterior si algo falla">{{ old('plan_rollback', $cambio->plan_rollback) }}</textarea></div>
        <div>
            <label class="{{ $lbl }}">Equipos afectados</label>
            <input x-model="filtro" placeholder="Filtrar equipos…" class="{{ $in }} mb-2 text-sm">
            <div class="border border-slate-200 rounded-md max-h-56 overflow-y-auto">
                @foreach($activos as $a)
                    @php $txt = $a->nomenclatura.' '.($a->tipo_equipo ?: $a->nombre); @endphp
                    <label class="flex gap-2 items-center px-3 py-1.5 border-b border-slate-50 text-sm" x-show="!filtro || @js(mb_strtolower($txt)).includes(filtro.toLowerCase())">
                        <input type="checkbox" name="activos[]" value="{{ $a->id }}" @checked(in_array($a->id, $sel, true)) class="rounded">
                        <span class="font-mono text-xs">{{ $a->nomenclatura }}</span> <span class="text-slate-500">{{ $a->tipo_equipo ?: $a->nombre }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex justify-end border-t border-slate-200 pt-5"><button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button></div>
    </form>
</div>
@endsection
