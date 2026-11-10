@extends('layouts.app')

@section('content')
@php
    $badge = ['vigente' => 'bg-green-100 text-green-800', 'por_vencer' => 'bg-orange-100 text-orange-800', 'vencido' => 'bg-red-100 text-red-800'];
    $etq = ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido'];
@endphp
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $tipo ? ($tipos[$tipo] ?? 'Contratos') : 'Contratos' }}</h2>
            <p class="text-gray-500 text-sm mt-1">Soporte, mantenimiento, enlaces, arrendamientos y pólizas · Gasto anual estimado (activos, MXN): <strong>${{ number_format($costoAnual, 2) }}</strong></p>
        </div>
        @if(auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos())
            <a href="{{ route('contratos.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nuevo contrato</a>
        @endif
    </div>
    <div class="flex gap-2 mb-4">
        @foreach(['' => 'Todos', 'por_vencer' => 'Vencen en 90 días', 'vencidos' => 'Vencidos'] as $k => $v)
            <a href="{{ route('contratos.index', $k ? ['vigencia' => $k] : []) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ ($filtro ?? '') === $k ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $v }}</a>
        @endforeach
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">Contrato</th><th class="py-3 px-4">Tipo</th><th class="py-3 px-4">Proveedor</th>
                <th class="py-3 px-4 text-right">Costo</th><th class="py-3 px-4">Vence</th><th class="py-3 px-4 text-center">Estado</th><th class="py-3 px-4 text-center">Activos</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($contratos as $c)
                    @php $e = $c->estadoVigencia(); @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $c->activo ? '' : 'opacity-50' }}">
                        <td class="py-3 px-4"><a href="{{ route('contratos.show', $c) }}" class="font-bold text-[#041b32] hover:underline">{{ $c->nombre }}</a><div class="text-xs text-slate-400">{{ $c->numero }}{{ $c->ancho_banda ? ' · '.$c->ancho_banda : '' }}</div></td>
                        <td class="py-3 px-4 text-xs">{{ $tipos[$c->tipo] ?? $c->tipo }}</td>
                        <td class="py-3 px-4 text-xs">{{ $c->proveedor?->nombre }}</td>
                        <td class="py-3 px-4 text-right text-xs whitespace-nowrap">{{ $c->costo !== null ? '$'.number_format($c->costo, 2).' '.$c->moneda : '' }}<div class="text-slate-400">{{ $c->periodicidad_pago }}</div></td>
                        <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $c->fecha_fin?->format('d/m/Y') ?? 'Indefinido' }}@if($c->diasRestantes() !== null && $c->diasRestantes() >= 0)<div class="text-slate-400">en {{ $c->diasRestantes() }} días</div>@endif</td>
                        <td class="py-3 px-4 text-center">@if($e)<span class="py-1 px-2 rounded-full text-xs font-bold {{ $badge[$e] }}">{{ $etq[$e] }}</span>@endif</td>
                        <td class="py-3 px-4 text-center">{{ $c->activos_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-gray-400">Sin contratos. Ejemplos: enlace de internet (Single Digits), póliza de soporte del BMS (Rowan), soporte Saflok (dormakaba), ENSEO.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
