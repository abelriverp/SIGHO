@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Herramientas de Recepción</h2>
        <p class="text-sm text-slate-500">Analiza los reportes en PDF del sistema de reservas: valida los correos de los huéspedes y detecta posibles extensiones de estancia.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('recepcion.email.index') }}" class="block bg-white rounded-lg shadow p-6 hover:shadow-lg transition border-t-4 border-[#2980B9]">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">✉️</span>
                <h3 class="text-lg font-bold text-slate-800">Validador de Emails</h3>
            </div>
            <p class="text-sm text-slate-500">Sube el PDF de reservas y detecta correos falsos, con errores de dedo, o genéricos de OTA (Booking/Expedia).</p>
            <span class="inline-block mt-4 text-sm font-bold text-[#2980B9]">Analizar un PDF →</span>
        </a>

        <a href="{{ route('recepcion.extensiones.index') }}" class="block bg-white rounded-lg shadow p-6 hover:shadow-lg transition border-t-4 border-[#27AE60]">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">🔄</span>
                <h3 class="text-lg font-bold text-slate-800">Detector de Extensiones</h3>
            </div>
            <p class="text-sm text-slate-500">Compara el PDF de salidas contra el de llegadas y encuentra huéspedes que probablemente extendieron su estancia.</p>
            <span class="inline-block mt-4 text-sm font-bold text-[#27AE60]">Comparar dos PDF →</span>
        </a>

        <a href="{{ route('recepcion.perfiles.index') }}" class="block bg-white rounded-lg shadow p-6 hover:shadow-lg transition border-t-4 border-[#E67E22]">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">📥</span>
                <h3 class="text-lg font-bold text-slate-800">Correos de Llegada</h3>
            </div>
            <p class="text-sm text-slate-500">Sube el reporte "Profiles with e-mails" y revisa, reserva por reserva, si el correo del huésped existe: sufijo mal escrito, dominio inválido o rechazado por el propio servidor.</p>
            <span class="inline-block mt-4 text-sm font-bold text-[#E67E22]">Analizar un PDF →</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-3 border-b bg-slate-50 flex justify-between items-center">
                <h4 class="font-bold text-slate-700 text-sm">Últimos análisis de correos</h4>
                <a href="{{ route('recepcion.email.index') }}" class="text-xs text-blue-500 hover:underline">Ver todos</a>
            </div>
            @forelse($ultimasValidaciones as $v)
                <a href="{{ route('recepcion.email.show', $v) }}" class="flex justify-between items-center px-5 py-3 border-b border-slate-50 hover:bg-slate-50">
                    <div>
                        <p class="text-sm font-medium text-slate-800 truncate max-w-[220px]">{{ $v->archivo_nombre }}</p>
                        <p class="text-xs text-slate-400">{{ $v->created_at->diffForHumans() }} · {{ $v->total }} correos</p>
                    </div>
                    @if($v->falsos > 0)
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-red-100 text-red-700">{{ $v->falsos }} malos</span>
                    @else
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">OK</span>
                    @endif
                </a>
            @empty
                <p class="px-5 py-6 text-center text-sm text-slate-400">Todavía no hay análisis de correos.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-3 border-b bg-slate-50 flex justify-between items-center">
                <h4 class="font-bold text-slate-700 text-sm">Últimas comparaciones de extensiones</h4>
                <a href="{{ route('recepcion.extensiones.index') }}" class="text-xs text-blue-500 hover:underline">Ver todas</a>
            </div>
            @forelse($ultimasDetecciones as $d)
                <a href="{{ route('recepcion.extensiones.show', $d) }}" class="flex justify-between items-center px-5 py-3 border-b border-slate-50 hover:bg-slate-50">
                    <div>
                        <p class="text-sm font-medium text-slate-800 truncate max-w-[220px]">{{ $d->archivo_salidas }} vs {{ $d->archivo_llegadas }}</p>
                        <p class="text-xs text-slate-400">{{ $d->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded uppercase {{ $d->total_coincidencias > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-200 text-slate-500' }}">
                        {{ $d->total_coincidencias }} posibles
                    </span>
                </a>
            @empty
                <p class="px-5 py-6 text-center text-sm text-slate-400">Todavía no hay comparaciones de extensiones.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-3 border-b bg-slate-50 flex justify-between items-center">
                <h4 class="font-bold text-slate-700 text-sm">Últimos correos de llegada</h4>
                <a href="{{ route('recepcion.perfiles.index') }}" class="text-xs text-blue-500 hover:underline">Ver todos</a>
            </div>
            @forelse($ultimosPerfiles as $p)
                <a href="{{ route('recepcion.perfiles.show', $p) }}" class="flex justify-between items-center px-5 py-3 border-b border-slate-50 hover:bg-slate-50">
                    <div>
                        <p class="text-sm font-medium text-slate-800 truncate max-w-[220px]">{{ $p->archivo_nombre }}</p>
                        <p class="text-xs text-slate-400">{{ $p->created_at->diffForHumans() }} · {{ $p->total }} reservas</p>
                    </div>
                    @if($p->totalConProblema() > 0)
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-red-100 text-red-700">{{ $p->totalConProblema() }} con problema</span>
                    @else
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">OK</span>
                    @endif
                </a>
            @empty
                <p class="px-5 py-6 text-center text-sm text-slate-400">Todavía no hay correos de llegada analizados.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
