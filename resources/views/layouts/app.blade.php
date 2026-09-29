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

        /* Franja de módulos: sigue siendo scrolleable si algún día no cabe
           completa (ver comentario junto al div). A propósito NO se oculta
           del todo la barra: si se oculta por completo, un módulo que quede
           fuera de la vista se vuelve invisible sin ningún aviso de que hay
           más contenido a la derecha. En su lugar se deja una barra fina y
           del mismo tono del fondo — casi no se nota, pero sigue dando la
           pista de que se puede desplazar. */
        .nav-scroll { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.25) transparent; }
        .nav-scroll::-webkit-scrollbar { height: 4px; }
        .nav-scroll::-webkit-scrollbar-track { background: transparent; }
        .nav-scroll::-webkit-scrollbar-thumb { background-color: rgba(255,255,255,0.25); border-radius: 9999px; }
        .nav-scroll::-webkit-scrollbar-thumb:hover { background-color: rgba(255,255,255,0.45); }

        /* Estilo "pill" para los items del menú de escritorio: fondo sutil
           en hover y fondo/color distinto cuando el módulo está activo. */
        .nav-pill { display: flex; align-items: center; border-radius: 9999px; transition: background-color .15s ease, color .15s ease; color: #e2e8f0; }
        .nav-pill:hover { background-color: rgba(255,255,255,0.08); color: #93c5fd; }
        .nav-pill-active { background-color: rgba(96,165,250,0.16); color: #60a5fa; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    <nav class="hotel-blue text-white shadow-lg no-print">
        <div class="max-w-[1400px] mx-auto px-4 md:px-6" x-data="{ mobileMenuOpen: false }">
            <div class="flex justify-between items-center h-16">

                <div class="flex items-center gap-3 shrink-0 pr-2 md:pr-4">
                    <img src="{{ asset('img/logo.jpg') }}" alt="Logo Hotel" class="h-9 md:h-10 w-auto object-contain">
                    <span class="font-bold text-lg md:text-xl tracking-widest uppercase">Mazatlán</span>
                </div>

                {{-- flex-1 min-w-0 + justify-end: este bloque puede achicarse; el
                     que NUNCA se debe achicar es el cluster de la derecha
                     (selector de propiedad / alertas / Salir), por eso ese
                     lleva shrink-0 más abajo. Así, aunque con el tiempo se
                     agreguen más módulos y la lista ya no quepa completa, lo
                     que cede es el scroll de los links — no que "Salir" se
                     salga de la pantalla. --}}
                <div class="flex items-center gap-2 md:gap-4 flex-1 min-w-0 justify-end">
                    {{-- min-w-0 + overflow-x-auto: si algún día la lista de
                         módulos no cabe ni con el texto/espaciado reducido,
                         esta franja se vuelve scrolleable en vez de desbordar
                         la barra completa. --}}
                    <div class="hidden lg:flex min-w-0 overflow-x-auto nav-scroll flex-nowrap space-x-1 2xl:space-x-2 text-[12px] 2xl:text-[13px] font-medium uppercase items-center">

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
                        <a href="{{ route('dashboard') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('dashboard') ? 'nav-pill-active' : '' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        {{-- 1. SEGUIMIENTO --}}
                        @if($user->tieneModulo('seguimiento_equipos_ti'))
                        <div class="relative group">
                            <a href="{{ route('assets.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('assets.*') && !request()->routeIs('assets.perifericos') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="12" rx="1"></rect>
                                    <line x1="8" y1="20" x2="16" y2="20"></line>
                                    <line x1="12" y1="16" x2="12" y2="20"></line>
                                </svg>
                                <span>Seguimiento</span>
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
                            <a href="{{ route('inventory.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('inventory.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="4" y="8" width="16" height="12"></rect>
                                    <polyline points="4,8 12,4 20,8"></polyline>
                                    <line x1="12" y1="8" x2="12" y2="20"></line>
                                </svg>
                                <span>Inventario</span>
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
                            <a href="{{ route('network.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('network.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="4" y="14" width="3" height="6"></rect>
                                    <rect x="10.5" y="10" width="3" height="10"></rect>
                                    <rect x="17" y="5" width="3" height="15"></rect>
                                </svg>
                                <span>Red</span>
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
                            <a href="{{ route('assets.perifericos') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('assets.perifericos') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="3" y="6" width="11" height="7" rx="1"></rect>
                                    <line x1="6" y1="17" x2="11" y2="17"></line>
                                    <line x1="8.5" y1="13" x2="8.5" y2="17"></line>
                                    <circle cx="19" cy="12" r="3"></circle>
                                </svg>
                                <span>Periféricos</span>
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
                            <a href="{{ route('activos.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('activos.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="4" y="3" width="16" height="18"></rect>
                                    <rect x="10" y="13" width="4" height="8"></rect>
                                </svg>
                                <span>Activos</span>
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
                            <a href="{{ route('condominios.estancias.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('condominios.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="4" y="3" width="16" height="18"></rect>
                                    <rect x="7" y="6" width="3" height="3"></rect>
                                    <rect x="14" y="6" width="3" height="3"></rect>
                                    <rect x="7" y="12" width="3" height="3"></rect>
                                    <rect x="14" y="12" width="3" height="3"></rect>
                                </svg>
                                <span>Condominios</span>
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

                        {{-- 6.5. HERRAMIENTAS DE RECEPCIÓN --}}
                        @if($user->tieneModulo('herramientas_recepcion'))
                        <div class="relative group">
                            <a href="{{ route('recepcion.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('recepcion.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="14" rx="1"></rect>
                                    <polyline points="3,6 12,13 21,6"></polyline>
                                </svg>
                                <span>Recepción</span>
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute left-0 top-full w-56 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('recepcion.email.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">✉️ Validador de Emails</a>
                                <a href="{{ route('recepcion.extensiones.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">🔄 Detector de Extensiones</a>
                                <a href="{{ route('recepcion.perfiles.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📥 Correos de Llegada</a>
                            </div>
                        </div>
                        @endif

                        {{-- 7. ADMIN (solo administradores) --}}
                        @if($user->isAdmin())
                        <div class="relative group">
                            <a href="{{ route('admin.users.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('admin.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <polygon points="12,3 20,6 20,12 12,21 4,12 4,6"></polygon>
                                </svg>
                                <span>Admin</span>
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </a>
                            <div class="absolute right-0 md:left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Usuarios y Roles</a>
                                <a href="{{ route('admin.hoteles.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">🏨 Propiedades</a>
                                <a href="{{ route('admin.areas.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Catálogo de Áreas</a>
                                <a href="{{ route('admin.departamentos.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Departamentos (Tickets)</a>
                                <a href="{{ route('admin.activo-plantillas.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Áreas de Activos</a>
                                <a href="{{ route('admin.anuncios.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">📢 Anuncios</a>
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
                            <a href="{{ route('tickets.index') }}" class="nav-pill gap-1.5 px-2.5 2xl:px-3 py-2 {{ request()->routeIs('tickets.*') ? 'nav-pill-active' : '' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <rect x="3" y="6" width="18" height="12" rx="1"></rect>
                                    <line x1="12" y1="6" x2="12" y2="18" stroke-dasharray="2,2"></line>
                                </svg>
                                <span>Tickets</span>
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
                                @if($user->isTecnico() || $user->isAdmin())
                                    <a href="{{ route('tickets.operadores.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">👷 Operadores</a>
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
                        <div class="border-l border-[#0a2f57] pl-3 md:pl-4 ml-1 flex items-center gap-2 md:gap-4 shrink-0">
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

                            <a href="{{ route('novedades.index') }}" class="text-lg hover:text-blue-300 transition" title="Novedades">🆕</a>

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

                            {{-- Botón hamburguesa: solo visible por debajo de "lg", que es
                                 donde la franja de módulos de arriba está oculta (hidden
                                 lg:flex) y hoy no había NINGÚN reemplazo — el menú
                                 simplemente desaparecía en celular/tablet. Abre el cajón
                                 de abajo. --}}
                            <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                                class="lg:hidden p-1 -mr-1 text-white hover:text-blue-300 transition"
                                :aria-expanded="mobileMenuOpen.toString()" aria-label="Abrir menú">
                                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    @endauth
                </div>
            </div>

            {{-- ============================================================
                 MENÚ MÓVIL (cajón): mismos módulos y mismas condiciones de
                 acceso que la franja de escritorio de arriba, solo que
                 apilados en vertical en vez de submenús flotantes por hover
                 (que no funcionan con dedo). Se muestra por debajo de "lg",
                 controlado por el mismo Alpine mobileMenuOpen del botón.
            ============================================================ --}}
            @auth
            <div x-show="mobileMenuOpen" x-cloak @click.outside="mobileMenuOpen = false"
                class="lg:hidden border-t border-[#0a2f57] py-2 space-y-0.5 text-sm max-h-[75vh] overflow-y-auto">

                <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('dashboard') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>

                @if($user->tieneModulo('seguimiento_equipos_ti'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('assets.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('assets.*') && !request()->routeIs('assets.perifericos') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="1"></rect><line x1="8" y1="20" x2="16" y2="20"></line><line x1="12" y1="16" x2="12" y2="20"></line></svg>
                        Seguimiento
                    </a>
                    <a href="{{ route('assets.create') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">+ Nuevo Equipo</a>
                </div>
                @endif

                @if($user->tieneModulo('inventario_suministros'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('inventory.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('inventory.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="8" width="16" height="12"></rect><polyline points="4,8 12,4 20,8"></polyline><line x1="12" y1="8" x2="12" y2="20"></line></svg>
                        Inventario
                        @if($bajoStockCount > 0)
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold">{{ $bajoStockCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('inventory.create') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">+ Nuevo Artículo</a>
                    <a href="{{ route('inventory.reportes') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📊 Reportes</a>
                </div>
                @endif

                @if($user->tieneModulo('red_infraestructura'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('network.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('network.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="14" width="3" height="6"></rect><rect x="10.5" y="10" width="3" height="10"></rect><rect x="17" y="5" width="3" height="15"></rect></svg>
                        Red
                    </a>
                    <a href="{{ route('network.create') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">+ Nuevo Equipo</a>
                </div>
                @endif

                @if($user->tieneModulo('perifericos'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('assets.perifericos') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('assets.perifericos') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="6" width="11" height="7" rx="1"></rect><line x1="6" y1="17" x2="11" y2="17"></line><line x1="8.5" y1="13" x2="8.5" y2="17"></line><circle cx="19" cy="12" r="3"></circle></svg>
                        Periféricos
                    </a>
                    <a href="{{ route('assets.create') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Registrar Equipo</a>
                </div>
                @endif

                @if($user->tieneModulo('inventario_habitaciones'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('activos.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('activos.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18"></rect><rect x="10" y="13" width="4" height="8"></rect></svg>
                        Activos
                    </a>
                </div>
                @endif

                @if($user->tieneModulo('condominios_checkin'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('condominios.estancias.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('condominios.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18"></rect><rect x="7" y="6" width="3" height="3"></rect><rect x="14" y="6" width="3" height="3"></rect><rect x="7" y="12" width="3" height="3"></rect><rect x="14" y="12" width="3" height="3"></rect></svg>
                        Condominios
                    </a>
                    <a href="{{ route('condominios.unidades.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Unidades</a>
                    <a href="{{ route('condominios.condominos.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Condóminos</a>
                    <a href="{{ route('condominios.reportes') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📊 Reportes</a>
                </div>
                @endif

                @if($user->tieneModulo('herramientas_recepcion'))
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('recepcion.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('recepcion.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="1"></rect><polyline points="3,6 12,13 21,6"></polyline></svg>
                        Recepción
                    </a>
                    <a href="{{ route('recepcion.email.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">✉️ Validador de Emails</a>
                    <a href="{{ route('recepcion.extensiones.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">🔄 Detector de Extensiones</a>
                    <a href="{{ route('recepcion.perfiles.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📥 Correos de Llegada</a>
                </div>
                @endif

                @if($user->isAdmin())
                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('admin.users.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('admin.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polygon points="12,3 20,6 20,12 12,21 4,12 4,6"></polygon></svg>
                        Admin
                    </a>
                    <a href="{{ route('admin.hoteles.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">🏨 Propiedades</a>
                    <a href="{{ route('admin.areas.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Catálogo de Áreas</a>
                    <a href="{{ route('admin.departamentos.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Departamentos (Tickets)</a>
                    <a href="{{ route('admin.activo-plantillas.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Áreas de Activos</a>
                    <a href="{{ route('admin.anuncios.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📢 Anuncios</a>
                    <a href="{{ route('admin.auditoria.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📋 Bitácora</a>
                </div>
                @endif

                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('tickets.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-2 py-2.5 font-bold uppercase text-[13px] rounded {{ request()->routeIs('tickets.*') ? 'text-blue-400' : 'text-white hover:bg-[#0a2f57]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="1"></rect><line x1="12" y1="6" x2="12" y2="18" stroke-dasharray="2,2"></line></svg>
                        Tickets
                        @if($ticketBadgeCount > 0)
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold">{{ $ticketBadgeCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('tickets.create') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">Levantar Ticket</a>
                    @if($user->puedeVerTodos() || $user->isTecnico())
                        <a href="{{ route('tickets.reportes') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📊 Reportes / Corte de KPIs</a>
                    @endif
                    @if($user->isTecnico() || $user->isAdmin())
                        <a href="{{ route('tickets.operadores.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">👷 Operadores</a>
                    @endif
                    @if($user->puedeVerTodos())
                        <a href="{{ route('tickets.categorias.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">⚙️ Categorías de solicitud</a>
                        <a href="{{ route('tickets.sla.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">⏱️ Política de SLA</a>
                        <a href="{{ route('puntos-qr.index') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 text-[12px] text-slate-300 hover:text-white normal-case">📱 Portal de huésped (QR)</a>
                    @endif
                </div>

                <div class="border-t border-[#0a2f57]/60 pt-1 mt-1">
                    <a href="{{ route('novedades.index') }}" @click="mobileMenuOpen = false" class="block px-2 py-2.5 font-bold uppercase text-[13px] rounded text-white hover:bg-[#0a2f57]">🆕 Novedades</a>
                </div>
            </div>
            @endauth
        </div>
    </nav>

    <main class="container mx-auto mt-8 mb-16 px-4">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
