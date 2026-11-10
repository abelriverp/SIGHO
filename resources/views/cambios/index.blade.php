@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Cambios</h2>
            <p class="text-gray-500 text-sm mt-1">Todo trabajo con riesgo de afectar la operación (actualizar el PMS, cambiar el firewall, mover un rack) se planea con ventana, plan de regreso y aprobación.</p>
        </div>
        <a href="{{ route('cambios.create') }}" class="hotel-blue text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nuevo cambio</a>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach(['activos' => 'En curso', 'pendientes' => 'Por aprobar' . ($pendientes ? " ($pendientes)" : ''), 'historial' => 'Historial', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('cambios.index', ['vista' => $k]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $vista === $k ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $v }}</a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead><tr class="hotel-blue text-white uppercase text-xs">
                <th class="py-3 px-4">#</th><th class="py-3 px-4">Cambio</th><th class="py-3 px-4">Ventana</th><th class="py-3 px-4 text-center">Riesgo</th><th class="py-3 px-4 text-center">Equipos</th><th class="py-3 px-4">Solicitó</th><th class="py-3 px-4 text-center">Estado</th>
            </tr></thead>
            <tbody class="text-sm text-gray-600">
                @forelse($cambios as $c)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-xs">C-{{ $c->id }}</td>
                        <td class="py-3 px-4"><a href="{{ route('cambios.show', $c) }}" class="font-bold text-[#041b32] hover:underline">{{ $c->titulo }}</a><div class="text-xs text-slate-400">{{ ucfirst($c->tipo) }}</div></td>
                        <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $c->ventana_inicio?->format('d/m/Y H:i') ?? 'Sin fecha' }}{{ $c->ventana_fin ? ' – '.$c->ventana_fin->format('H:i') : '' }}</td>
                        <td class="py-3 px-4 text-center text-xs font-bold {{ $c->riesgo === 'Alta' ? 'text-red-600' : ($c->riesgo === 'Media' ? 'text-amber-600' : 'text-green-700') }}">{{ $c->riesgo }}</td>
                        <td class="py-3 px-4 text-center">{{ $c->activos_count }}</td>
                        <td class="py-3 px-4 text-xs">{{ $c->solicitante?->name }}</td>
                        <td class="py-3 px-4 text-center">@include('cambios._badge', ['estado' => $c->estado])</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-gray-400">Sin cambios en esta vista.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
