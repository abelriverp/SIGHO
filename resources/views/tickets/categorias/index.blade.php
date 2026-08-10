@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Categorías de Tickets</h2>
            <p class="text-sm text-slate-500">Tipos de solicitud y sistemas que aparecen al levantar un ticket, por departamento. Solo aplican a esta propiedad.</p>
        </div>
        <a href="{{ route('tickets.categorias.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nueva Categoría</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 flex items-center gap-3">
        <label class="text-sm font-bold text-slate-600">Departamento:</label>
        <select name="departamento_id" onchange="this.form.submit()" class="bg-white border border-slate-300 rounded-md py-2 px-3 text-sm">
            <option value="">Todos</option>
            @foreach($departamentos as $depto)
                <option value="{{ $depto->id }}" {{ (string) $departamentoId === (string) $depto->id ? 'selected' : '' }}>{{ $depto->nombre }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Categoría</th>
                    <th class="py-3 px-6">Departamento</th>
                    <th class="py-3 px-6">Estructura</th>
                    <th class="py-3 px-6">Estado</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorias as $cat)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $cat->label }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $cat->departamento?->nombre ?? '—' }}</td>
                    <td class="py-4 px-6 text-slate-600">
                        @if($cat->requiere_subcategoria)
                            {{ $cat->subcategorias_count }} sistema(s)
                        @else
                            {{ $cat->campos_count }} campo(s) directo(s)
                        @endif
                    </td>
                    <td class="py-4 px-6">
                        @if($cat->activo)
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activo</span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactivo</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-right flex justify-end gap-3">
                        <a href="{{ route('tickets.categorias.show', $cat) }}" class="text-slate-600 hover:text-slate-900 font-bold text-sm">Gestionar</a>
                        <a href="{{ route('tickets.categorias.edit', $cat) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                        <form action="{{ route('tickets.categorias.destroy', $cat) }}" method="POST" onsubmit="return confirm('¿Eliminar esta categoría y todo lo que tenga dentro (sistemas/campos)? Los tickets ya creados con ella no se ven afectados.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-8 px-6 text-center text-slate-400">
                        Todavía no hay categorías para esta propiedad. Crea la primera con "+ Nueva Categoría".
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
