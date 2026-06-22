@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Nueva Revisión</h2>
            <p class="text-gray-500 text-sm">Equipo: <span class="font-bold">{{ $asset->nombre }}</span> ({{ $asset->nomenclatura }})</p>
        </div>
        <a href="{{ route('assets.show', $asset->id) }}" class="text-gray-500 hover:text-slate-800 font-medium">
            
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="hotel-blue px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Detalles del Reporte</h3>
        </div>

        <form action="{{ route('revisions.store', $asset->id) }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Problema (Título corto)</label>
                    <input type="text" name="problema" placeholder="Ej: No enciende, Pantalla azul, etc." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (Reporte del usuario)</label>
                    <textarea name="descripcion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Revisión (¿Qué revisaste?)</label>
                    <textarea name="revision" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico (Causa raíz)</label>
                    <textarea name="diagnostico" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Solución Aplicada</label>
                    <textarea name="solucion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado Final</label>
                    <select name="resultado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Exitoso">Exitoso / Reparado</option>
                        <option value="Pendiente">Pendiente de Pieza</option>
                        <option value="Sin Solución">Sin Solución / Baja</option>
                        <option value="Observación">Solo Observación</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <a href="{{ route('assets.show', $asset->id) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="hotel-blue text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90 transition">
                    Guardar Revisión
                </button>
            </div>
        </form>
    </div>
</div>
@endsection