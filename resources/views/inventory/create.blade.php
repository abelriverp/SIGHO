@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Registrar Nuevo Suministro</h3>
        </div>
        
        <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Artículo</label>
                    <input type="text" name="nombre" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" placeholder="Ej: Crimpadora RJ45">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad (Stock)</label>
                    <input type="number" name="cantidad" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" value="1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Herramientas">Herramientas</option>
                        <option value="Consumibles">Consumibles</option>
                        <option value="Periféricos">Periféricos</option>
                        <option value="Refacciones">Refacciones</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus Inicial</label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Disponible">Disponible</option>
                        <option value="Agotado">Agotado</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Foto (Opcional)</label>
                    <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-slate-500 file:bg-blue-50 file:text-blue-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                </div>
                <div>
                    <label class="block text-sm font-bold text-red-700 mb-1">Reporte (Opcional)</label>
                    <input type="file" name="pdf_baja" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-red-50 file:text-red-700 file:rounded-full file:border-0 file:px-4 file:py-2">
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <a href="{{ route('inventory.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition mr-2">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection