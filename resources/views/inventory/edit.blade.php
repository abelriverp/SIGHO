@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-semibold text-lg">Modificar: {{ $item->nombre }}</h3>
            <span class="bg-blue-800 text-blue-100 text-xs py-1 px-2 rounded">Act.: {{ $item->updated_at->format('d/m/Y') }}</span>
        </div>

        <form action="{{ route('inventory.update', $item->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Nombre + Cantidad --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $item->nombre) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Stock <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" value="{{ old('cantidad', $item->cantidad) }}" min="0" step="0.01"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            {{-- Motivo del cambio de stock (solo si cambia) --}}
            <div x-data="{ cantidadInicial: {{ $item->cantidad }} }" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <label class="block text-sm font-bold text-yellow-800 mb-1">Motivo del ajuste de stock</label>
                <input type="text" name="motivo_movimiento" value="{{ old('motivo_movimiento') }}"
                    placeholder="Ej: Reabastecimiento semanal, consumo en instalación..."
                    class="w-full rounded-md border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-100 text-sm">
                <p class="text-xs text-yellow-700 mt-1">Se registrará en el historial si la cantidad cambia.</p>
            </div>

            {{-- Área + Stock Mínimo --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Área Responsable <span class="text-red-500">*</span></label>
                    <select name="area" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="">-- Seleccionar área --</option>
                        @foreach($areas as $a)
                            <option value="{{ $a }}" {{ old('area', $item->area) == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Stock Mínimo (alerta)</label>
                    <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $item->stock_minimo) }}" min="0" step="0.01"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            {{-- Categoría + Unidad + Estatus --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach(['Herramientas','Consumibles','Periféricos','Refacciones','Otro'] as $c)
                            <option value="{{ $c }}" {{ old('categoria', $item->categoria) == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Unidad</label>
                    <select name="unidad" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach(['pza','caja','lt','kg','rollo','par','juego'] as $u)
                            <option value="{{ $u }}" {{ old('unidad', $item->unidad) == $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach(['Disponible','Agotado','Baja'] as $s)
                            <option value="{{ $s }}" {{ old('status', $item->status) == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">{{ old('descripcion', $item->descripcion) }}</textarea>
            </div>

            {{-- Archivos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Foto del Artículo</label>
                    @if($item->imagen_path)
                        <div class="flex items-center gap-3 mb-3 p-2 bg-gray-50 rounded border border-gray-200">
                            <img src="{{ Storage::url($item->imagen_path) }}" class="w-16 h-16 object-cover rounded">
                            <span class="text-xs text-gray-500">Imagen actual</span>
                        </div>
                    @endif
                    <input type="file" name="imagen" accept="image/*"
                        class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2 cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-bold text-blue-800 mb-2">Documentación PDF</label>
                    @if($item->pdf_path)
                        <div class="bg-blue-50 p-3 rounded-lg mb-3 border border-blue-100">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-blue-800 font-bold">Documento cargado</span>
                                <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="text-xs text-blue-600 underline">Ver PDF</a>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-1 rounded transition">
                                <input type="checkbox" name="delete_pdf" value="1" class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                                <span class="text-xs font-bold text-red-600">Eliminar documento</span>
                            </label>
                        </div>
                    @endif
                    <input type="file" name="pdf_baja" accept=".pdf"
                        class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2 cursor-pointer">
                </div>
            </div>

            <div class="flex justify-end pt-6 gap-3">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90 transition">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection