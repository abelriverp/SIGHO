@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">🆕 Novedades</h2>
        <p class="text-sm text-slate-500">Funciones nuevas y cambios en el sistema que te corresponden a ti.</p>
    </div>

    @if($anuncios->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center text-sm text-slate-400">
            Todavía no hay novedades para ti.
        </div>
    @else
        <div class="space-y-4">
            @foreach($anuncios as $anuncio)
                <div class="bg-white rounded-lg shadow border border-slate-100 p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="text-sm font-bold text-[#041b32]">{{ $anuncio->titulo }}</h3>
                        <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ $anuncio->created_at->format('d/m/Y') }}</span>
                    </div>
                    @if($anuncio->modulo)
                        <span class="inline-block text-[9px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded uppercase font-bold mb-3">{{ $anuncio->modulo->nombre }}</span>
                    @else
                        <span class="inline-block text-[9px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded uppercase font-bold mb-3">General</span>
                    @endif
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $anuncio->mensaje }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
