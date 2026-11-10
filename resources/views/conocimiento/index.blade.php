@extends('layouts.app')

@section('content')
@php $sel = 'bg-white border border-slate-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:border-[#041b32]'; @endphp
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Base de conocimiento</h2>
            <p class="text-gray-500 text-sm mt-1">Soluciones probadas para problemas que se repiten</p>
        </div>
        @if($editor)
            <a href="{{ route('conocimiento.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nuevo artículo</a>
        @endif
    </div>

    <form method="GET" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar</label>
            <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Ej: impresora no imprime, wifi, opera" class="w-full {{ $sel }}">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Departamento</label>
            <select name="departamento" class="{{ $sel }}">
                <option value="">Todos</option>
                @foreach($departamentos as $d)<option value="{{ $d }}" @selected(($filtros['departamento'] ?? '') === $d)>{{ $d }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Buscar</button>
    </form>

    <div class="space-y-3">
        @forelse($articulos as $a)
            <a href="{{ route('conocimiento.show', $a) }}" class="block bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:border-[#041b32] transition">
                <div class="flex justify-between gap-4">
                    <h3 class="font-bold text-[#041b32]">{{ $a->titulo }}</h3>
                    @unless($a->publicado)<span class="text-[10px] font-bold uppercase bg-slate-100 text-slate-500 rounded px-2 py-0.5 h-fit">Borrador</span>@endunless
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $a->departamento ?? 'General' }}{{ $a->categoria ? ' · '.$a->categoria : '' }} · {{ $a->vistas }} vistas · usado {{ $a->usos }} veces</p>
                @if($a->sintoma)<p class="text-sm text-slate-600 mt-2">{{ \Illuminate\Support\Str::limit($a->sintoma, 180) }}</p>@endif
            </a>
        @empty
            <div class="bg-white rounded-lg border border-slate-200 p-10 text-center text-slate-400">
                No hay artículos {{ array_filter($filtros) ? 'con esa búsqueda' : 'todavía' }}. Al resolver un ticket puedes convertir la solución en artículo.
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $articulos->links() }}</div>
</div>
@endsection
