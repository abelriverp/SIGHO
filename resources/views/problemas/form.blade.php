@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $problema->exists;
    $ligados = collect(old('tickets', $ticketsSugeridos->pluck('id')->all()))->map(fn ($v) => (int) $v)->all();
@endphp
<div class="max-w-4xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar problema P-'.$problema->id : 'Nuevo problema' }}</h2>
        <a href="{{ $editar ? route('problemas.show', $problema) : route('problemas.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('problemas.update', $problema) : route('problemas.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div><label class="{{ $lbl }}">Título *</label><input name="titulo" value="{{ old('titulo', $problema->titulo) }}" required maxlength="200" class="{{ $in }}"></div>
        <div><label class="{{ $lbl }}">Descripción / síntomas *</label><textarea name="descripcion" rows="3" required class="{{ $in }}">{{ old('descripcion', $problema->descripcion) }}</textarea></div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div><label class="{{ $lbl }}">Prioridad</label><select name="prioridad" class="{{ $in }}">@foreach(['Baja','Media','Alta'] as $p)<option @selected(old('prioridad', $problema->prioridad) === $p)>{{ $p }}</option>@endforeach</select></div>
            <div><label class="{{ $lbl }}">Estado</label><select name="estado" class="{{ $in }}">@foreach($estados as $k => $v)<option value="{{ $k }}" @selected(old('estado', $problema->estado) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="md:col-span-2"><label class="{{ $lbl }}">Responsable</label><select name="responsable_id" class="{{ $in }}"><option value="">—</option>@foreach($tecnicos as $t)<option value="{{ $t->id }}" @selected((string) old('responsable_id', $problema->responsable_id) === (string) $t->id)>{{ $t->name }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div><label class="{{ $lbl }}">Equipo</label><select name="asset_id" class="{{ $in }}"><option value="">—</option>@foreach($activos as $a)<option value="{{ $a->id }}" @selected((string) old('asset_id', $problema->asset_id) === (string) $a->id)>{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }}</option>@endforeach</select></div>
            <div><label class="{{ $lbl }}">Ubicación</label><select name="ubicacion_id" class="{{ $in }}"><option value="">—</option>@foreach($ubicaciones as $u)<option value="{{ $u->id }}" @selected((string) old('ubicacion_id', $problema->ubicacion_id) === (string) $u->id)>{{ $u->etiqueta }}</option>@endforeach</select></div>
        </div>
        <div><label class="{{ $lbl }}">Solución temporal (workaround)</label><textarea name="workaround" rows="2" class="{{ $in }}" placeholder="Lo que se hace mientras tanto para que el usuario siga trabajando">{{ old('workaround', $problema->workaround) }}</textarea></div>
        <div><label class="{{ $lbl }}">Causa raíz</label><textarea name="causa_raiz" rows="2" class="{{ $in }}" placeholder="Obligatoria para resolver o cerrar">{{ old('causa_raiz', $problema->causa_raiz) }}</textarea></div>
        <div><label class="{{ $lbl }}">Solución definitiva</label><textarea name="solucion_definitiva" rows="2" class="{{ $in }}" placeholder="Si requiere intervenir equipos, registra un Cambio">{{ old('solucion_definitiva', $problema->solucion_definitiva) }}</textarea></div>
        @if($ticketsSugeridos->isNotEmpty())
            <div>
                <label class="{{ $lbl }}">Tickets ligados</label>
                <div class="border border-slate-200 rounded-md max-h-56 overflow-y-auto">
                    @foreach($ticketsSugeridos as $t)
                        <label class="flex gap-2 items-start px-3 py-2 border-b border-slate-50 text-sm">
                            <input type="checkbox" name="tickets[]" value="{{ $t->id }}" @checked(in_array($t->id, $ligados, true)) class="rounded mt-0.5">
                            <span><strong>#{{ $t->id }}</strong> <span class="text-xs text-slate-400">{{ $t->created_at?->format('d/m/Y') }} · {{ $t->status }}</span><span class="block text-slate-600">{{ \Illuminate\Support\Str::limit($t->descripcion, 110) }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="flex justify-end border-t border-slate-200 pt-5"><button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button></div>
    </form>
</div>
@endsection
