@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 mt-6 mb-10">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('condominios.estancias.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver a estancias
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $estancia->nombre_visitante }}</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="bg-indigo-50 text-indigo-800 py-1 px-2 rounded-full text-xs font-bold">{{ $estancia->unidad->nombreCompleto() }}</span>
                <span class="bg-blue-50 text-blue-800 py-1 px-2 rounded-full text-xs font-bold">{{ $estancia->tipoVisitanteLabel() }}</span>
                @php
                    $badge = match($estancia->estado) {
                        'reservada'  => 'bg-gray-100 text-gray-600',
                        'confirmada' => 'bg-indigo-100 text-indigo-800',
                        'checkin'    => 'bg-green-100 text-green-800',
                        'checkout'   => 'bg-blue-100 text-blue-800',
                        'cancelada'  => 'bg-red-100 text-red-800',
                        'no_show'    => 'bg-yellow-100 text-yellow-800',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badge }}">{{ $estancia->estadoLabel() }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Identificación / Placas</span>{{ $estancia->identificacion_placas ?? '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Acompañantes</span>{{ $estancia->num_acompanantes }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Condómino</span>{{ $estancia->condomino->nombre ?? '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Llegada prevista</span>{{ $estancia->fecha_llegada_prevista->format('d/m/Y') }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Salida prevista</span>{{ optional($estancia->fecha_salida_prevista)->format('d/m/Y') ?? '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Tarifa/noche</span>{{ $estancia->tarifa_noche ? '$' . number_format($estancia->tarifa_noche, 2) : '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Check-in real</span>{{ optional($estancia->checkin_real)->format('d/m/Y H:i') ?? '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Check-out real</span>{{ optional($estancia->checkout_real)->format('d/m/Y H:i') ?? '—' }}</div>
            <div><span class="block text-xs font-bold text-gray-400 uppercase">Noches</span>{{ $estancia->nochesEstancia() ?? '—' }}</div>
        </div>

        @if($estancia->notas)
            <div class="pt-3 border-t border-gray-100 text-sm">
                <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Notas</span>
                {{ $estancia->notas }}
            </div>
        @endif

        <div class="pt-3 border-t border-gray-100 text-xs text-gray-400">
            Registrado por {{ $estancia->usuario->name ?? '—' }} el {{ $estancia->created_at->format('d/m/Y H:i') }}
        </div>

        <div class="flex gap-2 justify-end pt-3 border-t border-gray-100">
            @if($estancia->puedeHacerCheckin())
                <form action="{{ route('condominios.estancias.checkin', $estancia->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold text-sm py-2 px-4 rounded-lg">Registrar Check-in</button>
                </form>
            @endif
            @if($estancia->puedeHacerCheckout())
                <form action="{{ route('condominios.estancias.checkout', $estancia->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2 px-4 rounded-lg">Registrar Check-out</button>
                </form>
            @endif
            @if(!in_array($estancia->estado, ['checkout', 'cancelada']))
                <form action="{{ route('condominios.estancias.cancelar', $estancia->id) }}" method="POST" onsubmit="return confirm('¿Cancelar esta estancia?');">
                    @csrf
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm py-2 px-4 rounded-lg">Cancelar</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
