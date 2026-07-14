@extends('layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl tracking-tight">
                Panel de Control — SIGHO <span class="text-sm font-normal text-gray-500">v1.5</span>
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Monitoreo general de operaciones y servicios para la propiedad:
                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 uppercase">{{ $hotelActivo }}</span>
            </p>
        </div>
    </div>

    @if($suministrosBajos > 0 && auth()->user()->tieneModulo('inventario_suministros'))
        <div class="mb-6 rounded-md bg-amber-50 p-4 border border-amber-200 shadow-sm animate-pulse">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1a1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">Atención: Alerta de Inventario</h3>
                    <div class="mt-1 text-sm text-amber-700">
                        <p>Existen <strong>{{ $suministrosBajos }}</strong> artículos que han alcanzado o superado el stock mínimo permitido.</p>
                    </div>
                    <div class="mt-2 text-sm">
                        <a href="{{ route('inventory.index') }}" class="font-medium text-amber-800 underline hover:text-amber-900">
                            Ver artículos críticos &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt>
                <div class="absolute rounded-md bg-indigo-500 p-3 text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3.375M7.5 18.75h9.75m-9.75-6H19.5a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0019.5 3H4.5A2.25 2.25 0 002.25 5.25v13.5A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V15M2.25 15h12" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Tickets Pendientes</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ $ticketsAbiertos }}</p>
                <p class="ml-2 text-xs text-gray-400">de {{ $ticketsTotales }} totales</p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <a href="{{ route('tickets.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Administrar soporte</a>
                    </div>
                </div>
            </dd>
        </div>

        @if(auth()->user()->tieneModulo('inventario_suministros'))
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt>
                <div class="absolute rounded-md {{ $suministrosBajos > 0 ? 'bg-amber-500' : 'bg-emerald-500' }} p-3 text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Alertas de Inventario</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                <p class="text-2xl font-semibold {{ $suministrosBajos > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $suministrosBajos }}</p>
                <p class="ml-2 text-xs text-gray-400">en stock crítico</p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <a href="{{ route('inventory.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Revisar almacén</a>
                    </div>
                </div>
            </dd>
        </div>
        @endif

        @if(auth()->user()->tieneModulo('seguimiento_equipos_ti'))
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt>
                <div class="absolute rounded-md bg-blue-500 p-3 text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25zm-16.5 3h16.5m-16.5 3h16.5" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Equipos Registrados</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ $totalEquipos }}</p>
                <p class="ml-2 text-xs text-gray-400">monitoreados actualmente</p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <a href="{{ route('assets.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Ver lista de equipos</a>
                    </div>
                </div>
            </dd>
        </div>
        @endif

    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100 lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Historial Reciente & Tokens de Auditoría</h3>
                <p class="text-xs text-gray-500">Últimos movimientos validados en el sistema</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                    <thead>
                        <tr class="text-gray-500 text-xs uppercase tracking-wider bg-gray-50">
                            <th class="py-3 px-4 font-semibold">Token Auditoría</th>
                            <th class="py-3 px-4 font-semibold">Descripción de la Falla</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($ultimosTickets as $ticket)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs font-bold text-indigo-600 whitespace-nowrap">
                                    HEMZT-TK-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-3 px-4 text-gray-700 max-w-xs truncate">
                                    {{ $ticket->descripcion ?? 'Sin descripción' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                        @if(($ticket->status ?? '') === 'Abierto') bg-red-50 text-red-700 ring-red-600/10 
                                        @elseif(($ticket->status ?? '') === 'En Proceso') bg-blue-50 text-blue-700 ring-blue-600/10 
                                        @else bg-gray-50 text-gray-600 ring-gray-500/10 @endif">
                                        {{ ucfirst($ticket->status ?? 'Desconocido') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-sm text-gray-400">No hay registros de actividad reciente para tu usuario.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <div class="mb-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Acceso a Módulos</h3>
                <p class="text-xs text-gray-500">Módulos permitidos para tu perfil actual</p>
            </div>

            <nav class="space-y-2" aria-label="Sidebar">
                @php
                    $modulosConfig = [
                        ['id' => 'seguimiento_equipos_ti', 'name' => 'Seguimiento de Equipos', 'route' => 'assets.index', 'bg' => 'bg-blue-50 text-blue-700'],
                        ['id' => 'inventario_suministros', 'name' => 'Suministros', 'route' => 'inventory.index', 'bg' => 'bg-emerald-50 text-emerald-700'],
                        ['id' => 'red_infraestructura', 'name' => 'Red e Infraestructura', 'route' => 'network.index', 'bg' => 'bg-purple-50 text-purple-700'],
                        ['id' => 'perifericos', 'name' => 'Periféricos', 'route' => 'assets.perifericos', 'bg' => 'bg-pink-50 text-pink-700'],
                        ['id' => 'inventario_habitaciones', 'name' => 'Habitaciones', 'route' => 'habitaciones.index', 'bg' => 'bg-orange-50 text-orange-700'],
                        ['id' => 'gestion_tickets', 'name' => 'Mesa de Tickets', 'route' => 'tickets.index', 'bg' => 'bg-indigo-50 text-indigo-700'],
                    ];
                @endphp

                @foreach($modulosConfig as $mod)
                    @if(auth()->user()->tieneModulo($mod['id']))
                        <a href="{{ route($mod['route']) }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-50 transition group border border-gray-100 shadow-sm">
                            <span class="truncate text-gray-700 group-hover:text-gray-900">{{ $mod['name'] }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $mod['bg'] }}">Activo</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>

    </div>

</div>
@endsection