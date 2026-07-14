@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 mt-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('tickets.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver a Tickets
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">Reportes de Tickets</h2>
            <p class="text-sm text-gray-500">
                Corte de KPIs para un rango de fechas —
                @if(auth()->user()->puedeVerTodos())
                    toda la propiedad ({{ $hotelActivo }})
                @else
                    tu departamento: {{ auth()->user()->departamento ?? 'sin asignar' }}
                @endif
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('tickets.reportes') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $metricas['desde'] }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $metricas['hasta'] }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <div>
            <button type="submit" class="w-full bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                Filtrar
            </button>
        </div>
        <div>
            <a href="{{ route('tickets.reportes.pdf', request()->only(['desde', 'hasta'])) }}" class="w-full inline-flex items-center justify-center gap-2 bg-white border border-[#041b32] text-[#041b32] font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-50">
                ⬇ Descargar PDF
            </a>
        </div>
    </form>

    {{-- Tarjetas de KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="relative bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <x-info-tip>Tickets levantados dentro del rango seleccionado, contados por su <strong>fecha de creación</strong> — sin importar si ya se resolvieron o siguen abiertos.</x-info-tip>
            <p class="text-xs font-bold text-gray-400 uppercase">Tickets Creados</p>
            <p class="text-2xl font-bold text-[#041b32] mt-1">{{ $metricas['creados'] }}</p>
            <p class="text-xs text-gray-400 mt-1">levantados en el rango</p>
        </div>

        <div class="relative bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <x-info-tip>Tickets marcados como resueltos dentro del rango, contados por su <strong>fecha de resolución</strong> — no importa cuándo se hayan creado. Puede incluir tickets levantados antes del rango, por eso este número no tiene que coincidir con "Tickets Creados".</x-info-tip>
            <p class="text-xs font-bold text-gray-400 uppercase">Tickets Resueltos</p>
            <p class="text-2xl font-bold text-[#041b32] mt-1">{{ $metricas['resueltos'] }}</p>
            <p class="text-xs text-gray-400 mt-1">cerrados en el rango</p>
        </div>

        @php
            $semaforoEstilos = [
                'verde'    => ['text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
                'amarillo' => ['text' => 'text-amber-600',   'bg' => 'bg-amber-50',   'border' => 'border-amber-200'],
                'rojo'     => ['text' => 'text-red-600',     'bg' => 'bg-red-50',     'border' => 'border-red-200'],
            ];
            $slaColor = $metricas['slaPorcentaje'] === null
                ? ['text' => 'text-gray-400', 'bg' => 'bg-white', 'border' => 'border-gray-100']
                : $semaforoEstilos[$metricas['slaPorcentaje'] >= 80 ? 'verde' : ($metricas['slaPorcentaje'] >= 60 ? 'amarillo' : 'rojo')];
        @endphp
        <div class="relative rounded-lg shadow-sm border p-4 {{ $slaColor['bg'] }} {{ $slaColor['border'] }}">
            <x-info-tip>De los <strong>Tickets Resueltos</strong> en el rango, qué porcentaje se cerró dentro de las {{ $metricas['sla_horas'] }} horas desde que se creó. Si no hay tickets resueltos en el rango, se muestra "—" (no hay nada que evaluar todavía).</x-info-tip>
            <p class="text-xs font-bold text-gray-400 uppercase">% Dentro de SLA ({{ $metricas['sla_horas'] }} hrs)</p>
            <p class="text-2xl font-bold mt-1 {{ $slaColor['text'] }}">
                {{ $metricas['slaPorcentaje'] !== null ? $metricas['slaPorcentaje'] . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                @if($metricas['resueltos'] > 0)
                    {{ $metricas['slaCumplidos'] }} de {{ $metricas['resueltos'] }} a tiempo
                @else
                    sin tickets resueltos en el rango
                @endif
            </p>
        </div>

        <div class="relative bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <x-info-tip>El periodo de fechas que se está consultando en este corte. Cámbialo con el filtro de arriba ("Desde" / "Hasta").</x-info-tip>
            <p class="text-xs font-bold text-gray-400 uppercase">Rango</p>
            <p class="text-sm font-bold text-[#041b32] mt-1">{{ \Illuminate\Support\Carbon::parse($metricas['desde'])->format('d/m/Y') }}</p>
            <p class="text-xs text-gray-400">al {{ \Illuminate\Support\Carbon::parse($metricas['hasta'])->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Tickets por estado --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                <h3 class="text-xs font-bold text-gray-500 uppercase">Tickets por Estado (levantados en el rango)</h3>
                <p class="text-[11px] text-gray-400 normal-case mt-0.5">Por fecha de creación, agrupado por su estado actual.</p>
            </div>
            @if(empty($metricas['ticketsPorEstado']))
                <div class="p-6 text-center text-sm text-gray-400">Sin tickets en el rango seleccionado.</div>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($metricas['ticketsPorEstado'] as $estado => $total)
                            <tr>
                                <td class="py-2 px-5 text-gray-700">{{ $estado }}</td>
                                <td class="py-2 px-5 text-right font-bold text-[#041b32]">{{ $total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Tiempo promedio por departamento --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                <h3 class="text-xs font-bold text-gray-500 uppercase">Tiempo Promedio de Resolución por Departamento</h3>
                <p class="text-[11px] text-gray-400 normal-case mt-0.5">Por fecha de resolución (no de creación) — puede incluir tickets levantados antes del rango.</p>
            </div>
            @if(empty($metricas['tiempoPorDepartamento']))
                <div class="p-6 text-center text-sm text-gray-400">Sin tickets resueltos en el rango seleccionado.</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 px-5 text-left">Departamento</th>
                            <th class="py-2 px-5 text-right">Resueltos</th>
                            <th class="py-2 px-5 text-right">Horas prom.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($metricas['tiempoPorDepartamento'] as $fila)
                            <tr>
                                <td class="py-2 px-5 text-gray-700">{{ $fila['departamento'] }}</td>
                                <td class="py-2 px-5 text-right text-gray-500">{{ $fila['total'] }}</td>
                                <td class="py-2 px-5 text-right font-bold text-[#041b32]">{{ $fila['horas_promedio'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Ranking de técnicos (solo admin/gerente) --}}
    @if(count($metricas['rankingTecnicos']) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                <h3 class="text-xs font-bold text-gray-500 uppercase">Ranking de Técnicos</h3>
                <p class="text-[11px] text-gray-400 normal-case mt-0.5">Mismo criterio que "Tiempo por Departamento", pero solo cuenta tickets con técnico responsable registrado — por eso el total puede ser menor.</p>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                        <th class="py-2 px-5 text-left">Técnico</th>
                        <th class="py-2 px-5 text-right">Resueltos</th>
                        <th class="py-2 px-5 text-right">Horas prom.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($metricas['rankingTecnicos'] as $fila)
                        <tr>
                            <td class="py-2 px-5 text-gray-700">{{ $fila['nombre'] }}</td>
                            <td class="py-2 px-5 text-right text-gray-500">{{ $fila['total'] }}</td>
                            <td class="py-2 px-5 text-right font-bold text-[#041b32]">{{ $fila['horas_promedio'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
