@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10 mb-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Nueva Estancia</h3>
        </div>

        <form action="{{ route('condominios.estancias.store') }}" method="POST" class="p-6 space-y-6" x-data="{ tipoVisitante: '{{ old('tipo_visitante') }}' }">
            @csrf

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Unidad <span class="text-red-500">*</span></label>
                    <select name="unidad_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="">-- Seleccionar --</option>
                        @foreach($unidades as $u)
                            <option value="{{ $u->id }}" {{ (old('unidad_id', $unidadPreseleccionada) == $u->id) ? 'selected' : '' }}>
                                {{ $u->nombreCompleto() }} ({{ $u->estatus }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de visitante <span class="text-red-500">*</span></label>
                    <select name="tipo_visitante" x-model="tipoVisitante" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="">-- Seleccionar --</option>
                        @foreach($tiposVisitante as $clave => $label)
                            <option value="{{ $clave }}" {{ old('tipo_visitante') == $clave ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div x-show="tipoVisitante === 'propietario' || tipoVisitante === 'familiar'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-1">Condómino relacionado</label>
                <select name="condomino_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                    <option value="">-- Sin especificar --</option>
                    @foreach($condominos as $c)
                        <option value="{{ $c->id }}" {{ old('condomino_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del visitante <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_visitante" value="{{ old('nombre_visitante') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Identificación / Placas</label>
                    <input type="text" name="identificacion_placas" value="{{ old('identificacion_placas') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Fecha de llegada <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_llegada_prevista" value="{{ old('fecha_llegada_prevista', now()->toDateString()) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Salida estimada</label>
                    <input type="date" name="fecha_salida_prevista" value="{{ old('fecha_salida_prevista') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Acompañantes</label>
                    <input type="number" name="num_acompanantes" value="{{ old('num_acompanantes', 0) }}" min="0"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            <div x-show="tipoVisitante === 'inquilino_renta'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tarifa por noche (opcional)</label>
                <input type="number" name="tarifa_noche" value="{{ old('tarifa_noche') }}" min="0" step="0.01"
                    class="w-full md:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Notas</label>
                <textarea name="notas" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">{{ old('notas') }}</textarea>
            </div>

            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg p-3">
                <input type="checkbox" name="hacer_checkin_ahora" value="1" id="checkin_ahora"
                    {{ old('hacer_checkin_ahora') ? 'checked' : '' }}
                    class="rounded border-gray-300">
                <label for="checkin_ahora" class="text-sm text-gray-700">El visitante ya está llegando — hacer check-in de inmediato</label>
            </div>

            <div class="flex justify-end pt-4 gap-3">
                <a href="{{ route('condominios.estancias.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
