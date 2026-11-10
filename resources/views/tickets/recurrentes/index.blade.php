@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Mantenimiento preventivo</h2>
            <p class="text-gray-500 text-sm mt-1">Cada plantilla genera un ticket automáticamente en su fecha (todos los días a las 6:00 am).</p>
        </div>
        <a href="{{ route('tickets.recurrentes.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Programar mantenimiento</a>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="hotel-blue text-white uppercase text-xs">
                    <th class="py-3 px-4">Mantenimiento</th>
                    <th class="py-3 px-4">Departamento</th>
                    <th class="py-3 px-4">Equipo / lugar</th>
                    <th class="py-3 px-4">Frecuencia</th>
                    <th class="py-3 px-4">Próximo</th>
                    <th class="py-3 px-4">Último ticket</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600">
                @forelse($recurrentes as $r)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $r->activo ? '' : 'opacity-50' }}">
                        <td class="py-3 px-4"><a href="{{ route('tickets.recurrentes.edit', $r) }}" class="font-bold text-[#041b32] hover:underline">{{ $r->titulo }}</a>{{ $r->activo ? '' : ' (pausado)' }}</td>
                        <td class="py-3 px-4 text-xs">{{ $r->departamento_destino }}</td>
                        <td class="py-3 px-4 text-xs">{{ $r->asset?->nomenclatura }}{{ $r->asset && $r->ubicacion ? ' · ' : '' }}{{ $r->ubicacion?->etiqueta }}</td>
                        <td class="py-3 px-4 text-xs">{{ $frecuencias[$r->frecuencia] ?? $r->frecuencia }}</td>
                        <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $r->proxima_fecha->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-xs">
                            @if($r->ultimoTicket)<a href="{{ route('tickets.show', $r->ultimo_ticket_id) }}" class="underline">#{{ $r->ultimo_ticket_id }}</a> {{ $r->ultimoTicket->status }}@else — @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-gray-400">Sin mantenimientos programados. Ejemplos: limpieza de equipos de cómputo (trimestral), revisión de UPS (mensual), prueba de respaldos (semanal).</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
