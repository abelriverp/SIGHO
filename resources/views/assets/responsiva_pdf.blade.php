<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; color: #333; font-size: 12px; line-height: 1.5; }
    .header { background: #041b32; color: white; padding: 16px; text-align: center; margin-bottom: 18px; }
    table.lista { width: 100%; border-collapse: collapse; font-size: 10.5px; margin: 10px 0; }
    table.lista th { background: #f1f5f9; text-align: left; padding: 5px; }
    table.lista td { border-bottom: 1px solid #eee; padding: 5px; vertical-align: top; }
    .sub td { color: #555; font-size: 10px; padding-left: 16px; }
    ol { padding-left: 18px; } li { margin-bottom: 4px; }
    .firmas { margin-top: 50px; width: 100%; }
    .firmas td { width: 50%; text-align: center; padding: 0 20px; font-size: 11px; }
    .linea { border-top: 1px solid #333; padding-top: 4px; margin-top: 50px; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="header">
    <h2 style="margin:0;">{{ mb_strtoupper($hotel) }}</h2>
    <p style="margin:4px 0 0 0;">Carta responsiva de equipo de cómputo y comunicaciones</p>
</div>

<p>Cancún, Quintana Roo, a {{ now()->translatedFormat('d \d\e F \d\e Y') }}.</p>

<p>Yo, <strong>{{ $nombre }}</strong>{{ $usuario?->departamento ? ', del departamento de '.$usuario->departamento : '' }}{{ $usuario?->area ? ' ('.$usuario->area.')' : '' }}, recibo del Departamento de Sistemas de {{ $hotel }} el siguiente equipo, propiedad de la empresa, para el desempeño de mis funciones:</p>

<table class="lista">
    <tr><th>Nomenclatura</th><th>Equipo</th><th>Marca / modelo</th><th>Serie</th><th>Ubicación</th></tr>
    @foreach($activos as $a)
        <tr>
            <td><strong>{{ $a->nomenclatura }}</strong></td>
            <td>{{ $a->tipo_equipo ?: $a->categoria }}</td>
            <td>{{ $a->marca }} {{ $a->modelo }}</td>
            <td>{{ $a->numero_serie ?: '—' }}</td>
            <td>{{ $a->ubicacion?->etiqueta ?? $a->area }}</td>
        </tr>
        @foreach($a->componentes as $c)
            <tr class="sub"><td>↳ {{ $c->nomenclatura }}</td><td>{{ $c->tipo_equipo ?: $c->categoria }}</td><td>{{ $c->marca }} {{ $c->modelo }}</td><td>{{ $c->numero_serie ?: '—' }}</td><td></td></tr>
        @endforeach
    @endforeach
</table>

<p>Me comprometo a:</p>
<ol>
    <li>Usar el equipo exclusivamente para actividades de trabajo y conforme a las políticas de Sistemas del hotel.</li>
    <li>Cuidarlo y mantenerlo en buen estado; reportar de inmediato cualquier falla, daño, robo o extravío mediante un ticket en SIGHO.</li>
    <li>No instalar software sin autorización, no abrir el equipo ni prestarlo o trasladarlo fuera de su ubicación sin aviso a Sistemas.</li>
    <li>No compartir mis contraseñas ni dejar la sesión abierta sin supervisión.</li>
    <li>Devolver el equipo y sus accesorios en las mismas condiciones, salvo el desgaste normal, al terminar mi relación laboral o cuando Sistemas lo solicite.</li>
</ol>
<p>Acepto que el daño por mal uso o negligencia, o la pérdida del equipo, se atenderá conforme al reglamento interior de trabajo y a la normatividad aplicable.</p>

<table class="firmas">
    <tr>
        <td><div class="linea">{{ $nombre }}<br>Recibe</div></td>
        <td><div class="linea">{{ $emisor->name }}<br>Entrega · Sistemas</div></td>
    </tr>
</table>

<div class="footer">Generado por SIGHO el {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
