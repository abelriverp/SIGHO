@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto mb-10">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Topología de red</h2>
            <p class="text-gray-500 text-sm mt-1">Se arma con el campo "Cuelga de (uplink)" de cada equipo de red. Verde: responde al ping · rojo: caído · gris: sin monitoreo.</p>
        </div>
        <a href="{{ route('network.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Equipos de red</a>
    </div>
    @if($equipos->isEmpty())
        <div class="bg-white rounded-lg border border-slate-200 p-8 text-center text-slate-400">Aún no hay equipos de red registrados.</div>
    @else
        <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 overflow-x-auto">
            <ul>
                @foreach($raices as $d)
                    @include('network._nodo', ['d' => $d, 'nivel' => 0])
                @endforeach
            </ul>
        </div>
        @if($raices->count() > 1)
            <p class="text-xs text-slate-500 mt-3">Hay {{ $raices->count() }} equipos sin uplink. Si no todos son el equipo principal, edítalos e indica de cuál cuelgan.</p>
        @endif
    @endif
</div>
@endsection
