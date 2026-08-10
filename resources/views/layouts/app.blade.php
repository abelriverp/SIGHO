<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGHO - Hotel Emporio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        .hotel-blue { background-color: #041b32; }
        .nav-link:hover { color: #94a3b8; }
        .active-link { color: #60a5fa; }
        @media print { .no-print { display: none !important; } }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    <nav class="hotel-blue text-white shadow-lg no-print">
        <div class="max-w-[1400px] mx-auto px-4 md:px-6">
            <div class="flex justify-between items-center h-16">

                <div class="flex items-center gap-3 shrink-0 pr-4">
                    <img src="{{ asset('img/logo.jpg') }}" alt="Logo Hotel" class="h-9 md:h-10 w-auto object-contain">
                    <span class="font-bold text-lg md:text-xl tracking-widest uppercase">Mazatlán</span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden lg:flex space-x-3 xl:space-x-5 text-[13px] font-medium uppercase items-center">

                        @auth
                        @php
                            $user = auth()->user();

                            // Alertas proactivas (stock bajo + tickets vencidos), ya filtradas
                            // por propiedad (HotelScope) y por lo que este usuario puede ver.
                            $alertas = [];
                            try {
                                $alertas = app(\App\Services\AlertasService::class)->resumen($user);
                            } catch (\Exception $e) {
                                $alertas = ['stock_bajo' => collect(), 'stock_bajo_count' => 0, 'tickets_vencidos' => collect(), 'tickets_vencidos_count' => 0, 'tickets_por_vencer' => collect(), 'tickets_por_vencer_count' => 0];
                            }
                            $bajoStockCount = $alertas['stock_bajo_count'] ?? 0;
                            $totalAlertas = $bajoStockCount + ($alertas['tickets_vencidos_count'] ?? 0);
                        @endphp

                        {{-- 0. DASHBOARD --}}
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('dashboard') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                            Dashboard
                        </a>

                        {{-- 1. SEGUIMIENTO --}}
                        @if($user->tieneModulo('seguimiento_equipos_ti'))
                        <div class="relative group">
                            <a href="{{ route('assets.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('assets.*') && !request()->routeIs('assets.perifericos') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Seguimiento
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('assets.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Equipos</a>
                                <a href="{{ route('assets.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nuevo Equipo</a>
                            </div>
                        </div>
                        @endif

                        {{-- 2. INVENTARIO SUMINISTROS --}}
                        @if($user->tieneModulo('inventario_suministros'))
                        <div class="relative group">
                            <a href="{{ route('inventory.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('inventory.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Inventario
                                @if($bajoStockCount > 0)
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold">
                                        {{ $bajoStockCount }}
                                    </span>
                                @endif
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('inventory.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Suministros</a>
                                <a href="{{ route('inventory.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nuevo Artículo</a>
                                <a href="{{ route('inventory.reportes') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📊 Reportes</a>
                            </div>
                        </div>
                        @endif

                        {{-- 3. RED --}}
                        @if($user->tieneModulo('red_infraestructura'))
                        <div class="relative group">
                            <a href="{{ route('network.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('network.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Red
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('network.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Equipos</a>
                                <a href="{{ route('network.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nuevo Equipo</a>
                            </div>
                        </div>
                        @endif

                        {{-- 4. PERIFÉRICOS --}}
                        @if($user->tieneModulo('perifericos'))
                        <div class="relative group">
                            <a href="{{ route('assets.perifericos') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('assets.perifericos') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Periféricos
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('assets.perifericos') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Lista General</a>
                                <a href="{{ route('assets.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Registrar Equipo</a>
                            </div>
                        </div>
                        @endif

                        {{-- 5. ACTIVOS POR ÁREA --}}
                        @if($user->tieneModulo('inventario_habitaciones'))
                        <div class="relative group">
                            <a href="{{ route('activos.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('activos.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Activos
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-52 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('activos.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Inventario por Área</a>
                            </div>
                        </div>
                        @endif

                        {{-- 6. CONDOMINIOS / CHECK-IN --}}
                        @if($user->tieneModulo('condominios_checkin'))
                        <div class="relative group">
                            <a href="{{ route('condominios.estancias.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('condominios.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Condominios
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-52 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('condominios.estancias.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Check-in / Estancias</a>
                                <a href="{{ route('condominios.unidades.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Unidades</a>
                                <a href="{{ route('condominios.condominos.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Condóminos</a>
                                <a href="{{ route('condominios.reportes') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📊 Reportes</a>
                            </div>
                        </div>
                        @endif

                        {{-- 7. ADMIN (solo administradores) --}}
                        @if($user->isAdmin())
                        <div class="relative group">
                            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('admin.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Admin
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute right-0 md:left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Usuarios y Roles</a>
                                <a href="{{ route('admin.hoteles.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">🏨 Propiedades</a>
                                <a href="{{ route('admin.areas.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Catálogo de Áreas</a>
                                <a href="{{ route('admin.departamentos.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Departamentos (Tickets)</a>
                                <a href="{{ route('admin.activo-plantillas.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Áreas de Activos</a>
                                <a href="{{ route('admin.auditoria.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📋 Bitácora</a>
                            </div>
                        </div>
                        @endif

                        {{-- TICKETS (libre para todos los usuarios autenticados) --}}
                        @php
                            try {
                                $ticketBadgeCount = app(\App\Services\AlertasService::class)->ticketsAbiertosCount($user);
                            } catch (\Exception $e) {
                                $ticketBadgeCount = 0;
                            }
                        @endphp
                        <div class="relative group">
                            <a href="{{ route('tickets.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('tickets.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                Tickets
                                @if($ticketBadgeCount > 0)
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold">
                                        {{ $ticketBadgeCount }}
                                    </span>
                                @endif
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute right-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('tickets.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Panel de Reportes</a>
                                <a href="{{ route('tickets.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Levantar Ticket</a>
                                @if($user->puedeVerTodos() || $user->isTecnico())
                                    <a href="{{ route('tickets.reportes') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📊 Reportes / Corte de KPIs</a>
                                @endif
                                @if($user->puedeVerTodos())
                                    <a href="{{ route('tickets.categorias.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">⚙️ Categorías de solicitud</a>
                                    <a href="{{ route('tickets.sla.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">⏱️ Política de SLA</a>
                                    <a href="{{ route('puntos-qr.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📱 Portal de huésped (QR)</a>
                                @endif
                            </div>
                        </div>
                        @endauth

                    </div>

                    @auth
                        <div class="border-l border-[#0a2f57] pl-4 ml-1 flex items-center gap-4">
                            {{-- Selector de propiedad activa: solo Admin, y solo si hay
                                 más de una propiedad dada de alta (si solo hay una, no
                                 tiene caso mostrar un selector que no hace nada). --}}
                            @if(isset($hotelesDisponibles) && $hotelesDisponibles->count() > 1)
                                <form action="{{ route('admin.propiedad-activa.update') }}" method="POST">
                                    @csrf
                                    <select name="hotel_code" onchange="this.form.submit()" title="Propiedad activa"
                                            class="bg-[#0a2f57] text-white text-[11px] font-bold uppercase rounded px-2 py-1.5 border border-[#1a4a7a] focus:outline-none focus:ring-1 focus:ring-blue-400 cursor-pointer">
                                        @foreach($hotelesDisponibles as $hotel)
                                            <option value="{{ $hotel->hotel_code }}" {{ $hotelActivoSeleccionado === $hotel->hotel_code ? 'selected' : '' }}>
                                                🏨 {{ $hotel->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif

                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" type="button" class="relative flex items-center text-lg hover:text-blue-300 transition" title="Alertas">
                                    🔔
                                    @if($totalAlertas > 0)
                                        <span class="absolute -top-1 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold">
                                            {{ $totalAlertas > 99 ? '99+' : $totalAlertas }}
                                        </span>
                                    @endif
                                </button>
                                <div x-show="open" x-cloak class="absolute right-0 top-full mt-2 w-80 bg-white shadow-lg rounded-lg border-t-2 border-[#041b32] z-50 text-slate-600 normal-case max-h-96 overflow-y-auto">
                                    <div class="px-4 py-2 font-bold text-[#041b32] border-b border-gray-100 uppercase text-xs tracking-wide">Alertas</div>

                                    @if($totalAlertas === 0)
                                        <div class="px-4 py-6 text-center text-sm text-slate-400">Sin pendientes por ahora.</div>
                                    @endif

                                    @if(($alertas['stock_bajo_count'] ?? 0) > 0)
                                        <div class="px-4 pt-3 pb-1 text-xs font-bold text-slate-500 uppercase">Stock bajo ({{ $alertas['stock_bajo_count'] }})</div>
                                        @foreach($alertas['stock_bajo'] as $item)
                                            <a href="{{ route('inventory.index') }}" class="block px-4 py-2 hover:bg-blue-50 text-sm border-t border-gray-50">
                                                <span class="font-semibold">{{ $item->nombre }}</span>
                                                <span class="text-slate-400"> — {{ $item->cantidad }} / mín. {{ $item->stock_minimo }}</span>
                                            </a>
                                        @endforeach
                                    @endif

                                    @if(($alertas['tickets_vencidos_count'] ?? 0) > 0)
                                        <div class="px-4 pt-3 pb-1 text-xs font-bold text-slate-500 uppercase">Tickets vencidos ({{ $alertas['tickets_vencidos_count'] }})</div>
                                        @foreach($alertas['tickets_vencidos'] as $ticket)
                                            <a href="{{ route('tickets.show', $ticket->id) }}" class="block px-4 py-2 hover:bg-blue-50 text-sm border-t border-gray-50">
                                                <span class="font-semibold">#{{ $ticket->id }} · {{ $ticket->area }}</span>
                                                <span class="text-slate-400"> — {{ $ticket->prioridad }}, abierto desde {{ $ticket->created_at->diffForHumans() }}</span>
                                            </a>
                                        @endforeach
                                    @endif

                                    @if(($alertas['tickets_por_vencer_count'] ?? 0) > 0)
                                        <div class="px-4 pt-3 pb-1 text-xs font-bold text-amber-500 uppercase">Por vencer pronto ({{ $alertas['tickets_por_vencer_count'] }})</div>
                                        @foreach($alertas['tickets_por_vencer'] as $ticket)
                                            <a href="{{ route('tickets.show', $ticket->id) }}" class="block px-4 py-2 hover:bg-blue-50 text-sm border-t border-gray-50">
                                                <span class="font-semibold">#{{ $ticket->id }} · {{ $ticket->area }}</span>
                                                <span class="text-slate-400"> — {{ $ticket->prioridad }}, abierto desde {{ $ticket->created_at->diffForHumans() }}</span>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-[13px] uppercase hover:text-gray-300 transition bg-transparent border-0 cursor-pointer font-bold">Salir</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto mt-8 mb-16 px-4">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
