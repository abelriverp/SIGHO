@extends('layouts.app')

@section('content')
@php
    $k = $t['tickets'];
    $c = $k['calidad'] ?? [];
    $fmt = fn ($v, $suf = '') => $v === null ? '—' : $v . $suf;
    $tarjeta = 'bg-white rounded-lg border border-slate-200 p-4';
@endphp
<div class="max-w-6xl mx-auto mb-10">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Tablero de dirección</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $hotelNombre }} · del {{ $t['desde']->format('d/m/Y') }} al {{ $t['hasta']->format('d/m/Y') }}. El 1 de cada mes se envía por correo el PDF del mes anterior.</p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <input type="month" name="mes" value="{{ $mes }}" max="{{ now()->format('Y-m') }}" class="border border-slate-300 rounded-md py-1.5 px-2 text-sm" onchange="this.form.submit()">
            <a href="{{ route('direccion.pdf', ['mes' => $mes]) }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">Descargar PDF</a>
        </form>
    </div>

    <h3 class="text-xs font-bold uppercase text-slate-400 mb-2">Soporte</h3>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['Tickets creados', $k['creados']], ['Resueltos', $k['resueltos']],
            ['Dentro de SLA', $fmt($k['slaPorcentaje'], '%')], ['Tiempo medio de solución', $fmt($t['mttr'], ' h')],
            ['Satisfacción', $c['csatPromedio'] ?? null ? $c['csatPromedio'].' / 5' : '—'], ['Pendientes hoy', $t['backlogTotal']],
        ] as [$l, $v])
            <div class="{{ $tarjeta }}"><p class="text-xs text-slate-500">{{ $l }}</p><p class="text-2xl font-bold text-[#041b32]">{{ $v }}</p></div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Antigüedad de pendientes</p>
            @foreach(['menos_1_dia' => 'Menos de 1 día', '1_a_3_dias' => '1 a 3 días', '3_a_7_dias' => '3 a 7 días', 'mas_7_dias' => 'Más de 7 días'] as $key => $l)
                <div class="flex justify-between text-sm py-1 border-b border-slate-50"><span>{{ $l }}</span><strong class="{{ $key === 'mas_7_dias' && $t['backlog'][$key] ? 'text-red-600' : '' }}">{{ $t['backlog'][$key] }}</strong></div>
            @endforeach
        </div>
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Tiempo de solución por prioridad</p>
            @forelse($t['mttrPorPrioridad'] as $p => $h)
                <div class="flex justify-between text-sm py-1 border-b border-slate-50"><span>{{ $p }}</span><strong>{{ $h }} h</strong></div>
            @empty
                <p class="text-sm text-slate-400">Sin tickets resueltos.</p>
            @endforelse
            <p class="text-xs text-slate-400 mt-2">Primera respuesta: <strong>{{ \App\Services\DashboardMetricsService::duracion($c['primeraRespuestaMin'] ?? null) }}</strong> promedio · Reabiertos: {{ $fmt($c['reaperturaPct'] ?? null, '%') }} · Encuestas: {{ $c['csatRespuestas'] ?? 0 }}</p>
        </div>
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Equipos con más fallas</p>
            @forelse(array_slice($k['topActivos'] ?? [], 0, 5) as $a)
                <div class="flex justify-between text-sm py-1 border-b border-slate-50"><span class="truncate">{{ $a['etiqueta'] }}</span><strong>{{ $a['total'] }}</strong></div>
            @empty
                <p class="text-sm text-slate-400">Sin tickets ligados a equipos.</p>
            @endforelse
        </div>
    </div>

    <h3 class="text-xs font-bold uppercase text-slate-400 mb-2">Infraestructura</h3>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['Disponibilidad', $fmt($t['disponibilidad'], '%')], ['Equipos monitoreados', $t['monitoreados']],
            ['Caídas en el mes', $t['caidas']], ['Minutos caído', $t['minutosCaido']],
            ['Activos registrados', $t['activosTotal']], ['Valor de activos', '$'.number_format($t['valorActivos'], 0)],
        ] as [$l, $v])
            <div class="{{ $tarjeta }}"><p class="text-xs text-slate-500">{{ $l }}</p><p class="text-2xl font-bold text-[#041b32]">{{ $v }}</p></div>
        @endforeach
    </div>
    @if($t['caidosAhora']->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-6 text-sm text-red-800"><strong>Caídos ahora:</strong> {{ $t['caidosAhora']->implode(', ') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Activos por estado</p>
            @foreach($t['activosPorEstado'] as $e => $n)
                <div class="flex justify-between text-sm py-1 border-b border-slate-50"><span>{{ $e }}</span><strong>{{ $n }}</strong></div>
            @endforeach
            <p class="text-xs text-slate-400 mt-2">Bajas aprobadas en el mes: {{ $t['bajasPeriodo'] }}</p>
        </div>
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Problemas y cambios</p>
            @foreach([['Problemas abiertos', $t['problemasAbiertos']], ['Problemas resueltos en el mes', $t['problemasResueltos']], ['Cambios completados', $t['cambiosCompletados']], ['Cambios fallidos', $t['cambiosFallidos']]] as [$l, $v])
                <div class="flex justify-between text-sm py-1 border-b border-slate-50"><span>{{ $l }}</span><strong>{{ $v }}</strong></div>
            @endforeach
            @if($t['cambiosProximos']->isNotEmpty())
                <p class="text-xs font-bold text-slate-500 mt-3 mb-1">Próximas ventanas</p>
                @foreach($t['cambiosProximos'] as $cp)
                    <a href="{{ route('cambios.show', $cp->id) }}" class="block text-xs text-[#041b32] hover:underline">{{ $cp->ventana_inicio->format('d/m H:i') }} · {{ $cp->titulo }} ({{ $cp->riesgo }})</a>
                @endforeach
            @endif
        </div>
        <div class="{{ $tarjeta }}">
            <p class="font-bold text-slate-700 mb-2">Costos y vencimientos</p>
            <p class="text-sm">Gasto anual en contratos (MXN): <strong>${{ number_format($t['gastoAnual'], 0) }}</strong></p>
            <p class="text-sm mt-1">Costo de TI del mes: <strong>${{ number_format($t['costoTiMes'], 0) }}</strong></p>
            <p class="text-sm mt-1">Por cuarto-noche ocupado: <strong class="text-[#041b32]">{{ $t['costoPorCuartoNoche'] !== null ? '$'.number_format($t['costoPorCuartoNoche'], 2) : '—' }}</strong></p>
            <form method="POST" action="{{ route('direccion.ocupacion') }}" class="flex gap-2 items-center mt-2">
                @csrf
                <input type="hidden" name="mes" value="{{ $mes }}">
                <input type="number" name="cuartos_noche" min="1" required value="{{ $t['cuartosNoche'] }}" placeholder="Cuartos-noche del mes" class="flex-1 min-w-0 border border-slate-300 rounded text-xs p-1.5">
                <button class="bg-[#041b32] text-white text-xs font-bold px-3 py-1.5 rounded">Guardar</button>
            </form>
            <p class="text-[10px] text-slate-400 mt-1">Cuartos-noche ocupados del mes, del reporte de ocupación de Opera. Costo = contratos (llevados a mes) + licencias repartidas en su vigencia.</p>
            <p class="text-xs font-bold text-slate-500 mt-3 mb-1">Vencen en 90 días</p>
            @forelse($t['vencimientos'] as $v)
                <div class="flex justify-between text-xs py-0.5"><span class="truncate">{{ $v['tipo'] }}: {{ $v['nombre'] }}</span><span class="{{ $v['dias'] <= 15 ? 'text-red-600 font-bold' : '' }}">{{ $v['dias'] }} d</span></div>
            @empty
                <p class="text-xs text-slate-400">Nada por vencer.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
