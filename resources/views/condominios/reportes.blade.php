@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 mt-6 mb-10">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('condominios.estancias.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver a estancias
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">Reportes de Condominios</h2>
            <p class="text-sm text-gray-500">Ocupación, check-ins y tipos de visitante.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('condominios.reportes') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6 grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $desde }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">Filtrar</button>
    </form>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-400 uppercase">Unidades totales</p>
            <p class="text-2xl font-bold text-[#041b32]">{{ $totalUnidades }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-400 uppercase">Ocupadas ahora</p>
            <p class="text-2xl font-bold text-green-700">{{ $unidadesOcupadas }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-400 uppercase">% Ocupación</p>
            <p class="text-2xl font-bold text-blue-700">{{ $totalUnidades > 0 ? round($unidadesOcupadas / $totalUnidades * 100) : 0 }}%</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-400 uppercase">Promedio de noches</p>
            <p class="text-2xl font-bold text-[#041b32]">{{ $promedioNoches }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Check-ins por mes</h3>
            @if($checkinsPorMes->isEmpty())
                <p class="text-center text-gray-400 text-sm py-10">Sin check-ins en el rango seleccionado.</p>
            @else
                <canvas id="chartCheckins" height="260"></canvas>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Por tipo de visitante</h3>
            @if($porTipoVisitante->isEmpty())
                <p class="text-center text-gray-400 text-sm py-10">Sin datos.</p>
            @else
                <canvas id="chartTipoVisitante" height="260"></canvas>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-500 uppercase">Top unidades con más actividad</h3>
        </div>
        @if($topUnidades->isEmpty())
            <div class="py-10 text-center text-gray-400 text-sm">Sin estancias en el rango seleccionado.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                        <th class="py-2 px-4 text-left">Unidad</th>
                        <th class="py-2 px-4 text-right">Estancias registradas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($topUnidades as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 font-medium text-gray-700">{{ $u->torre ? $u->torre . ' - ' : '' }}{{ $u->identificador }}</td>
                        <td class="py-2 px-4 text-right font-bold text-[#041b32]">{{ $u->total_estancias }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@if($checkinsPorMes->isNotEmpty() || $porTipoVisitante->isNotEmpty())
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
    @if($checkinsPorMes->isNotEmpty())
    new Chart(document.getElementById('chartCheckins'), {
        type: 'line',
        data: {
            labels: {!! json_encode($checkinsPorMes->pluck('mes')) !!},
            datasets: [{
                label: 'Check-ins',
                data: {!! json_encode($checkinsPorMes->pluck('total')) !!},
                borderColor: '#041b32',
                backgroundColor: 'rgba(4,27,50,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    @endif

    @if($porTipoVisitante->isNotEmpty())
    const tipoLabels = {!! json_encode($porTipoVisitante->map(fn($r) => match($r->tipo_visitante) {
        'propietario'     => 'Propietario',
        'familiar'        => 'Familiar',
        'inquilino_renta' => 'Inquilino / Renta',
        'invitado'        => 'Invitado',
        'servicio'        => 'Servicio',
        default           => $r->tipo_visitante,
    })) !!};
    new Chart(document.getElementById('chartTipoVisitante'), {
        type: 'doughnut',
        data: {
            labels: tipoLabels,
            datasets: [{
                data: {!! json_encode($porTipoVisitante->pluck('total')) !!},
                backgroundColor: ['#041b32', '#2563eb', '#f59e0b', '#16a34a', '#94a3b8']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    @endif
</script>
@endif
@endsection
