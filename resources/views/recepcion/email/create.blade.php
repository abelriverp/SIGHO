@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Nuevo análisis de correos</h2>
        <p class="text-sm text-slate-500">Sube el PDF de reservas exportado del sistema. Se buscarán todos los correos electrónicos que contenga y se validarán uno por uno.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <form action="{{ route('recepcion.email.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6" id="form-email">
        @csrf
        <label class="block text-xs font-bold text-slate-500 mb-2">PDF de reservas</label>
        <input type="file" name="pdf" accept="application/pdf" required
            class="w-full border rounded-lg px-3 py-2 text-sm mb-4">

        <div class="flex items-center gap-3">
            <button type="submit" id="btn-submit-email" class="bg-[#2980B9] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">
                Analizar PDF
            </button>
            <a href="{{ route('recepcion.email.index') }}" class="text-sm text-slate-500 hover:underline">Cancelar</a>
            <span id="procesando-email" class="hidden text-sm text-slate-400 italic">Procesando, esto puede tardar unos segundos…</span>
        </div>
    </form>
</div>
<script>
    document.getElementById('form-email').addEventListener('submit', function () {
        document.getElementById('btn-submit-email').setAttribute('disabled', 'disabled');
        document.getElementById('procesando-email').classList.remove('hidden');
    });
</script>
@endsection
