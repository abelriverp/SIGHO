@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6 mt-6">
        <h2 class="text-3xl font-bold text-[#041b32]">Inventario de Suministros</h2>
        <a href="{{ route('inventory.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
            + Nuevo Artículo
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#041b32] text-white uppercase text-sm">
                    <th class="py-3 px-6">Foto</th>
                    <th class="py-3 px-6">Nombre</th>
                    <th class="py-3 px-6">Categoría</th>
                    <th class="py-3 px-6 text-center">Estatus</th>
                    <th class="py-3 px-6 text-center">Stock</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm">
                @foreach($items as $item)
                <tr class="border-b hover:bg-gray-50 transition">
                    {{-- COLUMNA FOTO REDONDA --}}
                    <td class="py-4 px-6">
                        @if($item->imagen_path)
                            <img src="{{ Storage::url($item->imagen_path) }}" class="w-10 h-10 object-cover rounded-full border border-gray-200 shadow-sm">
                        @else
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-xs text-gray-500 font-bold border border-gray-300">
                                NA
                            </div>
                        @endif
                    </td>

                    <td class="py-4 px-6 font-bold text-[#041b32]">
                        <a href="{{ route('inventory.show', $item->id) }}" class="hover:underline">{{ $item->nombre }}</a>
                    </td>
                    <td class="py-4 px-6">
                        <span class="bg-blue-50 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">{{ $item->categoria }}</span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="py-1 px-3 rounded-full text-xs font-bold
                            {{ $item->status == 'Disponible' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $item->status == 'Agotado' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $item->status == 'Baja' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="font-bold text-lg {{ $item->cantidad < 5 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $item->cantidad }}
                        </span>
                    </td>
                    
                    <td class="py-4 px-6 text-center">
                        <div class="flex items-center justify-center">
                            
                            {{-- ICONO PDF --}}
                            @if($item->pdf_path)
                                <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="text-gray-500 hover:text-red-600 mx-1" title="Ver Reporte PDF">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </a>
                            @endif

                            {{-- Botón Editar --}}
                            <a href="{{ route('inventory.edit', $item->id) }}" class="text-gray-500 hover:text-blue-900 mx-1" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>

                            {{-- Botón Eliminar --}}
                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este artículo?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-600 mx-1" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                            
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection