@extends('layouts.app')

@section('content')
@php $in = 'w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-sm'; @endphp
<div class="max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold text-slate-800">Bajas de equipo</h2>
    <p class="text-gray-500 text-sm mt-1 mb-6">El técnico solicita la baja desde la ficha del activo; un gerente o administrador la aprueba o rechaza. Al aprobarse se genera el acta.</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm mb-4">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="flex gap-2 mb-4">
        @foreach(['pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas'] as $k => $v)
            <a href="{{ route('bajas.index', ['estado' => $k]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $estado === $k ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $v }} ({{ $conteo[$k] ?? 0 }})</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($bajas as $b)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <a href="{{ route('assets.show', $b->asset_id) }}" class="font-mono font-bold text-[#041b32] hover:underline">{{ $b->asset?->nomenclatura }}</a>
                        <span class="text-sm text-slate-600">{{ $b->asset?->tipo_equipo ?: $b->asset?->nombre }} {{ $b->asset?->marca }} {{ $b->asset?->modelo }}</span>
                        <p class="text-xs text-slate-500 mt-1">Solicitó {{ $b->solicitante?->name }} el {{ $b->created_at->format('d/m/Y') }}{{ $b->destino ? ' · destino: '.(\App\Models\BajaActivo::DESTINOS[$b->destino] ?? $b->destino) : '' }}</p>
                    </div>
                    <div class="flex gap-2 items-start">
                        @if($b->dictamen_path)<a href="{{ Storage::url($b->dictamen_path) }}" target="_blank" class="text-xs border border-slate-300 rounded px-2 py-1">Dictamen</a>@endif
                        @if($b->estado === 'aprobada')<a href="{{ route('bajas.acta', $b) }}" class="text-xs bg-[#041b32] text-white font-bold rounded px-3 py-1">Acta PDF</a>@endif
                    </div>
                </div>
                <p class="text-sm text-slate-700 mt-2 whitespace-pre-line">{{ $b->motivo }}</p>
                @if($b->estado !== 'pendiente')
                    <p class="text-xs mt-2 {{ $b->estado === 'aprobada' ? 'text-green-700' : 'text-red-700' }}">{{ ucfirst($b->estado) }} por {{ $b->revisor?->name }} el {{ $b->revisado_at?->format('d/m/Y H:i') }}{{ $b->comentario_revision ? ': '.$b->comentario_revision : '' }}</p>
                @elseif(auth()->user()->puedeVerTodos())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <form method="POST" action="{{ route('bajas.aprobar', $b) }}" class="flex gap-2" onsubmit="return confirm('¿Aprobar la baja? El activo quedará en estado Baja.');">
                            @csrf
                            <input name="comentario_revision" placeholder="Comentario (opcional)" class="{{ $in }}">
                            <button class="bg-green-600 text-white font-bold px-4 rounded text-sm">Aprobar</button>
                        </form>
                        <form method="POST" action="{{ route('bajas.rechazar', $b) }}" class="flex gap-2">
                            @csrf
                            <input name="comentario_revision" required placeholder="Motivo del rechazo" class="{{ $in }}">
                            <button class="border border-red-300 text-red-700 font-bold px-4 rounded text-sm">Rechazar</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-center text-slate-400 py-8">Sin solicitudes {{ $estado === 'pendiente' ? 'pendientes' : ($estado === 'aprobada' ? 'aprobadas' : 'rechazadas') }}.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $bajas->links() }}</div>
</div>
@endsection
