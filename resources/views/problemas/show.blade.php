@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mb-10">
    <a href="{{ route('problemas.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Problemas</a>
    <div class="flex flex-wrap justify-between items-start gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">P-{{ $problema->id }} · {{ $problema->titulo }}</h2>
            <p class="text-sm text-slate-600">{{ $estados[$problema->estado] ?? $problema->estado }} · prioridad {{ $problema->prioridad }} · responsable {{ $problema->responsable?->name ?? '—' }} · abierto por {{ $problema->creador?->name }} el {{ $problema->created_at->format('d/m/Y') }}{{ $problema->resuelto_at ? ' · resuelto '.$problema->resuelto_at->format('d/m/Y') : '' }}</p>
            <p class="text-sm text-slate-500">
                @if($problema->asset)<a href="{{ route('assets.show', $problema->asset->id) }}" class="underline">{{ $problema->asset->nomenclatura }}</a>@endif
                @if($problema->ubicacion) · <a href="{{ route('ubicaciones.show', $problema->ubicacion) }}" class="underline">{{ $problema->ubicacion->etiqueta }}</a>@endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('cambios.create', ['problema_id' => $problema->id]) }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">+ Registrar cambio</a>
            <a href="{{ route('problemas.edit', $problema) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded">Editar / actualizar</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-5 mb-4"><p class="text-xs font-bold text-slate-400 uppercase mb-1">Descripción</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $problema->descripcion }}</p></div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach([['Solución temporal', $problema->workaround, 'amber'], ['Causa raíz', $problema->causa_raiz, 'red'], ['Solución definitiva', $problema->solucion_definitiva, 'green']] as [$t, $v, $c])
            <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-{{ $c }}-700 uppercase mb-1">{{ $t }}</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $v ?: 'Pendiente' }}</p></div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">{{ $problema->tickets->count() }} ticket(s) ligado(s)</div>
        @forelse($problema->tickets as $t)
            <a href="{{ route('tickets.show', $t->id) }}" class="flex justify-between gap-4 px-6 py-2.5 border-b border-gray-50 hover:bg-slate-50 text-sm">
                <span><strong class="text-[#041b32]">#{{ $t->id }}</strong> {{ \Illuminate\Support\Str::limit($t->descripcion, 100) }}</span>
                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $t->created_at->format('d/m/Y') }} · {{ $t->status }}</span>
            </a>
        @empty
            <p class="px-6 py-5 text-sm text-slate-400">Sin tickets ligados. Lígalos desde el detalle de cada ticket.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">Cambios relacionados</div>
        @forelse($problema->cambios as $c)
            <a href="{{ route('cambios.show', $c) }}" class="flex justify-between px-6 py-2.5 border-b border-gray-50 hover:bg-slate-50 text-sm"><span><strong>C-{{ $c->id }}</strong> {{ $c->titulo }}</span><span class="text-xs text-slate-500">{{ \App\Models\Cambio::ESTADOS[$c->estado] ?? $c->estado }}</span></a>
        @empty
            <p class="px-6 py-5 text-sm text-slate-400">Sin cambios registrados.</p>
        @endforelse
    </div>
</div>
@endsection
