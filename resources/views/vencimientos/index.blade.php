@extends('layouts.app')

@section('content')
@php
    $color = ['Contrato' => 'bg-purple-100 text-purple-800', 'Licencia' => 'bg-blue-100 text-blue-800', 'Garantía' => 'bg-emerald-100 text-emerald-800'];
    $fila = function ($i) use ($color) {
        $url = route($i['ruta'], $i['id']);
        return [$url, $color[$i['tipo']] ?? 'bg-slate-100'];
    };
@endphp
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Vencimientos</h2>
            <p class="text-gray-500 text-sm mt-1">Contratos, licencias y garantías. Gerentes y administradores reciben aviso por correo a los 30, 15, 7 y 1 días.</p>
        </div>
        <div class="flex gap-2">
            @foreach([30, 90, 180, 365] as $d)
                <a href="{{ route('vencimientos.index', ['dias' => $d]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $dias === $d ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $d }} días</a>
            @endforeach
        </div>
    </div>

    @foreach([['Próximos '.$dias.' días', $proximos, false], ['Vencidos (último año)', $vencidos, true]] as [$titulo, $lista, $esVencido])
        <h3 class="text-sm font-bold uppercase {{ $esVencido ? 'text-red-700' : 'text-slate-600' }} mb-2 mt-6">{{ $titulo }} ({{ $lista->count() }})</h3>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            @forelse($lista as $i)
                @php [$url, $badge] = $fila($i); @endphp
                <a href="{{ $url }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-b border-gray-50 hover:bg-slate-50">
                    <span class="flex items-center gap-3">
                        <span class="py-0.5 px-2 rounded-full text-[11px] font-bold {{ $badge }}">{{ $i['tipo'] }}</span>
                        <span class="text-sm"><span class="font-bold text-slate-800">{{ $i['nombre'] }}</span> <span class="text-xs text-slate-500">{{ $i['detalle'] }}</span></span>
                    </span>
                    <span class="text-xs whitespace-nowrap {{ $esVencido ? 'text-red-700 font-bold' : ($i['dias'] <= 30 ? 'text-orange-700 font-bold' : 'text-slate-600') }}">
                        {{ $i['fecha']->format('d/m/Y') }} · {{ $esVencido ? 'hace '.abs($i['dias']).' días' : ($i['dias'] === 0 ? 'hoy' : 'en '.$i['dias'].' días') }}
                    </span>
                </a>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400">Nada {{ $esVencido ? 'vencido' : 'por vencer en este periodo' }}.</p>
            @endforelse
        </div>
    @endforeach
</div>
@endsection
