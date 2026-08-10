@extends('layouts.app')
@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="max-w-md mx-auto mt-8 no-print">
    <a href="{{ route('puntos-qr.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Volver a la lista</a>
</div>

<div class="max-w-md mx-auto mt-4">
    <div id="tarjeta-qr" class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Escanea para solicitar servicio</p>
        <h2 class="text-2xl font-bold text-[#041b32] mb-6">Habitación / Unidad {{ $puntoQr->identificador }}</h2>

        <div id="qr-grande" class="flex justify-center mb-6"></div>

        <p class="text-[11px] text-slate-400 break-all">{{ $puntoQr->urlPublica() }}</p>
    </div>

    <div class="flex flex-wrap gap-3 justify-center mt-6 no-print">
        <button onclick="descargarQr()" class="bg-[#041b32] text-white font-bold py-2.5 px-5 rounded-full shadow hover:opacity-90 text-sm">
            ⬇️ Descargar imagen
        </button>
        <button onclick="window.print()" class="bg-slate-700 text-white font-bold py-2.5 px-5 rounded-full shadow hover:opacity-90 text-sm">
            🖨️ Imprimir
        </button>
        <button onclick="copiarEnlace()" class="bg-slate-200 text-slate-700 font-bold py-2.5 px-5 rounded-full shadow hover:bg-slate-300 text-sm">
            🔗 Copiar enlace
        </button>
    </div>

    <p id="copiado-msg" class="text-center text-green-600 text-sm mt-3 no-print" style="display:none;">Enlace copiado — ya lo puedes pegar en WhatsApp, correo o SMS.</p>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body * { visibility: hidden; }
        #tarjeta-qr, #tarjeta-qr * { visibility: visible; }
        #tarjeta-qr { position: absolute; top: 0; left: 0; right: 0; margin: 2cm auto; box-shadow: none; border: 1px solid #ddd; }
    }
</style>

<script>
    const urlPublica = @json($puntoQr->urlPublica());

    document.addEventListener('DOMContentLoaded', function () {
        new QRCode(document.getElementById('qr-grande'), { text: urlPublica, width: 260, height: 260 });
    });

    function descargarQr() {
        // qrcode.js dibuja en <canvas> cuando el navegador lo soporta.
        const canvas = document.querySelector('#qr-grande canvas');
        if (!canvas) {
            alert('Tu navegador no permite descargar la imagen directamente. Usa "Imprimir" o haz clic derecho sobre el QR y elige "Guardar imagen".');
            return;
        }
        const enlace = document.createElement('a');
        enlace.download = 'qr-habitacion-{{ $puntoQr->identificador }}.png';
        enlace.href = canvas.toDataURL('image/png');
        enlace.click();
    }

    function copiarEnlace() {
        navigator.clipboard.writeText(urlPublica).then(function () {
            document.getElementById('copiado-msg').style.display = 'block';
        });
    }
</script>
@endsection
