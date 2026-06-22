@extends('layouts.app')

@section('content')
<style>
  
    .table-container::-webkit-scrollbar { height: 12px; }
    .table-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .table-container::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
    .table-container::-webkit-scrollbar-thumb:hover { background: #64748b; }
    .text-vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        text-align: center;
        white-space: nowrap;
    }
    
    .excel-table th, .excel-table td {
        border: 1px solid #9ca3af; 
        padding: 4px 8px;
        white-space: nowrap; 
    }
</style>

<div class="m-4">
    <div class="flex justify-between items-end mb-4">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32] uppercase tracking-widest">Inventario General TI</h2>
            <p class="text-gray-500 text-sm">Vista de hoja de cálculo maestra</p>
        </div>
       
        <div class="flex gap-3">
            <a href="{{ route('assets.export') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Exportar a Excel
            </a>
            <a href="{{ route('assets.create') }}" class="bg-[#041b32] hover:opacity-90 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                + Nuevo Registro
            </a>
        </div>
    </div>
</div> <div class="w-full overflow-x-auto table-container bg-white shadow-xl rounded-lg pb-4 border border-gray-400">
    <table class="excel-table w-max text-xs text-center text-gray-800 font-sans border-collapse">
        
        <thead>
            <tr class="text-xs uppercase tracking-wider font-bold">
                <th rowspan="2" class="bg-gray-300 w-10">División</th>
                <th colspan="3" class="bg-green-300">Responsable</th>
                <th colspan="4" class="bg-blue-300">Nomenclatura / CPU / LAP</th>
                <th colspan="5" class="bg-green-300">Características</th>
                <th colspan="2" class="bg-blue-300">Software</th>
                <th colspan="3" class="bg-green-300">Monitor</th>
                <th colspan="3" class="bg-blue-300">Mouse</th>
                <th colspan="3" class="bg-green-300">Teclado</th>
                <th rowspan="2" class="bg-gray-200">Acciones</th>
            </tr>
            
            <tr class="bg-gray-100 text-[10px] uppercase font-bold text-gray-700">
                <th>Nombre PC</th>
                <th>Puesto</th>
                <th>Nombre Completo Asignación</th>
                
                <th>Tipo</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                
                <th>Procesador</th>
                <th>Ram (Gb)</th>
                <th>Tipo Ram</th>
                <th>Almacenamiento</th>
                <th>IP</th>
                
                <th>OS</th>
                <th>Office</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
            </tr>
        </thead>

        <tbody>
            {{-- LA MAGIA DE LOS COLORES EMPIEZA AQUÍ --}}
            @php
                $divColors = ['bg-red-200', 'bg-blue-200', 'bg-green-200', 'bg-purple-200', 'bg-orange-200', 'bg-teal-200', 'bg-pink-200', 'bg-indigo-200', 'bg-yellow-200', 'bg-lime-200'];
            @endphp

            @forelse($groupedAssets as $division => $assets)
                {{-- Escogemos un color basado en el índice actual del ciclo --}}
                @php
                    $currentColor = $divColors[$loop->index % count($divColors)];
                @endphp

                @foreach($assets as $index => $asset)
                    <tr class="hover:bg-gray-50 transition-colors">
                        
                        @if($index == 0)
                            {{-- Aplicamos el color en turno a la celda de la División --}}
                            <td rowspan="{{ $assets->count() }}" class="{{ $currentColor }} font-black text-gray-800 align-middle shadow-inner">
                                <div class="text-vertical mx-auto">{{ strtoupper($division ?? 'SIN DIVISIÓN') }}</div>
                            </td>
                        @endif

                        <td class="font-bold text-blue-900 bg-blue-50/30">
                            <a href="{{ route('assets.show', $asset->id) }}" class="hover:underline">{{ $asset->nomenclatura }}</a>
                        </td>
                        <td>{{ $asset->area }}</td>
                        <td class="font-bold">{{ $asset->encargado }}</td>

                        <td class="bg-blue-50/30">{{ $asset->tipo_equipo }}</td>
                        <td class="bg-blue-50/30">{{ $asset->marca }}</td>
                        <td class="bg-blue-50/30">{{ $asset->modelo }}</td>
                        <td class="bg-blue-50/30 font-mono text-[10px]">{{ $asset->numero_serie }}</td>

                        <td>{{ $asset->procesador }}</td>
                        <td class="font-bold">{{ $asset->ram }}</td>
                        <td>{{ $asset->tipo_ram }}</td>
                        <td>{{ $asset->almacenamiento }}</td>
                        <td class="font-mono text-[10px] text-blue-700 bg-blue-50/30">{{ $asset->ip_address }}</td>

                        <td class="bg-blue-50/30">{{ $asset->sistema_operativo }}</td>
                        <td class="bg-blue-50/30">{{ $asset->paqueteria_office }}</td>

                        <td>{{ $asset->monitor_marca }}</td>
                        <td>{{ $asset->monitor_modelo }}</td>
                        <td class="font-mono text-[10px]">{{ $asset->monitor_ns }}</td>

                        <td class="bg-blue-50/30">{{ $asset->mouse_marca }}</td>
                        <td class="bg-blue-50/30">{{ $asset->mouse_modelo }}</td>
                        <td class="bg-blue-50/30 font-mono text-[10px]">{{ $asset->mouse_ns }}</td>

                        <td>{{ $asset->teclado_marca }}</td>
                        <td>{{ $asset->teclado_modelo }}</td>
                        <td class="font-mono text-[10px]">{{ $asset->teclado_ns }}</td>

                        <td class="bg-gray-50">
                            <a href="{{ route('assets.edit', $asset->id) }}" class="text-blue-600 hover:text-blue-800 p-1 block">
                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="24" class="py-10 text-gray-500 text-base font-bold">No hay equipos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection