<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema TI - Hotel Emporio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hotel-blue { background-color: #041b32; }
        .nav-link:hover { color: #94a3b8; }
        .active-link { color: #60a5fa; } 
        @media print { .no-print { display: none !important; } }
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

                        @if(auth()->check() && (auth()->user()->email === 'admin@emporio.com' || auth()->user()->role === 'admin'))
                            
                            {{-- 1. SEGUIMIENTO --}}
                            <div class="relative group">
                                <a href="{{ route('assets.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('assets.*') && !request()->routeIs('assets.perifericos') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                    Seguimiento
                                    <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </a>
                                <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                    <a href="{{ route('assets.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Equipos TI</a>
                                    <a href="{{ route('assets.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nuevo Equipo</a>
                                </div>
                            </div>

                            {{-- 2. INVENTARIO SUMINISTROS --}}
                            <div class="relative group">
                                <a href="{{ route('inventory.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('inventory.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                    Inventario
                                    <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </a>
                                <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                    <a href="{{ route('inventory.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Suministros</a>
                                    <a href="{{ route('inventory.create') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nuevo Artículo</a>
                                </div>
                            </div>

                            {{-- 3. RED --}}
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

                            {{-- 4. PERIFÉRICOS (RECUPERADO) --}}
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

                            {{-- 5. HABITACIONES --}}
                            <div class="relative group">
                                <a href="{{ route('habitaciones.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('habitaciones.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                    Habitaciones
                                    <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </a>
                                <div class="absolute left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                    <a href="{{ route('habitaciones.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Ver Inventario (Pisos)</a>
                                    <a href="#" onclick="abrirModalCrear()" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">+ Nueva Habitación</a>
                                </div>
                            </div>
                            
                            {{-- 6. ADMIN --}}
                            <div class="relative group">
                                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-1 nav-link py-4 {{ request()->routeIs('admin.*') ? 'text-blue-400' : 'hover:text-blue-400' }} transition">
                                    Admin
                                    <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </a>
                                <div class="absolute right-0 md:left-0 top-full w-48 bg-white shadow-lg rounded-b-lg border-t-2 border-[#041b32] hidden group-hover:block z-50 text-slate-600">
                                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case">Usuarios y Roles</a>
                                    <a href="{{ route('admin.areas.index') }}" class="block px-4 py-3 hover:bg-blue-50 hover:text-[#041b32] transition normal-case border-t border-gray-100">Catálogo de Áreas</a>
                                </div>
                            </div>
                        @endif

                        {{-- TICKETS (ACCESIBLE PARA TODOS) --}}
                        @php
                            $ticketBadgeCount = 0;
                            try {
                                if (auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->email === 'admin@emporio.com')) {
                                    $ticketBadgeCount = \Illuminate\Support\Facades\DB::table('tickets')->where('status', 'Abierto')->count();
                                }
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
                            </div>
                        </div>

                    </div>

                    @if(auth()->check())
                        <div class="border-l border-[#0a2f57] pl-4 ml-1">
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-[13px] uppercase hover:text-gray-300 transition bg-transparent border-0 cursor-pointer font-bold">Salir</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto mt-8 mb-16 px-4">
        @yield('content')
    </main>
</body>
</html>