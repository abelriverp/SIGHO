@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Segmentos de red / VLANs</h2>
            <p class="text-gray-500 text-sm mt-1">Qué IP está usada, cuál está libre y dónde hay conflictos. Las IPs se toman del catálogo de Activos.</p>
        </div>
        @if(auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos())
            <a href="{{ route('segmentos.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nuevo segmento</a>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">VLAN</th><th class="py-3 px-4">Nombre</th><th class="py-3 px-4">Red</th><th class="py-3 px-4">Gateway</th><th class="py-3 px-4">DHCP</th><th class="py-3 px-4">Uso</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($segmentos as $s)
                    @php $total = $s->totalHosts(); $usadas = $uso[$s->id] ?? 0; $pct = $total ? round($usadas / $total * 100) : 0; @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono font-bold">{{ $s->vlan ?? '—' }}</td>
                        <td class="py-3 px-4"><a href="{{ route('segmentos.show', $s) }}" class="font-bold text-[#041b32] hover:underline">{{ $s->nombre }}</a><div class="text-xs text-slate-400">{{ $s->proposito }}</div></td>
                        <td class="py-3 px-4 font-mono text-xs">{{ $s->red }}</td>
                        <td class="py-3 px-4 font-mono text-xs">{{ $s->gateway }}</td>
                        <td class="py-3 px-4 font-mono text-xs">{{ $s->dhcp_inicio ? $s->dhcp_inicio.' – '.$s->dhcp_fin : '—' }}</td>
                        <td class="py-3 px-4 text-xs w-48">
                            <div class="flex justify-between mb-1"><span>{{ $usadas }} de {{ $total }} en catálogo</span><span>{{ $pct }}%</span></div>
                            <div class="h-1.5 bg-slate-100 rounded"><div class="h-1.5 rounded bg-blue-600" style="width: {{ min(100, $pct) }}%"></div></div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-gray-400">Sin segmentos. Ejemplo: VLAN 64 · Administrativa · 10.7.64.0/24.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
