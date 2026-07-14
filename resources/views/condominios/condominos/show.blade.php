@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 mt-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('condominios.condominos.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver a condóminos
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $condomino->nombre }}</h2>
        </div>
        <a href="{{ route('condominios.condominos.edit', $condomino->id) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded shadow hover:opacity-90">
            Editar
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-4">

            {{-- Unidades --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase">Unidades ({{ $condomino->unidades->count() }})</h3>
                </div>
                @if($condomino->unidades->isEmpty())
                    <div class="py-8 text-center text-gray-400 text-sm">Sin unidades asignadas.</div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($condomino->unidades as $u)
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <span class="font-bold text-[#041b32]">{{ $u->nombreCompleto() }}</span>
                                <span class="text-xs text-gray-500">{{ $u->estatus }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Historial de estancias --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase">Historial de estancias</h3>
                </div>
                @if($condomino->estancias->isEmpty())
                    <div class="py-8 text-center text-gray-400 text-sm">Sin estancias registradas.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                    <th class="py-2 px-4 text-left">Unidad</th>
                                    <th class="py-2 px-4 text-left">Llegada</th>
                                    <th class="py-2 px-4 text-left">Tipo</th>
                                    <th class="py-2 px-4 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($condomino->estancias as $e)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4">{{ $e->unidad->nombreCompleto() ?? '—' }}</td>
                                    <td class="py-2 px-4 text-xs">{{ $e->fecha_llegada_prevista->format('d/m/Y') }}</td>
                                    <td class="py-2 px-4 text-xs">{{ $e->tipoVisitanteLabel() }}</td>
                                    <td class="py-2 px-4 text-xs">{{ $e->estadoLabel() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-2 text-xs text-gray-500">
                <div><span class="font-bold text-gray-700">Teléfono:</span> {{ $condomino->telefono ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Email:</span> {{ $condomino->email ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Identificación:</span> {{ $condomino->identificacion ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Propietario desde:</span> {{ $condomino->fecha_alta?->format('d/m/Y') ?? '—' }}</div>
                @if($condomino->notas)
                    <div class="pt-2 border-t border-gray-100"><span class="font-bold text-gray-700">Notas:</span> {{ $condomino->notas }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
