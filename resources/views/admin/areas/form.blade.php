@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($area) ? 'Editar Área' : 'Registrar Nueva Área' }}</h3>
        </div>
        
        <form action="{{ isset($area) ? route('admin.areas.update', $area->id) : route('admin.areas.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($area)) @method('PUT') @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del Área/Departamento</label>
                <input type="text" name="nombre" value="{{ $area->nombre ?? old('nombre') }}" required class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
            </div>
            
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.areas.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection