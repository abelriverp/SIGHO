<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; }
        .header { background: #041b32; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
        .info-section { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .label { font-weight: bold; color: #666; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 14px; margin-top: 2px; }
        .evidencia-container { margin-top: 20px; text-align: center; }
        .evidencia-img { max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 5px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">HOTEL EMPORIO MAZATLÁN</h2>
        <p style="margin:5px 0 0 0;">Reporte de TI #{{ $ticket->id }}</p>
    </div>

    <div class="info-section">
        <div style="float: right; color: #041b32; font-weight: bold;">{{ strtoupper($ticket->status) }}</div>
        <div class="label">Solicitante:</div>
        <div class="value">{{ $ticket->user->name }}</div>
    </div>

    <div style="width: 100%; display: table;">
        <div style="display: table-row;">
            <div style="display: table-cell; width: 50%;">
                <div class="label">Área / Ubicación:</div>
                <div class="value">{{ $ticket->area }}</div>
            </div>
            <div style="display: table-cell; width: 50%;">
                <div class="label">Prioridad:</div>
                <div class="value" style="color: {{ $ticket->prioridad == 'Alta' ? '#e11d48' : '#334155' }};">
                    {{ $ticket->prioridad }}
                </div>
            </div>
        </div>
    </div>

    <div class="info-section" style="margin-top: 15px;">
        <div class="label">Descripción del reporte:</div>
        <div class="value">{{ $ticket->descripcion }}</div>
    </div>

    @if($ticket->status === 'Resuelto')
    <div class="info-section" style="background: #f0fdf4; padding: 10px; border: 1px solid #bbf7d0;">
        <div class="label" style="color: #166534;">Solución Aplicada:</div>
        <div class="value">{{ $ticket->notas_admin }}</div>
        <div style="font-size: 10px; color: #166534; margin-top: 5px;">Finalizado el: {{ $ticket->fecha_resolucion }}</div>
    </div>
    @endif

    @if($base64)
    <div class="evidencia-container">
        <div class="label" style="margin-bottom: 10px;">Evidencia Fotográfica:</div>
        <img src="{{ $base64 }}" class="evidencia-img">
    </div>
    @endif

    <div class="footer">
        Documento generado automáticamente por el Sistema de Gestión de TI - Hotel Emporio Mazatlán
    </div>
</body>
</html>