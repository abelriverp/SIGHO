@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 mt-6">

    {{-- Cabecera --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver al inventario
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $item->nombre }}</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="bg-indigo-50 text-indigo-800 py-1 px-2 rounded-full text-xs font-bold">{{ $item->area ?? 'Sin área' }}</span>
                <span class="bg-blue-50 text-blue-800 py-1 px-2 rounded-full text-xs font-bold">{{ $item->categoria }}</span>
                @php
                    $badgeClass = match($item->status) {
                        'Disponible' => 'bg-green-100 text-green-800',
                        'Agotado'    => 'bg-yellow-100 text-yellow-800',
                        'Baja'       => 'bg-red-100 text-red-800',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badgeClass }}">{{ $item->status }}</span>
            </div>
        </div>
        <a href="{{ route('inventory.edit', $item->id) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded shadow hover:opacity-90">
            Editar
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Info principal --}}
        <div class="md:col-span-2 space-y-4">

            {{-- Tarjeta de stock --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Stock</h3>
                <div class="flex items-end gap-6">
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block">Cantidad actual</span>
                        <span class="text-4xl font-bold {{ $item->bajoPorStock() ? 'text-red-600' : 'text-[#041b32]' }}">
                            {{ $item->cantidad }}
                        </span>
                        <span class="text-sm text-gray-500 ml-1">{{ $item->unidad }}</span>
                    </div>
                    @if($item->stock_minimo > 0)
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block">Stock mínimo</span>
                        <span class="text-2xl font-bold text-gray-400">{{ $item->stock_minimo }}</span>
                    </div>
                    @endif
                    @if($item->bajoPorStock())
                        <div class="flex-1">
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-lg block text-center">
                                ⚠ Stock bajo — Reabastecer pronto
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Descripción --}}
            @if($item->descripcion)
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">Descripción</h3>
                <p class="text-gray-600 text-sm">{{ $item->descripcion }}</p>
            </div>
            @endif

            {{-- Historial de movimientos --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase">Historial de movimientos</h3>
                </div>
                @if($item->movimientos->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                <th class="py-2 px-4 text-left">Fecha</th>
                                <th class="py-2 px-4 text-left">Tipo</th>
                                <th class="py-2 px-4 text-right">Cantidad</th>
                                <th class="py-2 px-4 text-right">Resultado</th>
                                <th class="py-2 px-4 text-left">Motivo</th>
                                <th class="py-2 px-4 text-left">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($item->movimientos->sortByDesc('created_at') as $mov)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 text-gray-500 text-xs">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 px-4">
                                    <span class="text-xs font-bold {{ $mov->tipo == 'entrada' ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }} px-2 py-1 rounded-full">
                                        {{ $mov->tipo == 'entrada' ? '↑ Entrada' : '↓ Salida' }}
                                    </span>
                                </td>
                                <td class="py-2 px-4 text-right font-bold {{ $mov->tipo == 'entrada' ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $mov->tipo == 'entrada' ? '+' : '-' }}{{ $mov->cantidad }}
                                </td>
                                <td class="py-2 px-4 text-right text-gray-600 font-bold">{{ $mov->cantidad_nueva }}</td>
                                <td class="py-2 px-4 text-gray-500 text-xs">{{ $mov->motivo ?? '—' }}</td>
                                <td class="py-2 px-4 text-gray-500 text-xs">{{ $mov->usuario->name ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-10 text-center text-gray-400 text-sm">
                        Sin movimientos registrados aún.
                    </div>
                @endif
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-4">
            {{-- Foto --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2">
                <div class="aspect-square bg-gray-50 rounded overflow-hidden flex items-center justify-center">
                    @if($item->imagen_path)
                        <img src="{{ Storage::url($item->imagen_path) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-medium text-gray-400">Sin Foto</span>
                    @endif
                </div>
            </div>

            {{-- PDF --}}
            @if($item->pdf_path)
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-800 uppercase">Documentación</span>
                    <a href="{{ Storage::url($item->pdf_path) }}" target="_blank"
                        class="bg-white hover:bg-gray-50 text-blue-700 border border-blue-200 text-xs font-bold py-2 px-3 rounded shadow-sm">
                        Ver PDF
                    </a>
                </div>
            @endif

            {{-- Meta --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-2 text-xs text-gray-500">
                <div><span class="font-bold text-gray-700">Hotel:</span> {{ $item->hotel_code ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Registrado por:</span> {{ $item->usuario->name ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Creado:</span> {{ $item->created_at->format('d/m/Y') }}</div>
                <div><span class="font-bold text-gray-700">Última actualización:</span> {{ $item->updated_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection