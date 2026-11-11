<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiquetas QR · SIGHO</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 16px; color: #0f172a; background: #fff; }
    .barra { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
    .barra button { background: #041b32; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .hoja { display: grid; grid-template-columns: repeat(auto-fill, 6.4cm); gap: 0.3cm; }
    .etiqueta { width: 6.4cm; height: 3.2cm; border: 1px dashed #94a3b8; border-radius: 4px; box-sizing: border-box; padding: 0.2cm; display: flex; gap: 0.2cm; align-items: center; break-inside: avoid; page-break-inside: avoid; }
    .qr { width: 2.6cm; height: 2.6cm; flex: none; }
    .qr img, .qr canvas { width: 2.6cm !important; height: 2.6cm !important; }
    .txt { min-width: 0; font-size: 8pt; line-height: 1.25; }
    .nom { font-size: 11pt; font-weight: bold; font-family: monospace; word-break: break-all; }
    .hotel { font-size: 6.5pt; color: #475569; text-transform: uppercase; margin-top: 2px; }
    @media print { .barra { display: none; } body { margin: 0.5cm; } .etiqueta { border-color: #cbd5e1; } @page { size: letter; margin: 0.6cm; } }
</style>
</head>
<body>
<div class="barra">
    <button onclick="window.print()">Imprimir</button>
    <span>{{ $activos->count() }} etiqueta(s) · tamaño 6.4 × 3.2 cm. Al escanear abre la ficha del equipo en SIGHO (requiere iniciar sesión).</span>
</div>
@if($activos->isEmpty())
    <p>No hay activos seleccionados.</p>
@endif
<div class="hoja">
    @foreach($activos as $a)
        <div class="etiqueta">
            <div class="qr" data-url="{{ route('assets.show', $a->id) }}"></div>
            <div class="txt">
                <div class="nom">{{ $a->nomenclatura }}</div>
                <div>{{ \Illuminate\Support\Str::limit($a->tipo_equipo ?: $a->nombre, 40) }}</div>
                @if($a->numero_serie)<div>S/N {{ \Illuminate\Support\Str::limit($a->numero_serie, 24) }}</div>@endif
                @if($a->ubicacion)<div>{{ \Illuminate\Support\Str::limit($a->ubicacion->etiqueta, 30) }}</div>@endif
                <div class="hotel">{{ \App\Models\Hotel::nombrePara($a->hotel_code) }} · Sistemas</div>
            </div>
        </div>
    @endforeach
</div>
<script>
    document.querySelectorAll('.qr').forEach(function (el) {
        new QRCode(el, { text: el.dataset.url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
    });
</script>
</body>
</html>
