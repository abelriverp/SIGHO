@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 mt-6">
    
    <div class="mb-6">
        <a href="{{ route('assets.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium flex items-center gap-1 transition">
          
        </a>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-6">
        
        <div class="flex-1 w-full">
            <h2 class="text-3xl font-bold text-[#041b32] mb-1">Expediente del Activo</h2>
            <p class="text-gray-500 text-sm mb-6">Detalles técnicos e historial.</p>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                <div class="col-span-1 md:col-span-2">
                    <span class="block text-xs text-gray-400 uppercase font-bold">Nombre del Equipo</span>
                    <span class="text-xl font-bold text-slate-800">{{ $asset->modelo }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Nomenclatura</span>
                    <span class="text-lg font-mono text-[#041b32]">{{ $asset->nomenclatura }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Área</span>
                    <span class="bg-blue-50 text-blue-800 py-1 px-3 rounded-full text-xs font-bold inline-block mt-1">{{ $asset->area }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Encargado</span>
                    <span class="text-base text-slate-700">{{ $asset->encargado }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Dirección IP</span>
                    <span class="text-base font-mono text-slate-600">{{ $asset->ip_address }}</span>
                </div>
                
                <div class="col-span-1 md:col-span-2 border-t border-gray-50 pt-4 mt-2">
                    <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Notas / Observaciones</span>
                    <p class="text-gray-600 text-sm">{{ $asset->notas ?? 'Sin observaciones registradas.' }}</p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/3 space-y-4">
            
            <div class="bg-white p-2 rounded-lg shadow-sm border border-gray-100">
                <div class="aspect-video bg-gray-50 rounded overflow-hidden flex items-center justify-center">
                    @if($asset->imagen_path)
                        <img src="{{ Storage::url($asset->imagen_path) }}" class="w-full h-full object-cover">
                    @else
                        <div class="flex flex-col items-center text-gray-300">
                            <svg class="w-10 h-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span class="text-xs font-bold uppercase">Sin Foto</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($asset->pdf_path)
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-800 uppercase">Documentación</span>
                    <a href="{{ Storage::url($asset->pdf_path) }}" target="_blank" class="bg-white hover:bg-gray-50 text-blue-700 border border-blue-200 text-xs font-bold py-2 px-3 rounded shadow-sm inline-flex items-center gap-2">
                        Ver PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow sm:rounded-lg mb-10">
        
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-slate-700 text-lg">Historial de Mantenimiento</h3>
            
            <a href="{{ route('revisions.create', $asset->id) }}" class="bg-[#041b32] hover:opacity-90 text-white text-sm font-bold py-2 px-4 rounded shadow transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Revisión
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#041b32] text-white uppercase font-bold text-sm">
                        <th class="py-3 px-4 whitespace-nowrap">PROBLEMA</th>
                        <th class="py-3 px-4 whitespace-nowrap">DESCRIPCION</th>
                        <th class="py-3 px-4 whitespace-nowrap">REVISION</th>
                        <th class="py-3 px-4 whitespace-nowrap">DIAGNOSTICO</th>
                        <th class="py-3 px-4 whitespace-nowrap">SOLUCION</th>
                        <th class="py-3 px-4 whitespace-nowrap">RESULTADO</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">FECHA</th>
                        {{-- Eliminada columna de Acciones --}}
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @foreach($asset->revisions as $revision)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-4 px-4 font-bold text-slate-800">{{ $revision->problema }}</td>
                        <td class="py-4 px-4">{{ Str::limit($revision->descripcion, 30) }}</td>
                        <td class="py-4 px-4">{{ Str::limit($revision->revision, 30) }}</td>
                        <td class="py-4 px-4">{{ Str::limit($revision->diagnostico, 30) }}</td>
                        <td class="py-4 px-4">{{ Str::limit($revision->solucion, 30) }}</td>
                        <td class="py-4 px-4">
                            <span class="px-2 py-1 rounded text-xs font-bold inline-block
                                {{ $revision->resultado == 'Exitoso' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $revision->resultado == 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $revision->resultado == 'Sin Solución' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $revision->resultado ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center text-xs whitespace-nowrap">
                            {{ $revision->created_at->format('d/m/Y H:i') }}
                        </td>
                        {{-- Eliminada celda de botones --}}
                    </tr>
                    @endforeach

                    @if($asset->revisions->isEmpty())
                    <tr>
                        <td colspan="7" class="py-10 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p>No hay revisiones registradas para este equipo.</p>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection