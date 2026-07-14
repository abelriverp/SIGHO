@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10 mb-10">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">
                {{ $unidad->exists ? 'Editar Unidad: ' . $unidad->identificador : 'Nueva Unidad' }}
            </h3>
        </div>

        <form action="{{ $unidad->exists ? route('condominios.unidades.update', $unidad->id) : route('condominios.unidades.store') }}"
              method="POST" class="p-6 space-y-6">
            @csrf
            @if($unidad->exists) @method('PUT') @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Identificador <span class="text-red-500">*</span></label>
                    <input type="text" name="identificador" value="{{ old('identificador', $unidad->identificador) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Ej: 101, PH-2, Depto 4B">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de unidad</label>
                    <input type="text" name="tipo_unidad" value="{{ old('tipo_unidad', $unidad->tipo_unidad) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Ej: Studio, 1 recámara, Penthouse">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Torre / Edificio</label>
                    <input type="text" name="torre" value="{{ old('torre', $unidad->torre) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Opcional">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Piso</label>
                    <input type="text" name="piso" value="{{ old('piso', $unidad->piso) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                        placeholder="Opcional">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Condómino (dueño)</label>
                    <select name="condomino_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        <option value="">-- Sin asignar --</option>
                        @foreach($condominos as $c)
                            <option value="{{ $c->id }}" {{ old('condomino_id', $unidad->condomino_id) == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <a href="{{ route('condominios.condominos.create') }}" class="text-blue-700 hover:underline">+ Registrar un nuevo condómino</a>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                    <select name="estatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                        @foreach($estatus as $s)
                            <option value="{{ $s }}" {{ old('estatus', $unidad->estatus ?? 'Vacia') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Se actualiza automáticamente al hacer check-in/check-out.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Notas</label>
                <textarea name="notas" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-100">{{ old('notas', $unidad->notas) }}</textarea>
            </div>

            <div class="flex justify-end pt-4 gap-3">
                <a href="{{ route('condominios.unidades.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
