@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-4 no-print">
        <a href="{{ route('tickets.index') }}" class="text-sm text-slate-500 hover:text-[#041b32] flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al listado
        </a>

        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir
            </button>
            
            <a href="{{ route('tickets.download', $ticket->id) }}" class="bg-[#041b32] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:opacity-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Descargar PDF
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg border border-slate-200 overflow-hidden printable-area">
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center header-print">
            <div>
                <p class="text-blue-200 text-[11px] font-bold uppercase tracking-wider">Número de seguimiento</p>
                <h3 class="text-white font-bold text-2xl">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</h3>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $ticket->status === 'Abierto' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                {{ strtoupper($ticket->status) }}
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reportado por</h4>
                    <p class="text-slate-800 font-medium">{{ $ticket->user->name ?? 'Usuario desconocido' }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Área / Departamento</h4>
                    <p class="text-slate-800 font-medium">{{ $ticket->area }}</p>
                </div>
                @if($ticket->descripcion_ubicacion)
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Equipo / Ubicación</h4>
                    <p class="text-slate-800 font-medium">{{ $ticket->descripcion_ubicacion }}</p>
                </div>
                @endif
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Prioridad</h4>
                    <p class="font-bold {{ $ticket->prioridad == 'Alta' ? 'text-red-600' : 'text-slate-700' }}">
                        {{ $ticket->prioridad }}
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción</h4>
                    <p class="text-slate-700 bg-slate-50 p-3 rounded border border-slate-100 mt-1 italic">
                        "{{ $ticket->descripcion }}"
                    </p>
                </div>

                @if($ticket->status === 'Resuelto')
                <div class="pt-4 border-t border-slate-100 solution-print">
                    <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider">Solución de TI</h4>
                    <p class="text-slate-700 mt-1">{{ $ticket->notas_admin }}</p>
                    <p class="text-[10px] text-slate-400 mt-2 italic">Resuelto el: {{ $ticket->fecha_resolucion }}</p>
                </div>
                @endif
            </div>

            <div class="no-print-evidence">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">Evidencia Adjunta</h4>
                @if($ticket->archivo_path)
                    <div class="rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                        @php $extension = pathinfo($ticket->archivo_path, PATHINFO_EXTENSION); @endphp
                        @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp']))
                            <img src="{{ asset('storage/' . $ticket->archivo_path) }}" class="w-full h-auto" alt="Evidencia">
                        @elseif(in_array(strtolower($extension), ['mp4', 'mov', 'avi']))
                            <video controls class="w-full h-auto">
                                <source src="{{ asset('storage/' . $ticket->archivo_path) }}">
                                Tu navegador no soporta video.
                            </video>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-48 bg-slate-50 rounded-lg border-2 border-dashed border-slate-200 text-slate-400">
                        <p class="text-xs">Sin evidencia adjunta</p>
                    </div>
                @endif
            </div>
        </div>

        @if($ticket->status === 'Abierto' && auth()->user()->puedeResponderTickets())
            <div class="bg-slate-50 px-6 py-6 border-t border-slate-200 no-print">
                <form action="{{ route('tickets.resolve', $ticket->id) }}" method="POST">
                    @csrf 
                    @method('PUT')
                    <label class="block text-sm font-bold text-slate-700 mb-2">Notas de Resolución:</label>
                    <textarea name="notas_admin" required class="w-full border-slate-300 rounded-lg mb-4 text-sm p-3" rows="3" placeholder="¿Cómo lo solucionaste?"></textarea>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded shadow transition">
                        Marcar como Resuelto
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<style>
    @media print {
        nav, .no-print, button, form, footer, .mb-4 {
            display: none !important;
        }
        
        body {
            background: white !important;
            margin: 0;
            padding: 0;
            color: black !important;
        }

        .printable-area {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .header-print {
            background-color: #041b32 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print-evidence {
            display: block !important;
            width: 100% !important;
            margin-top: 20px !important;
        }

        .no-print-evidence img {
            max-width: 400px !important;
            height: auto !important;
            border: 1px solid #ccc !important;
            display: block;
            margin: 0 auto;
        }

        .grid {
            display: block !important;
        }
        
        .space-y-4 {
            margin-bottom: 20px;
        }
    }
</style>
@endsection