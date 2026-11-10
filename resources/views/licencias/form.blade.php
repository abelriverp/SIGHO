@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $licencia->exists;
    $seleccionados = collect(old('activos', $editar ? $licencia->activos->pluck('id')->all() : []))->map(fn ($v) => (int) $v)->all();
@endphp
<div class="max-w-4xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar licencia' : 'Nueva licencia' }}</h2>
        <a href="{{ route('licencias.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" action="{{ $editar ? route('licencias.update', $licencia) : route('licencias.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Software *</label><input name="software" value="{{ old('software', $licencia->software) }}" required class="{{ $in }}" placeholder="Microsoft 365"></div>
            <div><label class="{{ $lbl }}">Edición</label><input name="edicion" value="{{ old('edicion', $licencia->edicion) }}" class="{{ $in }}" placeholder="Business Standard"></div>
            <div><label class="{{ $lbl }}">Tipo *</label><select name="tipo" class="{{ $in }}">@foreach($tipos as $k => $v)<option value="{{ $k }}" @selected(old('tipo', $licencia->tipo) === $k)>{{ $v }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Cantidad comprada *</label><input type="number" min="1" name="cantidad" value="{{ old('cantidad', $licencia->cantidad) }}" required class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Proveedor</label><select name="proveedor_id" class="{{ $in }}"><option value="">—</option>@foreach($proveedores as $p)<option value="{{ $p->id }}" @selected((string) old('proveedor_id', $licencia->proveedor_id) === (string) $p->id)>{{ $p->nombre }}</option>@endforeach</select></div>
            <div><label class="{{ $lbl }}">Referencia (folio, cuenta)</label><input name="referencia" value="{{ old('referencia', $licencia->referencia) }}" class="{{ $in }}" placeholder="No pongas la clave del producto"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Compra</label><input type="date" name="fecha_compra" value="{{ old('fecha_compra', $licencia->fecha_compra?->format('Y-m-d')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Vencimiento</label><input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $licencia->fecha_vencimiento?->format('Y-m-d')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Costo (MXN)</label><input type="number" step="0.01" min="0" name="costo" value="{{ old('costo', $licencia->costo) }}" class="{{ $in }}"></div>
        </div>
        <div>
            <label class="{{ $lbl }}">Instalada en</label>
            <select name="activos[]" multiple size="10" class="{{ $in }}">
                @foreach($activos as $a)<option value="{{ $a->id }}" @selected(in_array($a->id, $seleccionados, true))>{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }}</option>@endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Ctrl + clic para elegir varios. Si eliges más equipos que licencias compradas, la lista lo marca en rojo.</p>
        </div>
        <div><label class="{{ $lbl }}">Notas</label><textarea name="notas" rows="3" class="{{ $in }}">{{ old('notas', $licencia->notas) }}</textarea></div>
        <div class="flex justify-between border-t border-slate-200 pt-5">
            @if($editar && auth()->user()->puedeVerTodos())
                <button form="borrar-lic" class="border border-red-300 text-red-700 font-bold py-2 px-4 rounded text-sm" onclick="return confirm('¿Eliminar esta licencia?')">Eliminar</button>
            @else <span></span> @endif
            <button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button>
        </div>
    </form>
    @if($editar)<form id="borrar-lic" method="POST" action="{{ route('licencias.destroy', $licencia) }}">@csrf @method('DELETE')</form>@endif
</div>
@endsection
