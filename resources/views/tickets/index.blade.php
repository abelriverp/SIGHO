@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto mt-8 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">
                @if(auth()->user()->isTecnico())
                    Panel de Tickets
                @elseif(auth()->user()->isAdmin() || auth()->user()->isGerente())
                    Todos los Tickets
                @else
                    Mis Reportes
                @endif
            </h2>
            <p class="text-slate-500">Gestión de fallas y requerimientos técnicos.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="bg-[#041b32] text-white font-bold py-2.5 px-6 rounded-lg shadow hover:opacity-90 transition">
            + Nuevo Reporte
        </a>
    </div>

    {{-- ============================================ --}}
    {{-- SECCIÓN: Tickets a Resolver (admin/gerente/tecnico) --}}
    {{-- ============================================ --}}
    @if(auth()->user()->isAdmin() || auth()->user()->isGerente() || auth()->user()->isTecnico())
        <h3 class="text-xl font-bold text-red-600 mb-4 border-b-2 border-red-200 pb-2 flex items-center gap-2">
            <span class="flex h-3 w-3 bg-red-600 rounded-full animate-pulse"></span>
            @if(auth()->user()->isTecnico())
                Tickets de mi Departamento
            @else
                Pendientes de Solución (Todos)
            @endif
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @forelse($ticketsAbiertos as $ticket)
                <div class="bg-white rounded-lg shadow-md border-l-4 border-red-500 p-5 hover:shadow-lg transition">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold uppercase {{ $ticket->prioridad == 'Alta' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }} px-2 py-1 rounded">
                            {{ $ticket->prioridad }}
                        </span>
                        <span class="text-[11px] text-gray-400 font-medium">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    <h4 class="font-bold text-lg text-slate-800">{{ $ticket->area }}</h4>
                    
                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-xs text-blue-600 font-semibold">Reportó: {{ $ticket->user->name }}</p>
                        <span class="text-[9px] bg-slate-100 text-slate-500 border border-slate-200 px-1.5 py-0.5 rounded uppercase font-bold">
                            Para: {{ $ticket->departamento_destino ?? 'Sistemas' }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 line-clamp-3 italic">"{{ $ticket->descripcion }}"</p>
                    
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                        @if($ticket->archivo_path)
                            <span class="text-gray-400" title="Contiene evidencia">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </span>
                        @else
                            <span></span>
                        @endif
                        <a href="{{ route('tickets.show', $ticket->id) }}" class="text-sm font-bold text-[#041b32] hover:text-blue-600 transition">
                            Ver Ticket 
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-100 border-2 border-dashed border-slate-200 rounded-lg p-10 text-center">
                    <p class="text-slate-500 font-medium">No hay tickets pendientes por resolver. </p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ============================================ --}}
    {{-- SECCIÓN: Mis Reportes Personales (todos los roles) --}}
    {{-- ============================================ --}}
    <h3 class="text-xl font-bold text-blue-600 mb-4 border-b-2 border-blue-200 pb-2 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        Mis Reportes Personales
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        @forelse($misReportesAbiertos as $ticket)
            <div class="bg-white rounded-lg shadow-md border-l-4 border-blue-400 p-5 hover:shadow-lg transition">
                <div class="flex justify-between items-start mb-3">
                    <span class="text-[10px] font-bold uppercase {{ $ticket->prioridad == 'Alta' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }} px-2 py-1 rounded">
                        {{ $ticket->prioridad }}
                    </span>
                    <span class="text-[11px] text-gray-400 font-medium">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </div>
                
                <h4 class="font-bold text-lg text-slate-800">{{ $ticket->area }}</h4>
                
                <span class="text-[9px] bg-slate-100 text-slate-500 border border-slate-200 px-1.5 py-0.5 rounded uppercase font-bold inline-block mb-2">
                    Para: {{ $ticket->departamento_destino ?? 'Sistemas' }}
                </span>

                <p class="text-sm text-gray-600 line-clamp-3 italic">"{{ $ticket->descripcion }}"</p>
                
                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                    @if($ticket->archivo_path)
                        <span class="text-gray-400" title="Contiene evidencia">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </span>
                    @else
                        <span></span>
                    @endif
                    <a href="{{ route('tickets.show', $ticket->id) }}" class="text-sm font-bold text-[#041b32] hover:text-blue-600 transition">
                        Ver Ticket 
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-100 border-2 border-dashed border-slate-200 rounded-lg p-10 text-center">
                <p class="text-slate-500 font-medium">No has levantado ningún reporte pendiente.</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================ --}}
    {{-- SECCIÓN: Histórico Resueltos (combinado) --}}
    {{-- ============================================ --}}
    <h3 class="text-xl font-bold text-green-600 mb-4 border-b-2 border-green-200 pb-2">Histórico (Resueltos)</h3>
    <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[11px] tracking-wider">
                <tr>
                    <th class="py-4 px-6">ID</th>
                    <th class="py-4 px-6">Ubicación / Reporta</th>
                    <th class="py-4 px-6">Descripción</th>
                    <th class="py-4 px-6">Resolución TI</th>
                    <th class="py-4 px-6">Cierre</th>
                    <th class="py-4 px-6 text-right">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($ticketsResueltos->merge($misReportesResueltos)->sortByDesc('updated_at') as $ticket)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4 px-6 font-mono text-slate-400">#{{ $ticket->id }}</td>
                        <td class="py-4 px-6">
                            <div class="font-bold text-slate-800">{{ $ticket->area }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] text-slate-500">{{ $ticket->user->name }}</span>
                                <span class="text-[9px] text-blue-500 border border-blue-100 px-1 rounded font-bold uppercase">
                                    {{ $ticket->departamento_destino ?? 'Sistemas' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-6 max-w-xs">
                            <p class="truncate text-slate-600">{{ $ticket->descripcion }}</p>
                        </td>
                        <td class="py-4 px-6">
                            <p class="text-xs text-slate-700 line-clamp-1">{{ $ticket->notas_admin }}</p>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-green-600 font-medium">
                                {{ $ticket->fecha_resolucion ? \Carbon\Carbon::parse($ticket->fecha_resolucion)->format('d/m/Y') : $ticket->updated_at->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex bg-slate-100 text-[#041b32] text-[11px] font-bold py-1.5 px-3 rounded hover:bg-[#041b32] hover:text-white transition">
                                VER INFO
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 italic">No hay tickets resueltos en el historial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection