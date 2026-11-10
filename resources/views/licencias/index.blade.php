@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Licencias de software</h2>
            <p class="text-gray-500 text-sm mt-1">Compradas contra instaladas. No se guardan claves de producto aquí.</p>
        </div>
        @if(auth()->user()->puedeResponderTickets() || auth()->user()->puedeVerTodos())
            <a href="{{ route('licencias.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nueva licencia</a>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">Software</th><th class="py-3 px-4">Tipo</th><th class="py-3 px-4">Proveedor</th>
                <th class="py-3 px-4 text-center">En uso / compradas</th><th class="py-3 px-4">Vence</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($licencias as $l)
                    @php $d = $l->diasRestantes(); $exceso = $l->activos_count > $l->cantidad; @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4"><a href="{{ route('licencias.edit', $l) }}" class="font-bold text-[#041b32] hover:underline">{{ $l->software }}</a> <span class="text-xs text-slate-500">{{ $l->edicion }}</span><div class="text-xs text-slate-400">{{ $l->referencia }}</div></td>
                        <td class="py-3 px-4 text-xs">{{ $tipos[$l->tipo] ?? $l->tipo }}</td>
                        <td class="py-3 px-4 text-xs">{{ $l->proveedor?->nombre }}</td>
                        <td class="py-3 px-4 text-center font-bold {{ $exceso ? 'text-red-700' : 'text-slate-700' }}">{{ $l->activos_count }} / {{ $l->cantidad }}@if($exceso)<div class="text-[11px] font-normal">¡faltan {{ $l->activos_count - $l->cantidad }}!</div>@endif</td>
                        <td class="py-3 px-4 text-xs whitespace-nowrap {{ $d !== null && $d < 0 ? 'text-red-700 font-bold' : ($d !== null && $d <= 30 ? 'text-orange-700 font-bold' : '') }}">{{ $l->fecha_vencimiento?->format('d/m/Y') ?? 'No vence' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-gray-400">Sin licencias. Ejemplos: Microsoft 365, antivirus, Opera, Micros.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
