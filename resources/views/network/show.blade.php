@extends('layouts.app')

@section('content')

<style>
    .hardware-scroll::-webkit-scrollbar {
        height: 10px;
    }
    .hardware-scroll::-webkit-scrollbar-track {
        background: #1e293b; 
        border-radius: 8px;
    }
    .hardware-scroll::-webkit-scrollbar-thumb {
        background: #475569; 
        border-radius: 8px;
    }
    .hardware-scroll::-webkit-scrollbar-thumb:hover {
        background: #64748b; 
    }
</style>

<div x-data="{ 
        panelOpen: false, 
        portId: null, 
        portNum: null, 
        connectedTo: '', 
        speed: 'Gigabit',
        status: '',
        openPanel(id, num, conn, spd, stat) {
            this.portId = id;
            this.portNum = num;
            this.connectedTo = conn || '';
            this.speed = spd || 'Gigabit';
            this.status = stat;
            this.panelOpen = true;
        }
    }" 
    class="max-w-7xl mx-auto px-4 mt-6 relative overflow-hidden min-h-screen">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32] tracking-tight">{{ $device->nombre }}</h2>
            <p class="text-gray-500 font-medium">{{ $device->tipo }} localizado en {{ $device->ubicacion }}</p>
        </div>
        
        <div class="flex gap-3">
            <a href="{{ route('network.index') }}" class="text-gray-500 hover:text-[#041b32] font-bold py-2 px-4 transition">
             
            </a>
            <form action="{{ route('network.destroy', $device->id) }}" method="POST" onsubmit="return confirm('¿Borrar este dispositivo completo?');">
                @csrf @method('DELETE')
                <button class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 px-4 rounded shadow-sm transition">Eliminar Equipo</button>
            </form>
        </div>
    </div>

    <div class="bg-[#111827] rounded-xl p-6 md:p-8 shadow-2xl mb-8 border border-gray-800 relative">
        
        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center text-white font-bold text-xs">U</div>
                <span class="text-gray-400 font-mono text-sm tracking-[0.2em] uppercase">{{ $device->tipo }} • {{ $device->total_ports }} PORTS</span>
            </div>
            
            <div class="flex gap-4 text-xs font-mono tracking-wider">
                <span class="flex items-center gap-2 text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span> LINK/ACT
                </span>
                <span class="flex items-center gap-2 text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-gray-600"></span> DOWN
                </span>
            </div>
        </div>

        <div class="w-full overflow-x-auto pb-4 hardware-scroll">
            <div class="bg-[#0f172a] p-4 rounded-lg border border-[#1e293b] shadow-inner min-w-max mx-auto">
                
                <div class="grid grid-rows-2 grid-flow-col gap-x-2 gap-y-4">
                    
                    @foreach($device->ports as $port)
                        <button 
                            @click="openPanel({{ $port->id }}, {{ $port->port_number }}, '{{ addslashes($port->connected_to) }}', '{{ $port->speed }}', '{{ $port->status }}')"
                            class="relative w-12 h-12 border-2 rounded-md flex flex-col items-center justify-center group transition-all duration-300 flex-shrink-0
                            {{ $port->status == 'Ocupado' ? 'bg-green-700 border-green-400 shadow-[0_0_12px_rgba(34,197,94,0.4)]' : 'bg-black border-gray-800 hover:border-gray-600' }}">
                            
                            <div class="w-7 h-5 bg-[#0a0f18] rounded-sm mb-1 shadow-inner relative overflow-hidden">
                                <div class="absolute bottom-0 w-full h-1 bg-gradient-to-t from-yellow-700 to-transparent opacity-30"></div>
                            </div>
                            
                            <span class="text-[10px] font-mono font-bold transition-colors {{ $port->status == 'Ocupado' ? 'text-white' : 'text-gray-500 group-hover:text-gray-300' }}">
                                {{ str_pad($port->port_number, 2, '0', STR_PAD_LEFT) }}
                            </span>

                            @if($port->status == 'Ocupado')
                                <span class="absolute top-1 left-1 w-1.5 h-1.5 bg-green-300 rounded-full shadow-[0_0_6px_rgba(134,239,172,1)] animate-pulse"></span>
                            @else
                                <span class="absolute top-1 left-1 w-1.5 h-1.5 bg-gray-800 rounded-full"></span>
                            @endif
                        </button>
                    @endforeach

                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mt-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700">Puertos Activos</h3>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                {{ $device->ports->where('status', 'Ocupado')->count() }} / {{ $device->total_ports }} Conectados
            </span>
        </div>
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="text-xs uppercase text-gray-400 bg-white">
                <tr>
                    <th class="py-3 px-6 font-semibold">Puerto</th>
                    <th class="py-3 px-6 font-semibold">Identificador</th>
                    <th class="py-3 px-6 font-semibold">Velocidad</th>
                    <th class="py-3 px-6 font-semibold text-right">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($device->ports as $port)
                    @if($port->status == 'Ocupado')
                    <tr class="border-t border-gray-50 hover:bg-gray-50 transition cursor-pointer" 
                        @click="openPanel({{ $port->id }}, {{ $port->port_number }}, '{{ addslashes($port->connected_to) }}', '{{ $port->speed }}', '{{ $port->status }}')">
                        <td class="py-3 px-6 font-mono font-bold text-[#041b32]">Port {{ $port->port_number }}</td>
                        <td class="py-3 px-6 font-bold text-slate-700">{{ $port->connected_to }}</td>
                        <td class="py-3 px-6">
                            <span class="px-2 py-1 rounded text-xs font-bold {{ $port->speed == 'Gigabit' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $port->speed }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-right">
                            <button class="text-blue-600 hover:text-blue-800 text-xs font-bold uppercase tracking-wider">Configurar</button>
                        </td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div x-show="panelOpen" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div x-show="panelOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" 
             @click="panelOpen = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-md w-full flex">
            <div x-show="panelOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-full bg-white shadow-2xl flex flex-col h-full border-l border-gray-200">
                
                <div class="px-6 py-6 bg-[#041b32] text-white flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold tracking-wide">Configuración de Puerto</h2>
                        <p class="text-blue-200 text-sm font-mono mt-1">Port <span x-text="portNum"></span></p>
                    </div>
                    <button type="button" @click="panelOpen = false" class="text-blue-200 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form :action="`/network/port/${portId}`" method="POST" class="flex-1 flex flex-col justify-between overflow-y-auto p-6">
                    @csrf
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Identificador / Dispositivo
                            </label>
                            <input type="text" name="connected_to" x-model="connectedTo" 
                                placeholder="Ej: AP Pasillo, PC Recepción..."
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200 p-3 bg-gray-50 text-slate-800 font-medium">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Velocidad del Enlace
                            </label>
                            <select name="speed" x-model="speed" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200 p-3 bg-gray-50 text-slate-800 font-medium">
                                <option value="Gigabit">Gigabit (10/100/1000)</option>
                                <option value="Fast Ethernet">Fast Ethernet (10/100)</option>
                            </select>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                                Estado del Puerto
                            </label>
                            <div class="flex items-center gap-3">
                                <template x-if="connectedTo !== ''">
                                    <span class="flex items-center gap-2 text-sm font-bold text-green-600 bg-green-100 px-3 py-1.5 rounded-full">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Ocupado / Activo
                                    </span>
                                </template>
                                <template x-if="connectedTo === ''">
                                    <span class="flex items-center gap-2 text-sm font-bold text-gray-500 bg-gray-200 px-3 py-1.5 rounded-full">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span> Libre / Desconectado
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex gap-3">
                        <button type="button" @click="panelOpen = false" class="flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 bg-[#041b32] text-white font-bold py-3 rounded-lg shadow-md hover:bg-blue-900 transition">
                            Aplicar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection