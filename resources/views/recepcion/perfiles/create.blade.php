@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Nueva verificación de correos de llegada</h2>
        <p class="text-sm text-slate-500">Sube el reporte <strong>"Profiles with e-mails"</strong> exportado del sistema de reservas (recomendado: después de las llegadas del día). Se revisa cada reserva y se valida su correo — formato, dominio, y una verificación best-effort del buzón cuando el dominio lo permite.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <form action="{{ route('recepcion.perfiles.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6" id="form-perfiles">
        @csrf
        <label class="block text-xs font-bold text-slate-500 mb-2">PDF "Profiles with e-mails"</label>
        <input type="file" name="pdf" accept="application/pdf" required
            class="w-full border rounded-lg px-3 py-2 text-sm mb-4">

        <div class="flex items-center gap-3">
            <button type="submit" id="btn-submit-perfiles" class="bg-[#2980B9] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">
                Analizar PDF
            </button>
            <a href="{{ route('recepcion.perfiles.index') }}" class="text-sm text-slate-500 hover:underline">Cancelar</a>
            <span id="procesando-perfiles" class="hidden text-sm text-slate-400 italic">Procesando, esto puede tardar hasta medio minuto si hay dominios propios que verificar…</span>
        </div>
    </form>

    <p class="text-xs text-slate-400 mt-4">Nota: los correos de Gmail, Hotmail/Outlook, Yahoo y proveedores similares no se someten a verificación de buzón por SMTP — esos proveedores no confirman si una cuenta específica existe, por política de privacidad. Sí se intenta en dominios propios/corporativos (por ejemplo, el dominio de una empresa o dependencia de gobierno).</p>
</div>
<script>
    document.getElementById('form-perfiles').addEventListener('submit', function () {
        document.getElementById('btn-submit-perfiles').setAttribute('disabled', 'disabled');
        document.getElementById('procesando-perfiles').classList.remove('hidden');
    });
</script>
@endsection
