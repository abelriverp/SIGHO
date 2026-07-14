<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; }
        .header { background: #041b32; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
        .label { font-weight: bold; color: #666; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 14px; margin-top: 2px; }
        .kpi-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; background: #f8fafc; }
        .kpi-numero { font-size: 20px; font-weight: bold; color: #041b32; }
        .section-title { font-size: 12px; font-weight: bold; color: #041b32; border-bottom: 2px solid #041b32; padding-bottom: 4px; margin: 20px 0 10px; text-transform: uppercase; }
        table.datos { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.datos th { text-align: left; background: #f1f5f9; color: #64748b; text-transform: uppercase; font-size: 9px; padding: 6px 8px; }
        table.datos td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
        .footer-nota { margin-top: 25px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">HOTEL EMPORIO MAZATLÁN</h2>
        <p style="margin:5px 0 0 0;">Corte de Tickets — {{ \Illuminate\Support\Carbon::parse($metricas['desde'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($metricas['hasta'])->format('d/m/Y') }}</p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">Propiedad</div>
                <div class="value">{{ $hotelActivo }}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="label">Alcance</div>
                <div class="value">
                    @if($user->puedeVerTodos())
                        Toda la propiedad
                    @else
                        Departamento: {{ $user->departamento ?? 'sin asignar' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- KPIs principales --}}
    <table width="100%" cellpadding="6" cellspacing="0" style="margin-top: 10px;">
        <tr>
            <td width="33%">
                <div class="kpi-box">
                    <div class="label">Tickets Creados</div>
                    <div class="kpi-numero">{{ $metricas['creados'] }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="kpi-box">
                    <div class="label">Tickets Resueltos</div>
                    <div class="kpi-numero">{{ $metricas['resueltos'] }}</div>
                </div>
            </td>
            <td width="34%">
                <div class="kpi-box">
                    <div class="label">% Dentro de SLA ({{ $metricas['sla_horas'] }} hrs)</div>
                    <div class="kpi-numero">{{ $metricas['slaPorcentaje'] !== null ? $metricas['slaPorcentaje'] . '%' : '—' }}</div>
                    <div style="font-size: 9px; color: #94a3b8;">
                        @if($metricas['resueltos'] > 0)
                            {{ $metricas['slaCumplidos'] }} de {{ $metricas['resueltos'] }} a tiempo
                        @else
                            sin resueltos en el rango
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tickets por estado --}}
    <div class="section-title">Tickets por Estado (levantados en el rango)</div>
    @if(empty($metricas['ticketsPorEstado']))
        <p style="font-size: 11px; color: #94a3b8;">Sin tickets en el rango seleccionado.</p>
    @else
        <table class="datos">
            <thead>
                <tr><th>Estado</th><th style="text-align:right;">Total</th></tr>
            </thead>
            <tbody>
                @foreach($metricas['ticketsPorEstado'] as $estado => $total)
                    <tr>
                        <td>{{ $estado }}</td>
                        <td style="text-align:right; font-weight:bold; color:#041b32;">{{ $total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Tiempo por departamento --}}
    <div class="section-title">Tiempo Promedio de Resolución por Departamento</div>
    @if(empty($metricas['tiempoPorDepartamento']))
        <p style="font-size: 11px; color: #94a3b8;">Sin tickets resueltos en el rango seleccionado.</p>
    @else
        <table class="datos">
            <thead>
                <tr><th>Departamento</th><th style="text-align:right;">Resueltos</th><th style="text-align:right;">Horas promedio</th></tr>
            </thead>
            <tbody>
                @foreach($metricas['tiempoPorDepartamento'] as $fila)
                    <tr>
                        <td>{{ $fila['departamento'] }}</td>
                        <td style="text-align:right;">{{ $fila['total'] }}</td>
                        <td style="text-align:right; font-weight:bold; color:#041b32;">{{ $fila['horas_promedio'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Ranking de técnicos --}}
    @if(count($metricas['rankingTecnicos']) > 0)
        <div class="section-title">Ranking de Técnicos</div>
        <table class="datos">
            <thead>
                <tr><th>Técnico</th><th style="text-align:right;">Resueltos</th><th style="text-align:right;">Horas promedio</th></tr>
            </thead>
            <tbody>
                @foreach($metricas['rankingTecnicos'] as $fila)
                    <tr>
                        <td>{{ $fila['nombre'] }}</td>
                        <td style="text-align:right;">{{ $fila['total'] }}</td>
                        <td style="text-align:right; font-weight:bold; color:#041b32;">{{ $fila['horas_promedio'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-nota">
        Documento generado por SIGHO - Sistema Integral de Gestión de Hospitalidad y Operaciones el {{ now()->format('d/m/Y \a \l\a\s H:i') }} hrs.
    </div>
</body>
</html>
