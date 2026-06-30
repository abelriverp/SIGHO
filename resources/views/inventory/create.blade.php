@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Registrar Nuevo Suministro</h3>
        </div>

        <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Nombre + Cantidad --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Artículo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Ej: Crimpadora RJ45">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" value="{{ old('cantidad', 1) }}" min="0" step="0.01"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            {{-- Área + Stock Mínimo --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Área Responsable <span class="text-red-500">*</span></label>
                    <select name="area" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="">-- Seleccionar área --</option>
                        @foreach($areas as $a)
                            <option value="{{ $a }}" {{ old('area') == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Stock Mínimo (alerta)</label>
                    <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 0) }}" min="0" step="0.01"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                    <p class="text-xs text-gray-400 mt-1">El sistema alertará cuando el stock llegue a este nivel.</p>
                </div>
            </div>

            {{-- Categoría + Unidad + Estatus --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach(['Herramientas','Consumibles','Periféricos','Refacciones','Otro'] as $c)
                            <option value="{{ $c }}" {{ old('categoria') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Unidad</label>
                    <select name="unidad" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach(['pza','caja','lt','kg','rollo','par','juego'] as $u)
                            <option value="{{ $u }}" {{ old('unidad','pza') == $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="Disponible" {{ old('status','Disponible') == 'Disponible' ? 'selected' : '' }}>Disponible</option>
                        <option value="Agotado"    {{ old('status') == 'Agotado' ? 'selected' : '' }}>Agotado</option>
                        <option value="Baja"       {{ old('status') == 'Baja' ? 'selected' : '' }}>Baja</option>
                    </select>
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">{{ old('descripcion') }}</textarea>
            </div>

            {{-- Archivos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Foto (Opcional)</label>
                    <input type="file" name="imagen" accept="image/*"
                        class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                </div>
                <div>
                    <label class="block text-sm font-bold text-red-700 mb-1">Reporte PDF (Opcional)</label>
                    <input type="file" name="pdf_baja" accept=".pdf"
                        class="block w-full text-sm text-slate-500 file:bg-red-50 file:text-red-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                </div>
            </div>

            <div class="flex justify-end pt-4 gap-3">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection