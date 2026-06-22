@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 mt-6">
    
    <div class="mb-6">
        <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium flex items-center gap-1 transition">
          
        </a>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-6">
        
        <div class="flex-1 w-full">
            <h2 class="text-3xl font-bold text-[#041b32] mb-1">Detalle del Suministro</h2>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 mt-4">
                <div class="col-span-1 md:col-span-2">
                    <span class="block text-xs text-gray-400 uppercase font-bold">Nombre</span>
                    <span class="text-xl font-bold text-slate-800">{{ $item->nombre }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Categoría</span>
                    <span class="bg-blue-50 text-blue-800 py-1 px-3 rounded-full text-xs font-bold inline-block mt-1">{{ $item->categoria }}</span>
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Stock Actual</span>
                    <span class="text-2xl font-bold {{ $item->cantidad < 5 ? 'text-red-600' : 'text-[#041b32]' }}">
                        {{ $item->cantidad }}
                    </span>
                </div>
                <div class="col-span-1 md:col-span-2 border-t border-gray-50 pt-4">
                    <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Descripción</span>
                    <p class="text-gray-600 text-sm">{{ $item->descripcion ?? 'Sin descripción.' }}</p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/3 space-y-4">
            
            <div class="bg-white p-2 rounded-lg shadow-sm border border-gray-100">
                <div class="aspect-video bg-gray-50 rounded overflow-hidden flex items-center justify-center">
                    @if($item->imagen_path)
                        <img src="{{ Storage::url($item->imagen_path) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-medium text-gray-400">Sin Foto</span>
                    @endif
                </div>
            </div>

            @if($item->pdf_path)
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-800 uppercase">Documentación</span>
                    <a href="{{ Storage::url($item->pdf_path) }}" target="_blank" class="bg-white hover:bg-gray-50 text-blue-700 border border-blue-200 text-xs font-bold py-2 px-3 rounded shadow-sm inline-flex items-center gap-2">
                        Ver PDF
                    </a>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection