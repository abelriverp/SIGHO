@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 mt-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver al inventario
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">Reportes de Suministros</h2>
            <p class="text-sm text-gray-500">Productos más usados y desglose de salidas por tipo.</p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('inventory.reportes') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6 grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $desde }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Área</label>
            <select name="area" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                <option value="">Todas las áreas</option>
                @foreach($areas as $a)
                    <option value="{{ $a }}" {{ $filtroArea == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90">
                Filtrar
            </button>
        </div>
    </form>

    @if($topProductos->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-10 text-center text-gray-400">
            No hay salidas registradas en el rango seleccionado.
        </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Gráfica de barras: top productos --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Top 10 productos más usados (por cantidad de salida)</h3>
            <canvas id="chartTopProductos" height="260"></canvas>
        </div>

        {{-- Gráfica de dona: por tipo de salida --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Salidas por origen</h3>
            <canvas id="chartTipoSalida" height="260"></canvas>
        </div>
    </div>

    {{-- Tabla detalle --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-500 uppercase">Detalle</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                        <th class="py-2 px-4 text-left">Producto</th>
                        <th class="py-2 px-4 text-left">Área</th>
                        <th class="py-2 px-4 text-right">Total salidas</th>
                        <th class="py-2 px-4 text-right">Veces solicitado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($topProductos as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 font-medium text-gray-700">{{ $p->nombre }}</td>
                        <td class="py-2 px-4 text-gray-500">{{ $p->area ?? '—' }}</td>
                        <td class="py-2 px-4 text-right font-bold text-[#041b32]">{{ $p->total_salidas }} {{ $p->unidad }}</td>
                        <td class="py-2 px-4 text-right text-gray-500">{{ $p->veces_solicitado }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if($topProductos->isNotEmpty())
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
    const topProductosData = {
        labels: {!! json_encode($topProductos->pluck('nombre')) !!},
        totales: {!! json_encode($topProductos->pluck('total_salidas')) !!}
    };

    new Chart(document.getElementById('chartTopProductos'), {
        type: 'bar',
        data: {
            labels: topProductosData.labels,
            datasets: [{
                label: 'Cantidad de salida',
                data: topProductosData.totales,
                backgroundColor: '#041b32'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    const tipoSalidaLabels = {!! json_encode($porTipoSalida->map(fn($r) => match($r->tipo_salida) {
        'solicitud_huesped' => 'Solicitud de huésped',
        'consumo_area'      => 'Consumo de área',
        'ajuste'            => 'Ajuste / merma',
        default             => 'Sin clasificar',
    })) !!};
    const tipoSalidaData = {!! json_encode($porTipoSalida->pluck('total')) !!};

    new Chart(document.getElementById('chartTipoSalida'), {
        type: 'doughnut',
        data: {
            labels: tipoSalidaLabels,
            datasets: [{
                data: tipoSalidaData,
                backgroundColor: ['#dc2626', '#2563eb', '#f59e0b', '#94a3b8']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endif
@endsection
