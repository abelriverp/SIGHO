<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
    h1 { font-size: 17px; color: #041b32; margin: 0; }
    h2 { font-size: 11px; color: #041b32; text-transform: uppercase; margin: 14px 0 5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
    .sub { color: #64748b; margin: 2px 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    .kpi td { width: 16.6%; border: 1px solid #e2e8f0; padding: 6px; vertical-align: top; }
    .kpi .l { color: #64748b; font-size: 8px; }
    .kpi .v { font-size: 15px; font-weight: bold; color: #041b32; }
    .lista td { padding: 3px 4px; border-bottom: 1px solid #f1f5f9; }
    .r { text-align: right; font-weight: bold; }
    .col { width: 33%; vertical-align: top; padding-right: 8px; }
    .alerta { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 5px; margin-top: 6px; }
    .pie { position: fixed; bottom: -10px; color: #94a3b8; font-size: 8px; }
</style>
</head>
<body>
@php
    $k = $t['tickets']; $c = $k['calidad'] ?? [];
    $fmt = fn ($v, $suf = '') => $v === null ? '—' : $v . $suf;
@endphp
<h1>Reporte mensual de TI · {{ $hotelNombre }}</h1>
<p class="sub">Periodo: {{ $t['desde']->format('d/m/Y') }} al {{ $t['hasta']->format('d/m/Y') }} · Generado {{ now()->format('d/m/Y H:i') }}{{ $generadoPor ? ' por '.$generadoPor : '' }}</p>

<h2>Soporte</h2>
<table class="kpi"><tr>
    @foreach([['Tickets creados', $k['creados']], ['Resueltos', $k['resueltos']], ['Dentro de SLA ('.$k['sla_horas'].' h)', $fmt($k['slaPorcentaje'], '%')], ['Tiempo medio de solución', $fmt($t['mttr'], ' h')], ['Satisfacción', ($c['csatPromedio'] ?? null) ? $c['csatPromedio'].' / 5' : '—'], ['Pendientes al cierre', $t['backlogTotal']]] as [$l, $v])
        <td><div class="l">{{ $l }}</div><div class="v">{{ $v }}</div></td>
    @endforeach
</tr></table>

<table style="margin-top:8px"><tr>
    <td class="col"><b>Antigüedad de pendientes</b>
        <table class="lista">@foreach(['menos_1_dia' => 'Menos de 1 día', '1_a_3_dias' => '1 a 3 días', '3_a_7_dias' => '3 a 7 días', 'mas_7_dias' => 'Más de 7 días'] as $key => $l)<tr><td>{{ $l }}</td><td class="r">{{ $t['backlog'][$key] }}</td></tr>@endforeach</table>
    </td>
    <td class="col"><b>Tiempo de solución por prioridad</b>
        <table class="lista">@forelse($t['mttrPorPrioridad'] as $p => $h)<tr><td>{{ $p }}</td><td class="r">{{ $h }} h</td></tr>@empty<tr><td>Sin tickets resueltos</td></tr>@endforelse</table>
        <div style="color:#64748b;margin-top:3px">Reabiertos: {{ $fmt($c['reaperturaPct'] ?? null, '%') }} · Encuestas: {{ $c['csatRespuestas'] ?? 0 }}</div>
    </td>
    <td class="col"><b>Equipos con más fallas</b>
        <table class="lista">@forelse(array_slice($k['topActivos'] ?? [], 0, 5) as $a)<tr><td>{{ $a['etiqueta'] }}</td><td class="r">{{ $a['total'] }}</td></tr>@empty<tr><td>—</td></tr>@endforelse</table>
    </td>
</tr></table>

@if(! empty($k['tiempoPorDepartamento']))
<h2>Por departamento</h2>
<table class="lista">
    <tr><td><b>Departamento</b></td><td class="r">Resueltos</td><td class="r">Horas promedio</td></tr>
    @foreach($k['tiempoPorDepartamento'] as $d)
        <tr><td>{{ $d['departamento'] ?? '' }}</td><td class="r">{{ $d['total'] ?? '' }}</td><td class="r">{{ $d['horas_promedio'] ?? '' }}</td></tr>
    @endforeach
</table>
@endif

<h2>Infraestructura</h2>
<table class="kpi"><tr>
    @foreach([['Disponibilidad', $fmt($t['disponibilidad'], '%')], ['Equipos monitoreados', $t['monitoreados']], ['Caídas', $t['caidas']], ['Minutos caído', $t['minutosCaido']], ['Activos registrados', $t['activosTotal']], ['Valor de activos', '$'.number_format($t['valorActivos'], 0)]] as [$l, $v])
        <td><div class="l">{{ $l }}</div><div class="v">{{ $v }}</div></td>
    @endforeach
</tr></table>
@if($t['caidosAhora']->isNotEmpty())<div class="alerta">Caídos al generar el reporte: {{ $t['caidosAhora']->implode(', ') }}</div>@endif

<table style="margin-top:8px"><tr>
    <td class="col"><b>Activos por estado</b>
        <table class="lista">@foreach($t['activosPorEstado'] as $e => $n)<tr><td>{{ $e }}</td><td class="r">{{ $n }}</td></tr>@endforeach<tr><td>Bajas aprobadas en el mes</td><td class="r">{{ $t['bajasPeriodo'] }}</td></tr></table>
    </td>
    <td class="col"><b>Problemas y cambios</b>
        <table class="lista">
            <tr><td>Problemas abiertos</td><td class="r">{{ $t['problemasAbiertos'] }}</td></tr>
            <tr><td>Problemas resueltos</td><td class="r">{{ $t['problemasResueltos'] }}</td></tr>
            <tr><td>Cambios completados</td><td class="r">{{ $t['cambiosCompletados'] }}</td></tr>
            <tr><td>Cambios fallidos</td><td class="r">{{ $t['cambiosFallidos'] }}</td></tr>
        </table>
    </td>
    <td class="col"><b>Costos y vencimientos</b>
        <div style="margin:3px 0">Gasto anual en contratos: <b>${{ number_format($t['gastoAnual'], 0) }} MXN</b></div>
        <table class="lista">@forelse($t['vencimientos'] as $v)<tr><td>{{ $v['tipo'] }}: {{ $v['nombre'] }}</td><td class="r">{{ $v['dias'] }} d</td></tr>@empty<tr><td>Nada vence en 90 días</td></tr>@endforelse</table>
    </td>
</tr></table>

<div class="pie">SIGHO · Departamento de Sistemas</div>
</body>
</html>
