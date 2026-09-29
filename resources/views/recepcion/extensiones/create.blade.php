@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Nueva comparación de extensiones</h2>
        <p class="text-sm text-slate-500">Sube el PDF de salidas (departures) y el de llegadas (arrivals) del mismo periodo. Se buscarán huéspedes con nombre similar en ambos, que probablemente extendieron su estancia en vez de irse.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <form action="{{ route('recepcion.extensiones.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6" id="form-ext">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-bold text-slate-500 mb-2">PDF de Salidas (Departures)</label>
            <input type="file" name="salidas" accept="application/pdf" required
                class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
            <label class="block text-xs font-bold text-slate-500 mb-2">PDF de Llegadas (Arrivals)</label>
            <input type="file" name="llegadas" accept="application/pdf" required
                class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" id="btn-submit-ext" class="bg-[#27AE60] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">
                Comparar listas
            </button>
            <a href="{{ route('recepcion.extensiones.index') }}" class="text-sm text-slate-500 hover:underline">Cancelar</a>
            <span id="procesando-ext" class="hidden text-sm text-slate-400 italic">Procesando, esto puede tardar unos segundos…</span>
        </div>
    </form>
</div>
<script>
    document.getElementById('form-ext').addEventListener('submit', function () {
        document.getElementById('btn-submit-ext').setAttribute('disabled', 'disabled');
        document.getElementById('procesando-ext').classList.remove('hidden');
    });
</script>
@endsection
