@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('conocimiento.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Base de conocimiento</a>
    <div class="flex flex-wrap justify-between items-start gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $articulo->titulo }}</h2>
            <p class="text-xs text-slate-500 mt-1">
                {{ $articulo->departamento ?? 'General' }}{{ $articulo->categoria ? ' · '.$articulo->categoria : '' }}
                · {{ $articulo->autor?->name ?? '—' }} · actualizado {{ $articulo->updated_at->format('d/m/Y') }}
                · {{ $articulo->vistas }} vistas · usado {{ $articulo->usos }} veces
                @unless($articulo->publicado) · <strong>BORRADOR</strong>@endunless
            </p>
        </div>
        @if(auth()->user()->puedeResponderTickets())
            <div class="flex gap-2">
                <a href="{{ route('conocimiento.edit', $articulo) }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">Editar</a>
                @if(auth()->user()->puedeVerTodos())
                    <form method="POST" action="{{ route('conocimiento.destroy', $articulo) }}" onsubmit="return confirm('¿Eliminar este artículo?');">
                        @csrf @method('DELETE')
                        <button class="border border-red-300 text-red-700 text-sm font-bold py-2 px-4 rounded">Eliminar</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if($articulo->sintoma)
        <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-4">
            <h3 class="text-xs font-bold text-amber-700 uppercase mb-1">Síntoma / cómo lo reportan</h3>
            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $articulo->sintoma }}</p>
        </div>
    @endif
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h3 class="text-xs font-bold text-green-700 uppercase mb-2">Solución</h3>
        <div class="text-slate-800 whitespace-pre-line leading-relaxed">{{ $articulo->solucion }}</div>
    </div>
    <div class="mt-4 text-xs text-slate-500 flex flex-wrap gap-4">
        @if($articulo->etiquetas)<span>Etiquetas: {{ $articulo->etiquetas }}</span>@endif
        @if($articulo->ticketOrigen && auth()->user()->can('view', $articulo->ticketOrigen))
            <a href="{{ route('tickets.show', $articulo->ticket_origen_id) }}" class="underline">Basado en el ticket #{{ $articulo->ticket_origen_id }}</a>
        @endif
    </div>
</div>
@endsection
