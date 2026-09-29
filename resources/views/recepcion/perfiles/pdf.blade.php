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
        table.datos { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        table.datos th { text-align: left; background: #f1f5f9; color: #64748b; text-transform: uppercase; font-size: 8px; padding: 5px 6px; }
        table.datos td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .badge { display: inline-block; font-size: 8px; font-weight: bold; padding: 2px 5px; border-radius: 3px; text-transform: uppercase; }
        .badge-ok { background: #dcfce7; color: #15803d; }
        .badge-sin-correo { background: #fef3c7; color: #92400e; }
        .badge-sufijo { background: #fef9c3; color: #854d0e; }
        .badge-dominio { background: #fee2e2; color: #b91c1c; }
        .badge-rechazado { background: #fecaca; color: #7f1d1d; }
        .footer-nota { margin-top: 20px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">{{ strtoupper($propiedad) }}</h2>
        <p style="margin:5px 0 0 0;">Correos de Llegada — {{ $validacion->archivo_nombre }}</p>
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
                        {{ \App\Models\PerfilCorreoValidacionItem::GRUPOS_FILTRO[$filtros['estado'] ?? '']['label'] ?? 'Todos' }}
                        @if(($filtros['buscar'] ?? '') !== '')
                            · "{{ $filtros['buscar'] }}"
                        @endif
                    @else
                        Ninguno — se muestran todas las reservas
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- KPIs --}}
    <table width="100%" cellpadding="4" cellspacing="0" style="margin-top: 8px;">
        <tr>
            <td width="16%"><div class="kpi-box"><div class="label">Total</div><div class="kpi-numero">{{ $validacion->total }}</div></div></td>
            <td width="17%"><div class="kpi-box"><div class="label">OK</div><div class="kpi-numero" style="color:#15803d;">{{ $validacion->ok }}</div></div></td>
            <td width="17%"><div class="kpi-box"><div class="label">Sin correo</div><div class="kpi-numero" style="color:#92400e;">{{ $validacion->sin_correo }}</div></div></td>
            <td width="17%"><div class="kpi-box"><div class="label">Sufijo inválido</div><div class="kpi-numero" style="color:#854d0e;">{{ $validacion->sufijo_invalido }}</div></div></td>
            <td width="17%"><div class="kpi-box"><div class="label">Dominio/formato</div><div class="kpi-numero" style="color:#b91c1c;">{{ $validacion->dominio_invalido }}</div></div></td>
            <td width="16%"><div class="kpi-box"><div class="label">Rechazados</div><div class="kpi-numero" style="color:#7f1d1d;">{{ $validacion->rechazado_smtp }}</div></div></td>
        </tr>
    </table>

    <div class="section-title">Detalle por Reserva ({{ $items->count() }} {{ $items->count() === 1 ? 'resultado' : 'resultados' }})</div>

    @if($items->isEmpty())
        <p style="font-size: 11px; color: #94a3b8;">Ninguna reserva coincide con el filtro aplicado.</p>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th style="width:8%;"># Reserva</th>
                    <th style="width:16%;">Huésped</th>
                    <th style="width:7%;">Llegada</th>
                    <th style="width:7%;">Salida</th>
                    <th style="width:17%;">Correo</th>
                    <th style="width:9%;">Estado</th>
                    <th style="width:27%;">Motivo</th>
                    <th style="width:9%;">Verificación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->numero_reserva }}</td>
                    <td>{{ $item->huesped }}</td>
                    <td>{{ $item->fecha_llegada ?? '—' }}</td>
                    <td>{{ $item->fecha_salida ?? '—' }}</td>
                    <td style="font-family: monospace;">{{ $item->email ?? '—' }}</td>
                    <td>
                        @php
                            $claseBadge = match($item->estado) {
                                'OK' => 'badge-ok',
                                'SIN_CORREO' => 'badge-sin-correo',
                                'SUFIJO_INVALIDO' => 'badge-sufijo',
                                'RECHAZADO_SMTP' => 'badge-rechazado',
                                default => 'badge-dominio',
                            };
                        @endphp
                        <span class="badge {{ $claseBadge }}">{{ $item->estadoLabel() }}</span>
                    </td>
                    <td>
                        {{ $item->motivo ?? '—' }}
                        @if($item->sugerencia)
                            <br><span style="color:#94a3b8;">Sugerencia: {{ $item->sugerencia }}</span>
                        @endif
                    </td>
                    <td>{{ $item->verificacionSmtpLabel() }}</td>
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
