@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <h1 class="text-xl font-bold text-gray-800 mb-1">Acceso a Módulos</h1>
    <p class="text-sm text-gray-500 mb-6">Usuario: <strong>{{ $user->name }}</strong> — {{ $user->email }}</p>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.users.modulos.update', $user) }}">
        @csrf
        <div class="bg-white rounded-xl shadow divide-y divide-gray-100">
            @foreach($todosLosModulos as $modulo)
            <label class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 cursor-pointer">
                <input type="checkbox"
                       name="modulos[]"
                       value="{{ $modulo->id }}"
                       {{ in_array($modulo->id, $modulosActivos) ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-blue-600">
                <div>
                    <p class="font-medium text-gray-800">{{ $modulo->nombre }}</p>
                    <p class="text-xs text-gray-400">{{ $modulo->descripcion }}</p>
                </div>
            </label>
            @endforeach
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                Guardar cambios
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection