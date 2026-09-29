<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; }
        .header { background: #041b32; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
        .label { font-weight: bold; color: #666; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 14px; margin-top: 2px; }
        .kpi-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; background: #f8fafc; }
        .kpi-numero { font-size: 18px; font-weight: bold; color: #041b32; }
        .section-title { font-size: 12px; font-weight: bold; color: #041b32; border-bottom: 2px solid #041b32; padding-bottom: 4px; margin: 18px 0 10px; text-transform: uppercase; }
        table.datos { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        table.datos th { text-align: left; background: #f1f5f9; color: #64748b; text-transform: uppercase; font-size: 8.5px; padding: 6px 8px; }
        table.datos td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
        .badge { display: inline-block; font-size: 8px; font-weight: bold; padding: 2px 5px; border-radius: 3px; text-transform: uppercase; }
        .badge-ok { background: #dcfce7; color: #15803d; }
        .badge-typo { background: #fef9c3; color: #854d0e; }
        .badge-generico { background: #dbeafe; color: #1d4ed8; }
        .badge-malo { background: #fee2e2; color: #b91c1c; }
        .footer-nota { margin-top: 20px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">{{ strtoupper($propiedad) }}</h2>
        <p style="margin:5px 0 0 0;">Validador de Emails — {{ $validacion->archivo_nombre }}</p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 34%; vertical-align: top;">
                <div class="label">Analizado</div>
                <div class="value">{{ $validacion->created_at->format('d/m/Y H:i') }}</div>
            </td>
            <td style="width: 33%; vertical-align: top;">
                <div class="label">Subido por</div>
                <div class="value">{{ $validacion->usuario->name ?? 'usuario eliminado' }}</div>
            </td>
            <td style="width: 33%; vertical-align: top;">
                <div class="label">Filtro aplicado</div>
                <div class="value">
                    @if(($filtros['estado'] ?? '') !== '' || ($filtros['buscar'] ?? '') !== '')
                        {{ \App\Models\EmailValidacionItem::GRUPOS_FILTRO[$filtros['estado'] ?? '']['label'] ?? 'Todos' }}
                        @if(($filtros['buscar'] ?? '') !== '')
                            · "{{ $filtros['buscar'] }}"
                        @endif
                    @else
                        Ninguno — se muestran todos los correos
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- KPIs --}}
    <table width="100%" cellpadding="4" cellspacing="0" style="margin-top: 8px;">
        <tr>
            <td width="20%"><div class="kpi-box"><div class="label">Total</div><div class="kpi-numero">{{ $validacion->total }}</div></div></td>
            <td width="20%"><div class="kpi-box"><div class="label">Correctos</div><div class="kpi-numero" style="color:#15803d;">{{ $validacion->ok }}</div></div></td>
            <td width="20%"><div class="kpi-box"><div class="label">Errores tipográficos</div><div class="kpi-numero" style="color:#854d0e;">{{ $validacion->typos }}</div></div></td>
            <td width="20%"><div class="kpi-box"><div class="label">Genéricos (OTA)</div><div class="kpi-numero" style="color:#1d4ed8;">{{ $validacion->genericos }}</div></div></td>
            <td width="20%"><div class="kpi-box"><div class="label">Falsos/Inválidos</div><div class="kpi-numero" style="color:#b91c1c;">{{ $validacion->falsos }}</div></div></td>
        </tr>
    </table>

    <div class="section-title">Detalle de Correos ({{ $items->count() }} {{ $items->count() === 1 ? 'resultado' : 'resultados' }})</div>

    @if($items->isEmpty())
        <p style="font-size: 11px; color: #94a3b8;">Ningún correo coincide con el filtro aplicado.</p>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th style="width:38%;">Correo electrónico</th>
                    <th style="width:18%;">Estado</th>
                    <th style="width:14%; text-align:right;">Puntaje</th>
                    <th style="width:30%;">Sugerencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td style="font-family: monospace;">{{ $item->email }}</td>
                    <td>
                        @php
                            $claseBadge = match($item->estado) {
                                'OK' => 'badge-ok',
                                'ERROR TIPOGRAFICO' => 'badge-typo',
                                'GENERICO' => 'badge-generico',
                                default => 'badge-malo',
                            };
                        @endphp
                        <span class="badge {{ $claseBadge }}">{{ $item->estadoLabel() }}</span>
                    </td>
                    <td style="text-align:right;">{{ $item->score }}</td>
                    <td>{{ $item->sugerencia ?? '—' }}</td>
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
