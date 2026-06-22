@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Catálogo de Áreas</h2>
        <a href="{{ route('admin.areas.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nueva Área</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">ID</th>
                    <th class="py-3 px-6">Nombre del Área</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areas as $area)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6 text-slate-400">#{{ $area->id }}</td>
                    <td class="py-4 px-6 font-bold text-slate-800">{{ $area->nombre }}</td>
                    <td class="py-4 px-6 text-right flex justify-end gap-3">
                        <a href="{{ route('admin.areas.edit', $area->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                        <form action="{{ route('admin.areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta área?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection