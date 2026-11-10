@extends('layouts.app')

@section('content')
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Control de Equipos</h2>
            <p class="text-gray-500 text-sm mt-1">Historial de reparaciones y mantenimiento</p>
        </div>  
        <div class="flex gap-2"> 
            <a href="{{ route('assets.create') }}" class="hotel-blue hover:opacity-90 text-white font-bold py-2 px-4 rounded-lg shadow transition">
                + Nuevo Equipo
            </a>
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Buscar dispositivo..." class="pl-4 pr-10 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-900 shadow-sm w-64 bg-white">
                <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="hotel-blue text-white uppercase text-sm leading-normal">
                    <th class="py-4 px-6 font-semibold">Foto</th>
                    <th class="py-4 px-6 font-semibold">Modelo</th>
                    <th class="py-4 px-6 font-semibold">Nomenclatura</th>
                    <th class="py-4 px-6 font-semibold">Area</th>
                    <th class="py-4 px-6 font-semibold text-center">Estatus</th>
                    <th class="py-4 px-6 font-semibold">Encargado</th>
                    <th class="py-4 px-6 font-semibold">IP</th>
                    <th class="py-4 px-6 font-semibold text-center">Ultima Revision</th>
                    <th class="py-4 px-6 font-semibold text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm" id="tableBody">
                @foreach($assets as $asset)
                <tr class="border-b border-gray-200 hover:bg-gray-50 transition searchable-row">
                    
                    <td class="py-4 px-6">
                        @if($asset->imagen_path)
                            <img src="{{ Storage::url($asset->imagen_path) }}" class="w-10 h-10 object-cover rounded-full border border-gray-200">
                        @else
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-xs text-gray-500 font-bold">
                                NA
                            </div>
                        @endif
                    </td>

                    <td class="py-4 px-6 font-medium text-slate-800">
                        <a href="{{ route('assets.show', $asset->id) }}" class="hover:text-blue-800 hover:underline">
                            {{ $asset->modelo }}
                        </a>
                    </td>
                    
                    <td class="py-4 px-6">{{ $asset->nomenclatura }}</td>
                    <td class="py-4 px-6"><span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">{{ $asset->area }}</span></td>
                    
                    <td class="py-4 px-6 text-center">
                        <span class="py-1 px-3 rounded-full text-xs font-bold
                            {{ $asset->status == 'Activo' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $asset->status == 'En Reparación' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $asset->status == 'Baja' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $asset->status ?? 'Sin Asignar' }}
                        </span>
                    </td>

                    <td class="py-4 px-6">{{ $asset->encargado }}</td>
                    <td class="py-4 px-6 font-mono text-xs">{{ $asset->ip_address }}</td>
                    <td class="py-4 px-6 text-center">
                        {{ $asset->updated_at?->format('d/m/Y') ?? 'Importado' }}
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex item-center justify-center">
                            
                            @if($asset->pdf_path)
                                <a href="{{ Storage::url($asset->pdf_path) }}" target="_blank" class="text-gray-500 hover:text-red-600 mx-1" title="Ver Protocolo PDF">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </a>
                            @endif

                            <a href="{{ route('assets.edit', $asset->id) }}" class="text-gray-500 hover:text-blue-900 mx-1" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>

                            <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este equipo?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600 mx-1" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- Fila de "No se encontraron resultados" oculta por defecto --}}
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="9" class="py-8 text-center text-gray-400 font-medium">
                        No se encontraron equipos que coincidan con tu búsqueda.
                    </td>
                </tr>

                @if($assets->isEmpty())
                <tr class="empty-state">
                    <td colspan="9" class="py-8 text-center text-gray-400">
                        No hay dispositivos registrados aún.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        </div>
    </div>

  {{-- 3. EL SCRIPT MÁGICO PARA EL BUSCADOR EN TIEMPO REAL (VERSIÓN PRECISA) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll('.searchable-row');
            const noResultsRow = document.getElementById('noResultsRow');

            searchInput.addEventListener('keyup', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                let hasVisibleRows = false;

                rows.forEach(row => {
                    // Obtenemos todas las celdas (td) de la fila actual
                    const cells = row.querySelectorAll('td');
                    
                    // Nos aseguramos de que sea una fila normal con datos
                    if (cells.length > 6) {
                        // Extraemos el texto SOLO de las columnas que pediste:
                        const modelo = cells[1].textContent.toLowerCase();
                        const nomenclatura = cells[2].textContent.toLowerCase();
                        const area = cells[3].textContent.toLowerCase();
                        const estatus = cells[4].textContent.toLowerCase();
                        const encargado = cells[5].textContent.toLowerCase();
                        const ip = cells[6].textContent.toLowerCase();

                        // Unimos todo ese texto específico en una sola variable
                        const combinedText = `${modelo} ${nomenclatura} ${area} ${estatus} ${encargado} ${ip}`;

                        // Comparamos si lo que escribiste está dentro de esa combinación
                        if (combinedText.includes(searchTerm)) {
                            row.style.display = '';
                            hasVisibleRows = true;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });

                // Si ninguna fila coincidió y escribiste algo, muestra el mensaje de "No resultados"
                if (!hasVisibleRows && searchTerm !== '') {
                    noResultsRow.style.display = '';
                } else {
                    noResultsRow.style.display = 'none';
                }
            });
        });
    </script>
@endsection