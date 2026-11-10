@extends('layouts.app')

@section('content')
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32]';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $editar = $contrato->exists;
    $seleccionados = collect(old('activos', $editar ? $contrato->activos->pluck('id')->all() : []))->map(fn ($v) => (int) $v)->all();
@endphp
<div class="max-w-4xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">{{ $editar ? 'Editar contrato' : 'Nuevo contrato' }}</h2>
        <a href="{{ $editar ? route('contratos.show', $contrato) : route('contratos.index') }}" class="text-gray-500 hover:text-[#041b32]">Volver</a>
    </div>
    <form method="POST" enctype="multipart/form-data" action="{{ $editar ? route('contratos.update', $contrato) : route('contratos.store') }}" class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 space-y-5">
        @csrf
        @if($editar) @method('PUT') @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="md:col-span-2"><label class="{{ $lbl }}">Nombre *</label><input name="nombre" value="{{ old('nombre', $contrato->nombre) }}" required maxlength="200" class="{{ $in }}" placeholder="Ej: Enlace dedicado 200 Mbps"></div>
            <div><label class="{{ $lbl }}">Número / folio</label><input name="numero" value="{{ old('numero', $contrato->numero) }}" class="{{ $in }}"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div><label class="{{ $lbl }}">Tipo *</label><select name="tipo" class="{{ $in }}">@foreach($tipos as $k => $v)<option value="{{ $k }}" @selected(old('tipo', $contrato->tipo) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div>
                <label class="{{ $lbl }}">Proveedor</label>
                <select name="proveedor_id" class="{{ $in }}"><option value="">—</option>@foreach($proveedores as $p)<option value="{{ $p->id }}" @selected((string) old('proveedor_id', $contrato->proveedor_id) === (string) $p->id)>{{ $p->nombre }}</option>@endforeach</select>
                <p class="text-xs text-slate-500 mt-1">¿No aparece? <a href="{{ route('proveedores.index') }}" target="_blank" class="underline">Agregar proveedor</a></p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div><label class="{{ $lbl }}">Inicio</label><input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $contrato->fecha_inicio?->format('Y-m-d')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Fin</label><input type="date" name="fecha_fin" value="{{ old('fecha_fin', $contrato->fecha_fin?->format('Y-m-d')) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Avisar con (días)</label><input type="number" name="dias_aviso" min="1" max="365" value="{{ old('dias_aviso', $contrato->dias_aviso) }}" class="{{ $in }}"></div>
            <label class="flex items-center gap-2 pt-7"><input type="hidden" name="renovacion_automatica" value="0"><input type="checkbox" name="renovacion_automatica" value="1" @checked(old('renovacion_automatica', $contrato->renovacion_automatica)) class="rounded"><span class="text-sm font-bold text-slate-700">Renovación automática</span></label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div><label class="{{ $lbl }}">Costo</label><input type="number" step="0.01" min="0" name="costo" value="{{ old('costo', $contrato->costo) }}" class="{{ $in }}"></div>
            <div><label class="{{ $lbl }}">Moneda</label><select name="moneda" class="{{ $in }}">@foreach(['MXN','USD'] as $m)<option @selected(old('moneda', $contrato->moneda) === $m)>{{ $m }}</option>@endforeach</select></div>
            <div><label class="{{ $lbl }}">Pago</label><select name="periodicidad_pago" class="{{ $in }}"><option value="">—</option>@foreach($periodos as $k => $v)<option value="{{ $k }}" @selected(old('periodicidad_pago', $contrato->periodicidad_pago) === $k)>{{ $v }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div><label class="{{ $lbl }}">Ancho de banda (enlaces)</label><input name="ancho_banda" value="{{ old('ancho_banda', $contrato->ancho_banda) }}" class="{{ $in }}" placeholder="200 Mbps simétrico"></div>
            <div><label class="{{ $lbl }}">IP pública / bloque (enlaces)</label><input name="ip_publica" value="{{ old('ip_publica', $contrato->ip_publica) }}" class="{{ $in }} font-mono" placeholder="201.x.x.x/29"></div>
        </div>
        <div><label class="{{ $lbl }}">Contacto de soporte / cómo levantar reporte</label><input name="contacto_soporte" value="{{ old('contacto_soporte', $contrato->contacto_soporte) }}" class="{{ $in }}" placeholder="Ej: 800 123 4567 opción 2, cuenta 98765"></div>
        <div><label class="{{ $lbl }}">SLA contratado</label><textarea name="sla" rows="2" class="{{ $in }}" placeholder="Ej: respuesta 4 h, solución 24 h, disponibilidad 99.5%">{{ old('sla', $contrato->sla) }}</textarea></div>
        <div>
            <label class="{{ $lbl }}">Equipos cubiertos</label>
            <select name="activos[]" multiple size="8" class="{{ $in }}">
                @foreach($activos as $a)<option value="{{ $a->id }}" @selected(in_array($a->id, $seleccionados, true))>{{ $a->nomenclatura }} · {{ $a->tipo_equipo ?: $a->nombre }}</option>@endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Ctrl + clic para elegir varios.</p>
        </div>
        <div><label class="{{ $lbl }}">Notas</label><textarea name="notas" rows="3" class="{{ $in }}">{{ old('notas', $contrato->notas) }}</textarea></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-end">
            <div>
                <label class="{{ $lbl }}">Contrato firmado (PDF)</label>
                @if($contrato->documento_path)<a href="{{ Storage::url($contrato->documento_path) }}" target="_blank" class="text-sm text-blue-700 underline block mb-2">Ver documento actual</a>@endif
                <input type="file" name="documento" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2">
            </div>
            <label class="flex items-center gap-2 pb-2"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked(old('activo', $contrato->activo ?? true)) class="rounded"><span class="text-sm font-bold text-slate-700">Vigente (incluir en alertas y gasto)</span></label>
        </div>
        <div class="flex justify-end border-t border-slate-200 pt-5"><button class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md">Guardar</button></div>
    </form>
</div>
@endsection
