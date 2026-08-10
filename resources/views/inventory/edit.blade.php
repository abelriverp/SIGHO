@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-10 mb-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4 flex items-center justify-between">
            <h3 class="text-white font-semibold text-lg">Editar Artículo: {{ $item->nombre }}</h3>
            <a href="{{ route('inventory.show', $item->id) }}" class="text-white text-xs underline hover:text-gray-200">Ver detalle</a>
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
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Artículo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $item->nombre) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Ej: Papel higiénico industrial">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad actual <span class="text-red-500">*</span></label>
                        <input type="number" name="cantidad" value="{{ old('cantidad', $item->cantidad) }}" min="0" step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Unidad</label>
                        <select name="unidad" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                            @foreach(['pza','caja','lt','kg','rollo','par','juego','bolsa','sobre','galón'] as $u)
                                <option value="{{ $u }}" {{ old('unidad', $item->unidad ?? 'pza') == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="text-xs text-amber-600 -mt-4 md:col-span-2">
                    ⚠ Si cambias la cantidad aquí, se registrará como un ajuste manual en el historial de movimientos (no como entrada/salida por solicitud de huésped).
                </p>
            </div>

            {{-- Motivo del ajuste (solo si cambia la cantidad) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Motivo del ajuste de cantidad (opcional)</label>
                <input type="text" name="motivo_movimiento" value="{{ old('motivo_movimiento') }}" maxlength="255"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                    placeholder="Ej: Corrección de inventario físico">
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
                    <p class="text-xs text-gray-400 mt-1">El sistema alertará cuando el stock llegue a este nivel (no afecta el estatus del artículo).</p>
                </div>
            </div>

            {{-- Categoría --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                    <option value="">-- Seleccionar --</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c }}" {{ old('categoria', $item->categoria) == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                    placeholder="Notas adicionales, marca, especificaciones...">{{ old('descripcion', $item->descripcion) }}</textarea>
            </div>

            {{-- Portal de huésped (QR) --}}
            <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-lg p-3">
                <input type="checkbox" id="disponible_huesped" name="disponible_huesped" value="1"
                    {{ old('disponible_huesped', $item->disponible_huesped) ? 'checked' : '' }} class="mt-1 w-4 h-4">
                <label for="disponible_huesped" class="text-sm text-slate-700">
                    <span class="font-bold">Disponible para pedir desde el QR de habitación</span>
                    <p class="text-xs text-slate-500">El huésped podrá solicitarlo directo desde el portal (ver "Portal de huésped (QR)" en el menú de Tickets). Déjalo sin marcar para artículos de uso interno.</p>
                </label>
            </div>

            {{-- Archivos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Foto</label>
                    @if($item->imagen_path)
                        <div class="mb-2">
                            <img src="{{ Storage::url($item->imagen_path) }}" class="w-20 h-20 object-cover rounded border border-gray-200">
                        </div>
                    @endif
                    <input type="file" name="imagen" accept="image/*"
                        class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                    <p class="text-xs text-gray-400 mt-1">Sube una nueva imagen solo si quieres reemplazar la actual.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Documento PDF</label>
                    @if($item->pdf_path)
                        <div class="mb-2 flex items-center gap-3">
                            <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="text-blue-700 text-xs underline">Ver PDF actual</a>
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_pdf" value="1"> Eliminar
                            </label>
                        </div>
                    @endif
                    <input type="file" name="pdf_baja" accept=".pdf"
                        class="block w-full text-sm text-slate-500 file:bg-red-50 file:text-red-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                    <p class="text-xs text-gray-400 mt-1">Ficha técnica, vale de baja, etc.</p>
                </div>
            </div>

            <div class="flex justify-end pt-4 gap-3">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
