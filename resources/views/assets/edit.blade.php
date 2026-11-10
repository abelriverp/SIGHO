@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-6 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Editar activo</h2>
        <a href="{{ route('assets.show', $asset->id) }}" class="text-gray-500 hover:text-[#041b32] font-medium">Volver</a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-semibold text-lg">Modificar: {{ $asset->nomenclatura }}</h3>
            <span class="bg-blue-800 text-blue-100 text-xs py-1 px-2 rounded font-bold">Act.: {{ $asset->updated_at?->format('d/m/Y') }}</span>
        </div>
        <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" class="p-6 bg-slate-50/30">
            @csrf
            @method('PUT')
            @include('assets._form')
            <div class="flex justify-end pt-6 gap-3 border-t border-slate-200 mt-6">
                <a href="{{ route('assets.show', $asset->id) }}" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition shadow-sm">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md hover:opacity-95 transition">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
