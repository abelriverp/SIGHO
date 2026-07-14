@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Propiedades</h2>
        <a href="{{ route('admin.hoteles.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nueva Propiedad</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Código</th>
                    <th class="py-3 px-6">Nombre</th>
                    <th class="py-3 px-6">Tipo</th>
                    <th class="py-3 px-6">Usuarios</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hoteles as $hotel)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6 font-mono text-xs font-bold text-slate-500">{{ $hotel->hotel_code }}</td>
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $hotel->nombre }}</td>
                    <td class="py-4 px-6">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $hotel->tipoLabel() }}</span>
                    </td>
                    <td class="py-4 px-6 text-slate-600">{{ $hotel->usuarios_count }}</td>
                    <td class="py-4 px-6 text-right flex justify-end gap-3">
                        <a href="{{ route('admin.hoteles.edit', $hotel->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                        <form action="{{ route('admin.hoteles.destroy', $hotel->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta propiedad?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-gray-500">No hay propiedades registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection