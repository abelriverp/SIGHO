@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Editar Revisión</h2>
            <p class="text-gray-500 text-sm">Equipo: <span class="font-bold">{{ $revision->asset->nombre }}</span></p>
        </div>
    
        <a href="{{ route('assets.show', $revision->asset_id) }}" class="text-gray-500 hover:text-slate-800 font-medium">
           
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="hotel-blue px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-semibold text-lg">Modificar Reporte</h3>
            <span class="bg-blue-800 text-blue-100 text-xs py-1 px-2 rounded">
                Fecha original: {{ $revision->->format('d/m/Y') }}
            </span>
        </div>

        <form action="{{ route('revisions.update', $revision->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Problema (Título corto)</label>
                    <input type="text" name="problema" value="{{ $revision->problema }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (Reporte del usuario)</label>
                    <textarea name="descripcion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">{{ $revision->descripcion }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Revisión (¿Qué revisaste?)</label>
                    <textarea name="revision" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">{{ $revision->revision }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico (Causa raíz)</label>
                    <textarea name="diagnostico" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">{{ $revision->diagnostico }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Solución Aplicada</label>
                    <textarea name="solucion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">{{ $revision->solucion }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado Final</label>
                    <select name="resultado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Exitoso" {{ $revision->resultado == 'Exitoso' ? 'selected' : '' }}>Exitoso / Reparado</option>
                        <option value="Pendiente" {{ $revision->resultado == 'Pendiente' ? 'selected' : '' }}>Pendiente de Pieza</option>
                        <option value="Sin Solución" {{ $revision->resultado == 'Sin Solución' ? 'selected' : '' }}>Sin Solución / Baja</option>
                        <option value="Observación" {{ $revision->resultado == 'Observación' ? 'selected' : '' }}>Solo Observación</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <a href="{{ route('assets.show', $revision->asset_id) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="hotel-blue text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90 transition">
                    Actualizar Revisión
                </button>
            </div>
        </form>
    </div>
</div>
@endsection