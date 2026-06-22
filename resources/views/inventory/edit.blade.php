@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Editar Artículo</h2>
        <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium">
           
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-semibold text-lg">Modificar: {{ $item->nombre }}</h3>
            <span class="bg-blue-800 text-blue-100 text-xs py-1 px-2 rounded">Act.: {{ $item->updated_at->format('d/m/Y') }}</span>
        </div>
        
        <form action="{{ route('inventory.update', $item->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="nombre" value="{{ $item->nombre }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Stock</label>
                    <input type="number" name="cantidad" value="{{ $item->cantidad }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Herramientas" {{ $item->categoria == 'Herramientas' ? 'selected' : '' }}>Herramientas</option>
                        <option value="Consumibles" {{ $item->categoria == 'Consumibles' ? 'selected' : '' }}>Consumibles</option>
                        <option value="Periféricos" {{ $item->categoria == 'Periféricos' ? 'selected' : '' }}>Periféricos</option>
                        <option value="Refacciones" {{ $item->categoria == 'Refacciones' ? 'selected' : '' }}>Refacciones</option>
                        <option value="Otro" {{ $item->categoria == 'Otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus Actual</label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Disponible" {{ $item->status == 'Disponible' ? 'selected' : '' }}>Disponible</option>
                        <option value="Agotado" {{ $item->status == 'Agotado' ? 'selected' : '' }}>Agotado</option>
                        <option value="Baja" {{ $item->status == 'Baja' ? 'selected' : '' }}>Baja / Falla</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">{{ $item->descripcion }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
  
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Foto del Artículo</label>
                    @if($item->imagen_path)
                        <div class="flex items-center gap-3 mb-3 p-2 bg-gray-50 rounded border border-gray-200">
                            <img src="{{ Storage::url($item->imagen_path) }}" class="w-16 h-16 object-cover rounded">
                            <span class="text-xs text-gray-500">Imagen Actual</span>
                        </div>
                    @endif
                    <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2 hover:file:bg-blue-100 cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-bold text-blue-800 mb-2">Documentación / Reporte (PDF)</label>
                    
                    @if($item->pdf_path)
                        <div class="bg-blue-50 p-3 rounded-lg mb-3 border border-blue-100">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-blue-800 font-bold">Documento cargado</span>
                                <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="text-xs text-blue-600 underline hover:text-blue-800">Ver PDF</a>
                            </div>
                            
                            {{-- Checkbox para eliminar --}}
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-100 p-1 rounded transition">
                                <input type="checkbox" name="delete_pdf" value="1" class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                                <span class="text-xs font-bold text-red-600">Eliminar documento</span>
                            </label>
                        </div>
                    @endif

                    <input type="file" name="pdf_baja" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-xs text-gray-400 mt-1">Subir un documento no cambia el estatus automáticamente.</p>
                </div>
            </div>

            <div class="flex justify-end pt-6 gap-3">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90 transition">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection