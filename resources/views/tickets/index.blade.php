@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto mt-8 px-4 pb-16">

    {{-- ============================================================ --}}
    {{-- ENCABEZADO --}}
    {{-- ============================================================ --}}
    <div class="flex justify-between items-center mb-6">
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
            <p class="text-slate-500 text-sm">Gestión de fallas y requerimientos técnicos.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="bg-[#041b32] text-white font-bold py-2.5 px-6 rounded-lg shadow hover:opacity-90 transition">
            + Nuevo Reporte
        </a>
    </div>

    {{-- ============================================================ --}}
    {{-- BARRA SUPERIOR: BUSCADOR DE TOKEN + FILTROS --}}
    {{-- ============================================================ --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-8 space-y-4">

        {{-- Buscador de Token --}}
        <form method="GET" action="{{ route('tickets.index') }}" class="flex gap-2 items-center">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    {{-- Ícono llave/escudo --}}
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
                <input type="text"
                       name="token"
                       value="{{ request('token') }}"
                       placeholder="Verificar token de auditoría — ej. HEMZT-2026-00042-R9KF"
                       class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#041b32] uppercase placeholder:normal-case placeholder:font-sans"
                       autocomplete="off"
                       oninput="this.value = this.value.toUpperCase()">
            </div>
            <button type="submit"
                    class="bg-[#041b32] text-white text-sm font-bold px-5 py-2.5 rounded-lg hover:opacity-90 transition whitespace-nowrap">
                Verificar Token
            </button>
            @if(request('token'))
                <a href="{{ route('tickets.index') }}"
                   class="text-sm text-slate-500 hover:text-red-500 font-medium px-2 py-2.5 whitespace-nowrap transition">
                    ✕ Limpiar
                </a>
            @endif
        </form>

        {{-- Divisor --}}
        <div class="border-t border-slate-100"></div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-wrap gap-3 items-end">
            {{-- Usuario que reportó --}}
            <div class="flex flex-col gap-1 min-w-[160px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Usuario</label>
                <input type="text"
                       name="usuario"
                       value="{{ request('usuario', $filtros['usuario'] ?? '') }}"
                       placeholder="Nombre o correo"
                       class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
            </div>

            {{-- Área / ubicación --}}
            <div class="flex flex-col gap-1 min-w-[160px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Área / Ubicación</label>
                <input type="text"
                       name="area"
                       value="{{ request('area', $filtros['area'] ?? '') }}"
                       placeholder="Ej. Recepción, Piso 3"
                       class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
            </div>

            {{-- Departamento destino --}}
            <div class="flex flex-col gap-1 min-w-[160px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Departamento</label>
                <select name="departamento_destino"
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
                    <option value="">Todos</option>
                    {{-- Se listan todos (incluyendo inactivos) para no perder el filtro
                         de tickets antiguos que quedaron con un departamento ya desactivado. --}}
                    @foreach(\App\Models\Departamento::orderBy('nombre')->pluck('nombre') as $dpto)
                        <option value="{{ $dpto }}" {{ (request('departamento_destino') === $dpto || (isset($filtros['departamento_destino']) && $filtros['departamento_destino'] === $dpto)) ? 'selected' : '' }}>
                            {{ $dpto }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Estatus --}}
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estatus</label>
                <select name="status"
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
                    <option value="">Todos</option>
                    @foreach(['Abierto', 'Resuelto', 'Cerrado', 'Reabierto'] as $st)
                        <option value="{{ $st }}" {{ (request('status') === $st || (isset($filtros['status']) && $filtros['status'] === $st)) ? 'selected' : '' }}>
                            {{ $st }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Prioridad --}}
            <div class="flex flex-col gap-1 min-w-[120px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Prioridad</label>
                <select name="prioridad"
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
                    <option value="">Todas</option>
                    @foreach(['Alta', 'Media', 'Baja'] as $p)
                        <option value="{{ $p }}" {{ (request('prioridad') === $p || (isset($filtros['prioridad']) && $filtros['prioridad'] === $p)) ? 'selected' : '' }}>
                            {{ $p }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Fecha desde --}}
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Desde</label>
                <input type="date"
                       name="fecha_desde"
                       value="{{ request('fecha_desde', $filtros['fecha_desde'] ?? '') }}"
                       class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
            </div>

            {{-- Fecha hasta --}}
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Hasta</label>
                <input type="date"
                       name="fecha_hasta"
                       value="{{ request('fecha_hasta', $filtros['fecha_hasta'] ?? '') }}"
                       class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
            </div>

            {{-- Orden --}}
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Orden</label>
                <select name="orden"
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#041b32]">
                    <option value="recientes" {{ (request('orden', $filtros['orden'] ?? 'recientes')) === 'recientes' ? 'selected' : '' }}>
                        Más recientes primero
                    </option>
                    <option value="antiguos" {{ (request('orden', $filtros['orden'] ?? 'recientes')) === 'antiguos' ? 'selected' : '' }}>
                        Más antiguos primero
                    </option>
                </select>
            </div>

            {{-- Botones --}}
            <div class="flex gap-2 items-end">
                <button type="submit"
                        class="bg-slate-700 text-white text-sm font-bold px-5 py-2 rounded-lg hover:opacity-90 transition">
                    Filtrar
                </button>
                @if(collect(request()->only(['departamento_destino','status','prioridad','fecha_desde','fecha_hasta','usuario','area']))->filter()->isNotEmpty())
                    <a href="{{ route('tickets.index') }}"
                       class="text-sm text-slate-500 hover:text-red-500 font-medium px-3 py-2 transition">
                        ✕ Quitar filtros
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- RESULTADO DE BÚSQUEDA DE TOKEN --}}
    {{-- ============================================================ --}}
    @if(isset($tokenBuscado))
        @if(isset($tokenNoEncontrado) && $tokenNoEncontrado)
            <div class="mb-8 bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-4">
                <div class="text-red-500 mt-0.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-red-700">Token no encontrado</p>
                    <p class="text-sm text-red-600 font-mono mt-1">{{ $tokenBuscado }}</p>
                    <p class="text-sm text-red-500 mt-1">Este token no existe en el sistema. Verifique que esté escrito correctamente.</p>
                </div>
            </div>

        @elseif(isset($tokenSinAcceso) && $tokenSinAcceso)
            <div class="mb-8 bg-yellow-50 border border-yellow-200 rounded-xl p-6 flex items-start gap-4">
                <div class="text-yellow-500 mt-0.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-yellow-700">Sin acceso</p>
                    <p class="text-sm text-yellow-600">El token existe pero no tienes permisos para ver ese ticket.</p>
                </div>
            </div>

        @elseif(isset($ticketEncontrado) && $ticketEncontrado)
            @php
                $tk = $ticketEncontrado;
                $esTokenR = $tk->token_resolucion === $tokenBuscado;
                $esTokenC = $tk->token_confirmacion === $tokenBuscado;
                $tipoToken = $esTokenR ? 'Resolución' : 'Confirmación';
                $colorToken = $esTokenR ? 'blue' : 'green';
            @endphp
            <div class="mb-8 bg-green-50 border border-green-200 rounded-xl overflow-hidden">
                {{-- Header resultado --}}
                <div class="bg-green-600 text-white px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-bold">Token válido — {{ $tipoToken }}</span>
                    </div>
                    <span class="font-mono text-sm bg-green-700 px-3 py-1 rounded-lg">{{ $tokenBuscado }}</span>
                </div>

                {{-- Cuerpo resultado --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ticket</p>
                            <p class="font-mono font-bold text-slate-800">#{{ str_pad($tk->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Área / Ubicación</p>
                            <p class="font-semibold text-slate-800">{{ $tk->area }}</p>
                            @if($tk->descripcion_ubicacion)
                                <p class="text-sm text-slate-500">{{ $tk->descripcion_ubicacion }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Departamento</p>
                            <p class="text-slate-700">{{ $tk->departamento_destino }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reportó</p>
                            <p class="text-slate-700">{{ $tk->user->name }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Estatus actual</p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold
                                {{ $tk->status === 'Cerrado'   ? 'bg-green-100 text-green-700'  : '' }}
                                {{ $tk->status === 'Resuelto'  ? 'bg-blue-100 text-blue-700'    : '' }}
                                {{ $tk->status === 'Abierto'   ? 'bg-red-100 text-red-700'      : '' }}
                                {{ $tk->status === 'Reabierto' ? 'bg-orange-100 text-orange-700': '' }}">
                                {{ $tk->status }}
                            </span>
                        </div>

                        @if($esTokenR && $tk->fecha_resolucion)
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fecha de Resolución</p>
                            <p class="text-slate-700">{{ \Carbon\Carbon::parse($tk->fecha_resolucion)->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif

                        @if($esTokenC && $tk->fecha_confirmacion)
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fecha de Confirmación</p>
                            <p class="text-slate-700">{{ \Carbon\Carbon::parse($tk->fecha_confirmacion)->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif

                        @if($tk->notas_admin)
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Solución aplicada</p>
                            <p class="text-slate-700 text-sm">{{ $tk->notas_admin }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Token de Resolución</p>
                            <p class="font-mono text-xs text-blue-700 bg-blue-50 px-2 py-1 rounded inline-block">{{ $tk->token_resolucion ?? '—' }}</p>
                        </div>
                        @if($tk->token_confirmacion)
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Token de Confirmación</p>
                            <p class="font-mono text-xs text-green-700 bg-green-50 px-2 py-1 rounded inline-block">{{ $tk->token_confirmacion }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 pb-5">
                    <a href="{{ route('tickets.show', $tk->id) }}"
                       class="inline-block bg-[#041b32] text-white text-sm font-bold px-5 py-2 rounded-lg hover:opacity-90 transition">
                        Ver detalle completo del ticket →
                    </a>
                </div>
            </div>
        @endif
    @endif

    {{-- ============================================================ --}}
    {{-- SECCIÓN: Tickets a Resolver (admin / gerente / tecnico) --}}
    {{-- ============================================================ --}}
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
                        <span class="text-[10px] font-bold uppercase {{ $ticket->prioridad == 'Alta' ? 'bg-red-100 text-red-700' : ($ticket->prioridad == 'Media' ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-600') }} px-2 py-1 rounded">
                            {{ $ticket->prioridad }}
                        </span>
                        <span class="text-[11px] text-gray-400 font-medium">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-mono text-[11px] font-bold text-slate-400">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <h4 class="font-bold text-lg text-slate-800">{{ $ticket->area }}</h4>

                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-xs text-blue-600 font-semibold">Reportó: {{ $ticket->user->name }}</p>
                        <span class="text-[9px] bg-slate-100 text-slate-500 border border-slate-200 px-1.5 py-0.5 rounded uppercase font-bold">
                            {{ $ticket->departamento_destino ?? 'Sistemas' }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 line-clamp-3 italic">"{{ $ticket->descripcion }}"</p>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                        @if($ticket->archivo_path || $ticket->archivos_evidencia)
                            <span class="text-gray-400" title="Contiene evidencia">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </span>
                        @else
                            <span></span>
                        @endif
                        <a href="{{ route('tickets.show', $ticket->id) }}"
                           class="text-sm font-bold text-[#041b32] hover:text-blue-600 transition">
                            Ver Ticket →
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-100 border-2 border-dashed border-slate-200 rounded-lg p-10 text-center">
                    <p class="text-slate-500 font-medium">No hay tickets pendientes por resolver.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- SECCIÓN: Mis Reportes Personales --}}
    {{-- ============================================================ --}}
    <h3 class="text-xl font-bold text-blue-600 mb-4 border-b-2 border-blue-200 pb-2 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Mis Reportes Personales
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        @forelse($misReportesAbiertos as $ticket)
            <div class="bg-white rounded-lg shadow-md border-l-4 border-blue-400 p-5 hover:shadow-lg transition">
                <div class="flex justify-between items-start mb-3">
                    <span class="text-[10px] font-bold uppercase {{ $ticket->prioridad == 'Alta' ? 'bg-red-100 text-red-700' : ($ticket->prioridad == 'Media' ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-600') }} px-2 py-1 rounded">
                        {{ $ticket->prioridad }}
                    </span>
                    <span class="text-[11px] text-gray-400 font-medium">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div class="flex items-center gap-2 mb-1">
                    <span class="font-mono text-[11px] font-bold text-slate-400">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>

                <h4 class="font-bold text-lg text-slate-800">{{ $ticket->area }}</h4>

                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[9px] bg-slate-100 text-slate-500 border border-slate-200 px-1.5 py-0.5 rounded uppercase font-bold">
                        Para: {{ $ticket->departamento_destino ?? 'Sistemas' }}
                    </span>
                    {{-- Badge de estatus --}}
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase
                        {{ $ticket->status === 'Resuelto'  ? 'bg-blue-100 text-blue-700'    : '' }}
                        {{ $ticket->status === 'Reabierto' ? 'bg-orange-100 text-orange-700': '' }}
                        {{ $ticket->status === 'Abierto'   ? 'bg-red-100 text-red-600'      : '' }}">
                        {{ $ticket->status }}
                    </span>
                </div>

                <p class="text-sm text-gray-600 line-clamp-3 italic">"{{ $ticket->descripcion }}"</p>

                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                    @if($ticket->archivo_path)
                        <span class="text-gray-400" title="Contiene evidencia">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                    @else
                        <span></span>
                    @endif
                    <a href="{{ route('tickets.show', $ticket->id) }}"
                       class="text-sm font-bold text-[#041b32] hover:text-blue-600 transition">
                        Ver Ticket →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-100 border-2 border-dashed border-slate-200 rounded-lg p-10 text-center">
                <p class="text-slate-500 font-medium">No tienes reportes personales pendientes.</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================================ --}}
    {{-- SECCIÓN: Histórico Resueltos --}}
    {{-- ============================================================ --}}
    <h3 class="text-xl font-bold text-green-600 mb-4 border-b-2 border-green-200 pb-2">Histórico (Resueltos / Cerrados)</h3>
    <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[11px] tracking-wider">
                <tr>
                    <th class="py-4 px-6">ID</th>
                    <th class="py-4 px-6">Ubicación / Reporta</th>
                    <th class="py-4 px-6">Descripción</th>
                    <th class="py-4 px-6">Resolución</th>
                    <th class="py-4 px-6">Token</th>
                    <th class="py-4 px-6">Cierre</th>
                    <th class="py-4 px-6 text-right">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($ticketsResueltos->merge($misReportesResueltos)->unique('id')->sortByDesc('updated_at') as $ticket)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-4 px-6 font-mono text-slate-400">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</td>
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
                            <div class="space-y-1">
                                @if($ticket->token_resolucion)
                                    <span class="block font-mono text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded">
                                        {{ $ticket->token_resolucion }}
                                    </span>
                                @endif
                                @if($ticket->token_confirmacion)
                                    <span class="block font-mono text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded">
                                        {{ $ticket->token_confirmacion }}
                                    </span>
                                @endif
                                @if(!$ticket->token_resolucion && !$ticket->token_confirmacion)
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-green-600 font-medium">
                                {{ $ticket->fecha_resolucion
                                    ? \Carbon\Carbon::parse($ticket->fecha_resolucion)->format('d/m/Y')
                                    : $ticket->updated_at->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('tickets.show', $ticket->id) }}"
                               class="inline-flex bg-slate-100 text-[#041b32] text-[11px] font-bold py-1.5 px-3 rounded hover:bg-[#041b32] hover:text-white transition">
                                VER INFO
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 italic">No hay tickets en el historial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection