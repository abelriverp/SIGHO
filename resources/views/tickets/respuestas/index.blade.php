@extends('layouts.app')

@section('content')
@php $in = 'w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:border-[#041b32]'; @endphp
<div class="max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800">Respuestas predefinidas</h2>
    <p class="text-gray-500 text-sm mt-1 mb-6">Textos que los técnicos insertan con un clic al escribir una nota o una solución.</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm mb-4">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('tickets.respuestas.store') }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Título</label><input name="titulo" required maxlength="120" class="{{ $in }}" placeholder="Ej: Se reinició el equipo"></div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Departamento</label>
            <select name="departamento" class="{{ $in }}"><option value="">Todos</option>@foreach($departamentos as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select>
        </div>
        <div class="md:row-span-2 flex items-end"><button class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm w-full">Agregar</button></div>
        <div class="md:col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1">Texto</label><textarea name="texto" rows="3" required class="{{ $in }}"></textarea></div>
    </form>

    <div class="space-y-3">
        @forelse($respuestas as $r)
            <details class="bg-white rounded-lg shadow-sm border border-slate-200 {{ $r->activo ? '' : 'opacity-60' }}">
                <summary class="p-4 cursor-pointer flex justify-between gap-4">
                    <span class="font-bold text-slate-800">{{ $r->titulo }}</span>
                    <span class="text-xs text-slate-500">{{ $r->departamento ?? 'Todos' }}{{ $r->activo ? '' : ' · inactiva' }}</span>
                </summary>
                <form method="POST" action="{{ route('tickets.respuestas.update', $r) }}" class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf @method('PUT')
                    <input name="titulo" value="{{ $r->titulo }}" required class="{{ $in }}">
                    <select name="departamento" class="{{ $in }}"><option value="">Todos</option>@foreach($departamentos as $d)<option value="{{ $d }}" @selected($r->departamento === $d)>{{ $d }}</option>@endforeach</select>
                    <textarea name="texto" rows="3" required class="{{ $in }} md:col-span-2">{{ $r->texto }}</textarea>
                    <label class="flex items-center gap-2 text-sm"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked($r->activo) class="rounded"> Activa</label>
                    <div class="flex justify-end gap-2">
                        <button class="bg-[#041b32] text-white font-bold py-1.5 px-4 rounded text-sm">Guardar</button>
                        <button form="borrar-{{ $r->id }}" class="border border-red-300 text-red-700 font-bold py-1.5 px-4 rounded text-sm" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                    </div>
                </form>
                <form id="borrar-{{ $r->id }}" method="POST" action="{{ route('tickets.respuestas.destroy', $r) }}">@csrf @method('DELETE')</form>
            </details>
        @empty
            <p class="text-center text-slate-400 py-8">Sin respuestas todavía.</p>
        @endforelse
    </div>
</div>
@endsection
