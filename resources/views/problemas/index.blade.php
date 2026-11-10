@extends('layouts.app')

@section('content')
@php $badge = ['abierto' => 'bg-red-100 text-red-800', 'en_analisis' => 'bg-orange-100 text-orange-800', 'causa_identificada' => 'bg-blue-100 text-blue-800', 'resuelto' => 'bg-green-100 text-green-800', 'cerrado' => 'bg-slate-100 text-slate-600']; @endphp
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Problemas</h2>
            <p class="text-gray-500 text-sm mt-1">La causa común detrás de varios tickets: se documenta la solución temporal, la causa raíz y la solución definitiva.</p>
        </div>
        <a href="{{ route('problemas.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nuevo problema</a>
    </div>

    @if(count($candidatos))
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <p class="font-bold text-amber-900 mb-2">Posibles problemas: 3 o más tickets en 30 días del mismo equipo o lugar</p>
            <div class="flex flex-wrap gap-2">
                @foreach($candidatos as $c)
                    <a href="{{ route('problemas.create', [$c['col'] => $c['id']]) }}" class="bg-white border border-amber-300 rounded-full px-3 py-1 text-sm hover:bg-amber-100">{{ $c['etiqueta'] }} <strong>({{ $c['total'] }})</strong> → abrir problema</a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex gap-2 mb-4">
        @foreach(['abiertos' => 'Abiertos', 'resuelto' => 'Resueltos', 'cerrado' => 'Cerrados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('problemas.index', ['estado' => $k]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $estado === $k ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $v }}</a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">#</th><th class="py-3 px-4">Problema</th><th class="py-3 px-4">Equipo / lugar</th><th class="py-3 px-4 text-center">Tickets</th><th class="py-3 px-4">Responsable</th><th class="py-3 px-4 text-center">Estado</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($problemas as $p)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-xs">P-{{ $p->id }}</td>
                        <td class="py-3 px-4"><a href="{{ route('problemas.show', $p) }}" class="font-bold text-[#041b32] hover:underline">{{ $p->titulo }}</a><div class="text-xs {{ $p->prioridad === 'Alta' ? 'text-red-600' : 'text-slate-400' }}">Prioridad {{ $p->prioridad }} · {{ $p->created_at->format('d/m/Y') }}</div></td>
                        <td class="py-3 px-4 text-xs">{{ $p->asset?->nomenclatura }}{{ $p->asset && $p->ubicacion ? ' · ' : '' }}{{ $p->ubicacion?->etiqueta }}</td>
                        <td class="py-3 px-4 text-center font-bold">{{ $p->tickets_count }}</td>
                        <td class="py-3 px-4 text-xs">{{ $p->responsable?->name }}</td>
                        <td class="py-3 px-4 text-center"><span class="py-1 px-2 rounded-full text-xs font-bold {{ $badge[$p->estado] ?? '' }}">{{ \App\Models\Problema::ESTADOS[$p->estado] ?? $p->estado }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-gray-400">Sin problemas en esta vista.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
