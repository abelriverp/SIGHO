@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Catálogo de Departamentos</h2>
            <p class="text-sm text-slate-500">Departamentos a los que se les puede levantar/asignar un ticket (Sistemas, Mantenimiento, Prevención, etc.)</p>
        </div>
        <a href="{{ route('admin.departamentos.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nuevo Departamento</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Nombre</th>
                    <th class="py-3 px-6">Estado</th>
                    <th class="py-3 px-6">Tickets</th>
                    <th class="py-3 px-6">Usuarios asignados</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departamentos as $depto)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $depto->nombre }}</td>
                    <td class="py-4 px-6">
                        @if($depto->activo)
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activo</span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactivo</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-slate-600">{{ $depto->tickets_count }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $depto->usuarios_count }}</td>
                    <td class="py-4 px-6 text-right flex justify-end gap-3">
                        <a href="{{ route('admin.departamentos.edit', $depto->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                        <form action="{{ route('admin.departamentos.destroy', $depto->id) }}" method="POST" onsubmit="return confirm('¿Eliminar departamento? Solo se puede si no tiene tickets ni usuarios asignados.');">
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
</div>
@endsection
