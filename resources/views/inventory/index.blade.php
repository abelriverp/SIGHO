@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4">

    {{-- Cabecera --}}
    <div class="flex justify-between items-center mb-6 mt-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Inventario de Suministros</h2>
            @if($bajoStock > 0)
                <a href="{{ route('inventory.index', ['bajo_stock' => 1]) }}"
                   class="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-100 px-2 py-1 rounded-full mt-1 hover:bg-red-200 transition">
                    ⚠ {{ $bajoStock }} artículo(s) con stock bajo — ver →
                </a>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.reportes') }}" class="bg-white text-[#041b32] border border-gray-200 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                📊 Reportes
            </a>
            <a href="{{ route('inventory.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                + Nuevo Artículo
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('inventory.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3">

        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
            placeholder="Buscar nombre..."
            class="col-span-2 md:col-span-2 rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">

        <select name="area" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todas las áreas</option>
            @foreach($areas as $a)
                <option value="{{ $a }}" {{ ($filtros['area'] ?? '') == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>

        <select name="categoria" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $c)
                <option value="{{ $c }}" {{ ($filtros['categoria'] ?? '') == $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            <option value="">Todos los estatus</option>
            @foreach(['Disponible','Agotado','Baja'] as $s)
                <option value="{{ $s }}" {{ ($filtros['status'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-[#041b32] text-white text-sm font-bold py-2 px-3 rounded hover:opacity-90">Filtrar</button>
            <a href="{{ route('inventory.index') }}" class="flex-1 text-center bg-gray-100 text-gray-700 text-sm font-bold py-2 px-3 rounded hover:bg-gray-200">Limpiar</a>
        </div>
    </form>

    {{-- Alerta de filtro activo --}}
    @if(!empty($filtros['bajo_stock']))
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-2 rounded mb-4 flex items-center justify-between">
            <span>⚠ Mostrando solo artículos con stock bajo</span>
            <a href="{{ route('inventory.index') }}" class="text-red-600 font-bold hover:underline text-xs">Ver todos</a>
        </div>
    @endif

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#041b32] text-white uppercase text-xs">
                    <th class="py-3 px-4">Foto</th>
                    <th class="py-3 px-4">Nombre</th>
                    <th class="py-3 px-4">Área</th>
                    <th class="py-3 px-4">Categoría</th>
                    <th class="py-3 px-4 text-center">Estatus</th>
                    <th class="py-3 px-4 text-center">Stock</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm divide-y divide-gray-100">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 transition {{ $item->bajoPorStock() ? 'bg-red-50' : '' }}">

                    {{-- Foto --}}
                    <td class="py-3 px-4">
                        @if($item->imagen_path)
                            <img src="{{ Storage::url($item->imagen_path) }}" class="w-10 h-10 object-cover rounded-full border border-gray-200 shadow-sm">
                        @else
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-xs text-gray-400 font-bold border border-gray-200">
                                NA
                            </div>
                        @endif
                    </td>

                    {{-- Nombre --}}
                    <td class="py-3 px-4">
                        <a href="{{ route('inventory.show', $item->id) }}" class="font-bold text-[#041b32] hover:underline">
                            {{ $item->nombre }}
                        </a>
                        @if($item->bajoPorStock())
                            <span class="ml-1 text-xs text-red-600 font-bold">⚠ Stock bajo</span>
                        @endif
                    </td>

                    {{-- Área --}}
                    <td class="py-3 px-4">
                        <span class="bg-indigo-50 text-indigo-800 py-1 px-2 rounded-full text-xs font-bold">
                            {{ $item->area ?? '—' }}
                        </span>
                    </td>

                    {{-- Categoría --}}
                    <td class="py-3 px-4">
                        <span class="bg-blue-50 text-blue-800 py-1 px-2 rounded-full text-xs font-bold">
                            {{ $item->categoria }}
                        </span>
                    </td>

                    {{-- Estatus --}}
                    <td class="py-3 px-4 text-center">
                        @php
                            $badgeClass = match($item->status) {
                                'Disponible' => 'bg-green-100 text-green-800',
                                'Agotado'    => 'bg-yellow-100 text-yellow-800',
                                'Baja'       => 'bg-red-100 text-red-800',
                                default      => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badgeClass }}">
                            {{ $item->status }}
                        </span>
                    </td>

                    {{-- Stock --}}
                    <td class="py-3 px-4 text-center">
                        <span class="font-bold text-base {{ $item->bajoPorStock() ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $item->cantidad }}
                        </span>
                        <span class="text-xs text-gray-400 ml-0.5">{{ $item->unidad }}</span>
                        @if($item->stock_minimo > 0)
                            <div class="text-xs text-gray-400">mín: {{ $item->stock_minimo }}</div>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Ver / historial --}}
                            <a href="{{ route('inventory.show', $item->id) }}" class="text-gray-400 hover:text-[#041b32]" title="Ver detalle e historial">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </a>
                            {{-- Editar --}}
                            <a href="{{ route('inventory.edit', $item->id) }}" class="text-gray-400 hover:text-blue-900" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            {{-- PDF --}}
                            @if($item->pdf_path)
                                <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="text-gray-400 hover:text-red-600" title="Ver PDF">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </a>
                            @endif
                            {{-- Eliminar --}}
                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirm('¿Eliminar {{ addslashes($item->nombre) }}? Esta acción no se puede deshacer.');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center text-gray-400">
                        <div class="text-4xl mb-2">📦</div>
                        <p class="font-medium">No hay artículos registrados</p>
                        <a href="{{ route('inventory.create') }}" class="text-blue-700 text-sm hover:underline mt-1 inline-block">+ Registrar el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @endif

    <div class="text-xs text-gray-400 mt-3 text-right">
        {{ $items->total() }} artículo(s) total — Hotel: {{ config('app.hotel_code') }}
    </div>
</div>
@endsection