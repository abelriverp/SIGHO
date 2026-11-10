<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; color: #333; font-size: 12px; line-height: 1.5; }
    .header { background: #041b32; color: white; padding: 16px; text-align: center; margin-bottom: 18px; }
    h3 { color: #041b32; border-bottom: 2px solid #041b32; font-size: 12px; text-transform: uppercase; padding-bottom: 3px; margin-top: 18px; }
    table.datos td { padding: 4px 6px; vertical-align: top; }
    table.datos td.l { color: #666; font-weight: bold; font-size: 10px; text-transform: uppercase; width: 28%; }
    table.lista { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.lista th { background: #f1f5f9; text-align: left; padding: 5px; }
    table.lista td { border-bottom: 1px solid #eee; padding: 5px; }
    .firmas { margin-top: 60px; width: 100%; }
    .firmas td { width: 33%; text-align: center; padding: 0 10px; font-size: 11px; }
    .linea { border-top: 1px solid #333; padding-top: 4px; margin-top: 50px; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
@php $a = $baja->asset; @endphp
<div class="header">
    <h2 style="margin:0;">{{ mb_strtoupper(\App\Models\Hotel::nombrePara($baja->hotel_code)) }}</h2>
    <p style="margin:4px 0 0 0;">Acta de baja de activo de TI · Folio BAJA-{{ str_pad($baja->id, 5, '0', STR_PAD_LEFT) }}</p>
</div>

<p>En las instalaciones de {{ \App\Models\Hotel::nombrePara($baja->hotel_code) }}, con fecha {{ $baja->revisado_at?->format('d/m/Y') }}, se hace constar la baja definitiva del activo descrito a continuación, solicitada por {{ $baja->solicitante?->name }} y autorizada por {{ $baja->revisor?->name }}.</p>

<h3>Activo</h3>
<table class="datos" width="100%">
    <tr><td class="l">Nomenclatura</td><td>{{ $a->nomenclatura }}</td><td class="l">Categoría / tipo</td><td>{{ $a->categoria }} {{ $a->tipo_equipo ? '· '.$a->tipo_equipo : '' }}</td></tr>
    <tr><td class="l">Marca / modelo</td><td>{{ $a->marca }} {{ $a->modelo }}</td><td class="l">Número de serie</td><td>{{ $a->numero_serie ?: '—' }}</td></tr>
    <tr><td class="l">Ubicación</td><td>{{ $a->ubicacion?->etiqueta ?? $a->area }}</td><td class="l">Fecha de compra</td><td>{{ $a->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td></tr>
    <tr><td class="l">Factura / proveedor</td><td>{{ $a->factura ?: '—' }} {{ $a->proveedor ? '· '.$a->proveedor : '' }}</td><td class="l">Costo de adquisición</td><td>{{ $a->costo !== null ? '$'.number_format($a->costo, 2).' MXN' : '—' }}</td></tr>
</table>

@if($a->componentes->isNotEmpty())
    <h3>Periféricos y componentes ligados</h3>
    <table class="lista">
        <tr><th>Nomenclatura</th><th>Tipo</th><th>Marca / modelo</th><th>Serie</th></tr>
        @foreach($a->componentes as $c)
            <tr><td>{{ $c->nomenclatura }}</td><td>{{ $c->tipo_equipo ?: $c->categoria }}</td><td>{{ $c->marca }} {{ $c->modelo }}</td><td>{{ $c->numero_serie }}</td></tr>
        @endforeach
    </table>
    <p style="font-size:10px;color:#666;">Los periféricos ligados no se dan de baja automáticamente; si también se retiran, requieren su propia solicitud.</p>
@endif

<h3>Motivo de la baja</h3>
<p style="white-space: pre-line;">{{ $baja->motivo }}</p>
<p><strong>Destino final:</strong> {{ \App\Models\BajaActivo::DESTINOS[$baja->destino] ?? 'Por definir' }}</p>
@if($baja->comentario_revision)<p><strong>Comentario de autorización:</strong> {{ $baja->comentario_revision }}</p>@endif
@if($baja->dictamen_path)<p style="font-size:10px;color:#666;">Se adjuntó dictamen técnico en SIGHO.</p>@endif

<table class="firmas">
    <tr>
        <td><div class="linea">{{ $baja->solicitante?->name }}<br>Solicita (Sistemas)</div></td>
        <td><div class="linea">{{ $baja->revisor?->name }}<br>Autoriza</div></td>
        <td><div class="linea">&nbsp;<br>Contraloría / Activo fijo</div></td>
    </tr>
</table>

<div class="footer">Generado por SIGHO el {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
