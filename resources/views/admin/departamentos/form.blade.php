@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($departamento) ? 'Editar Departamento' : 'Registrar Nuevo Departamento' }}</h3>
        </div>

        <form action="{{ isset($departamento) ? route('admin.departamentos.update', $departamento->id) : route('admin.departamentos.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($departamento)) @method('PUT') @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <ul class="list-disc ml-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del Departamento</label>
                <input type="text" name="nombre" value="{{ $departamento->nombre ?? old('nombre') }}" required
                    placeholder="Ej: Prevención"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                <p class="text-[10px] text-slate-400 mt-1 italic">
                    Este nombre aparecerá para elegir a quién se envía un ticket, y para asignar
                    a qué departamento atiende cada usuario técnico.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="activo" name="activo" value="1"
                    {{ (!isset($departamento) || $departamento->activo) ? 'checked' : '' }}
                    class="w-4 h-4">
                <label for="activo" class="text-sm font-bold text-slate-700">Activo (visible en nuevos tickets y usuarios)</label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.departamentos.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
