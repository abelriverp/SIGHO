@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10 mb-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">
                {{ $condomino->exists ? 'Editar Condómino: ' . $condomino->nombre : 'Nuevo Condómino' }}
            </h3>
        </div>

        <form action="{{ $condomino->exists ? route('condominios.condominos.update', $condomino->id) : route('condominios.condominos.store') }}"
              method="POST" class="p-6 space-y-6">
            @csrf
            @if($condomino->exists) @method('PUT') @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" value="{{ old('nombre', $condomino->nombre) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $condomino->telefono) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $condomino->email) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Identificación</label>
                    <input type="text" name="identificacion" value="{{ old('identificacion', $condomino->identificacion) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="INE, pasaporte, etc.">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Propietario desde</label>
                    <input type="date" name="fecha_alta" value="{{ old('fecha_alta', optional($condomino->fecha_alta)->format('Y-m-d')) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Notas</label>
                <textarea name="notas" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">{{ old('notas', $condomino->notas) }}</textarea>
            </div>

            <div class="flex justify-end pt-4 gap-3">
                <a href="{{ route('condominios.condominos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
