@extends('layouts.app')

@section('content')
@php $e = $contrato->estadoVigencia(); $d = $contrato->diasRestantes(); @endphp
<div class="max-w-5xl mx-auto mb-10">
    <a href="{{ route('contratos.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Contratos</a>
    <div class="flex flex-wrap justify-between items-start gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $contrato->nombre }}</h2>
            <p class="text-sm text-slate-600">{{ $tipos[$contrato->tipo] ?? $contrato->tipo }}{{ $contrato->proveedor ? ' · '.$contrato->proveedor->nombre : '' }}{{ $contrato->numero ? ' · '.$contrato->numero : '' }}{{ $contrato->activo ? '' : ' · INACTIVO' }}</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos())<a href="{{ route('contratos.edit', $contrato) }}" class="border border-slate-300 text-slate-700 text-sm font-bold py-2 px-4 rounded">Editar</a>@endif
            @if(auth()->user()->puedeVerTodos())
                <form method="POST" action="{{ route('contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Eliminar este contrato?');">@csrf @method('DELETE')<button class="border border-red-300 text-red-700 text-sm font-bold py-2 px-4 rounded">Eliminar</button></form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Vigencia</p><p class="text-sm font-bold text-slate-800 mt-1">{{ $contrato->fecha_inicio?->format('d/m/Y') ?? '—' }} → {{ $contrato->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Estado</p><p class="text-sm font-bold mt-1 {{ $e === 'vencido' ? 'text-red-700' : ($e === 'por_vencer' ? 'text-orange-700' : 'text-green-700') }}">{{ $e === 'vencido' ? 'Vencido hace '.abs($d).' días' : ($d !== null ? 'Faltan '.$d.' días' : 'Sin fecha de fin') }}</p><p class="text-xs text-slate-400">Aviso {{ $contrato->dias_aviso }} días antes{{ $contrato->renovacion_automatica ? ' · renovación automática' : '' }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Costo</p><p class="text-sm font-bold text-slate-800 mt-1">{{ $contrato->costo !== null ? '$'.number_format($contrato->costo, 2).' '.$contrato->moneda : '—' }}</p><p class="text-xs text-slate-400">{{ $periodos[$contrato->periodicidad_pago] ?? '' }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase">Documento</p>@if($contrato->documento_path)<a href="{{ Storage::url($contrato->documento_path) }}" target="_blank" class="text-sm font-bold text-blue-700 underline">Ver PDF</a>@else<p class="text-sm text-slate-400 mt-1">Sin archivo</p>@endif</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase mb-1">Soporte</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $contrato->contacto_soporte ?: ($contrato->proveedor?->soporte ?: '—') }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-slate-400 uppercase mb-1">SLA contratado</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $contrato->sla ?: '—' }}</p></div>
    </div>
    @if($contrato->notas)<div class="bg-white rounded-lg border border-slate-200 p-4 mb-6"><p class="text-xs font-bold text-slate-400 uppercase mb-1">Notas</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $contrato->notas }}</p></div>@endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">{{ $contrato->activos->count() }} equipo(s) cubierto(s)</div>
        @forelse($contrato->activos as $a)
            <a href="{{ route('assets.show', $a->id) }}" class="flex justify-between px-6 py-2.5 border-b border-gray-50 hover:bg-slate-50 text-sm"><span><span class="font-mono font-bold text-[#041b32]">{{ $a->nomenclatura }}</span> {{ $a->tipo_equipo ?: $a->nombre }}</span><span class="text-xs text-slate-500">{{ $a->ubicacion?->etiqueta }}</span></a>
        @empty
            <p class="px-6 py-5 text-sm text-slate-400">Sin equipos ligados.</p>
        @endforelse
    </div>
</div>
@endsection
