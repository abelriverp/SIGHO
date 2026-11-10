@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="flex justify-between items-start mb-6">
        <div>
            <a href="{{ route('tickets.categorias.index') }}" class="text-xs text-slate-400 hover:text-slate-600">&larr; Volver a Categorías</a>
            <h2 class="text-2xl font-bold text-[#041b32] mt-1">{{ $categoria->label }}</h2>
            <p class="text-sm text-slate-500">{{ $categoria->departamento?->nombre ?? '—' }} · {{ $categoria->activo ? 'Activa' : 'Inactiva' }}</p>
        </div>
        <a href="{{ route('tickets.categorias.edit', $categoria) }}" class="bg-slate-200 text-slate-700 font-bold py-2 px-4 rounded-lg shadow hover:bg-slate-300 text-sm">Editar categoría</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    @if($categoria->requiere_subcategoria)
        {{-- Lista de sistemas --}}
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Sistemas</h3>
            <a href="{{ route('tickets.categorias.subcategorias.create', ['categoria_id' => $categoria->id]) }}"
                class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-lg shadow hover:opacity-90 text-sm">+ Agregar sistema</a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 border-b">
                    <tr>
                        <th class="py-3 px-6">Sistema</th>
                        <th class="py-3 px-6">Campos</th>
                        <th class="py-3 px-6">Estado</th>
                        <th class="py-3 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoria->subcategorias as $sub)
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-4 px-6 font-medium text-slate-800">{{ $sub->label }}</td>
                        <td class="py-4 px-6 text-slate-600">{{ $sub->campos->count() }} campo(s)</td>
                        <td class="py-4 px-6">
                            @if($sub->activo)
                                <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activo</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right flex justify-end gap-3">
                            <a href="{{ route('tickets.categorias.subcategorias.edit', $sub) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                            <form action="{{ route('tickets.categorias.subcategorias.duplicar', $sub) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-slate-500 hover:text-slate-700 font-bold text-sm" title="Crea una copia con los mismos campos, para un sistema que necesite exactamente lo mismo">Duplicar</button>
                            </form>
                            <form action="{{ route('tickets.categorias.subcategorias.destroy', $sub) }}" method="POST" onsubmit="return confirm('¿Eliminar este sistema y sus campos?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-8 px-6 text-center text-slate-400">Sin sistemas todavía. Agrega el primero.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    @else
        {{-- Campos directos --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Campos que pide</h3>
                <a href="{{ route('tickets.categorias.edit', $categoria) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar campos</a>
            </div>
            @forelse($categoria->campos as $campo)
                <p class="text-sm text-slate-700 py-1 border-b border-slate-50">
                    <span class="font-bold">{{ $campo->label }}</span>
                    <span class="text-slate-400"> ({{ \App\Models\TicketCampo::TIPOS[$campo->tipo_campo] ?? $campo->tipo_campo }}{{ $campo->requerido ? ', obligatorio' : ', opcional' }})</span>
                </p>
            @empty
                <p class="text-sm text-slate-400">Sin campos todavía. Edita la categoría para agregarlos.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection
