@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 mt-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Diagrama de Red</h2>
            <p class="text-gray-500 text-sm">Gestión de Switches y Patch Panels</p>
        </div>
        <a href="{{ route('network.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded shadow hover:opacity-90 transition">
            + Nuevo Equipo de Red
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($devices as $device)
        <a href="{{ route('network.show', $device->id) }}" class="block group">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-lg hover:border-blue-400 transition">
                <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-[#041b32]">{{ $device->nombre }}</h3>
                        <span class="text-xs font-bold px-2 py-1 rounded bg-blue-50 text-blue-800 mt-1 inline-block">{{ $device->tipo }}</span>
                    </div>
                    <div class="bg-gray-100 p-2 rounded text-gray-500 group-hover:bg-[#041b32] group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <p class="text-sm text-gray-600 flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        {{ $device->ubicacion ?? 'Ubicación no especificada' }}
                    </p>
                    <p class="text-sm text-gray-600 flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ $device->total_ports }} Puertos Totales
                    </p>
                </div>
            </div>
        </a>
        @endforeach

        @if($devices->isEmpty())
        <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12 text-gray-400 bg-white rounded-lg shadow-sm border border-gray-100">
            No hay dispositivos de red registrados aún.
        </div>
        @endif
    </div>
</div>
@endsection