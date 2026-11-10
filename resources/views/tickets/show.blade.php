@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8">

    {{-- Barra superior --}}
    <div class="flex justify-between items-center mb-4 no-print">
        {{-- $volverUrl: URL de /tickets con los filtros/orden que tenía el
             usuario antes de entrar aquí (ver TicketController::show()).
             Cae a la lista sin filtros si no venía navegando desde ahí. --}}
        <a href="{{ $volverUrl ?? route('tickets.index') }}" class="text-sm text-slate-500 hover:text-[#041b32] flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al listado
        </a>
        <div class="flex gap-2 items-center">
            <button onclick="window.print()" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir
            </button>

            {{-- Botón PDF con opción de bitácora (solo técnico/admin) --}}
            @if(auth()->user()->puedeResponderTickets())
                <div class="relative" id="pdf-dropdown-wrap">
                    <button onclick="togglePdfMenu()"
                        class="bg-[#041b32] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Descargar PDF
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="pdf-menu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-10 p-3">
                        <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Opciones de PDF</p>
                        <a href="{{ route('tickets.download', $ticket->id) }}"
                            class="block text-sm text-slate-700 hover:text-[#041b32] py-1.5 border-b border-slate-100">
                            📄 Sin bitácora
                        </a>
                        <a href="{{ route('tickets.download', $ticket->id) }}?bitacora=1"
                            class="block text-sm text-slate-700 hover:text-[#041b32] py-1.5 mt-1">
                            📋 Con bitácora completa
                        </a>
                    </div>
                </div>
            @else
                <a href="{{ route('tickets.download', $ticket->id) }}" class="bg-[#041b32] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar PDF
                </a>
            @endif

            {{-- Eliminar ticket (solo Administrador) --}}
            @if(auth()->user()->isAdmin())
                <form action="{{ route('tickets.destroy', $ticket->id) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar este ticket permanentemente? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-red-100 transition">
                        🗑️ Eliminar
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Mensajes de sesión --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm no-print">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 rounded mb-4 text-sm no-print">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm no-print">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-lg border border-slate-200 overflow-hidden printable-area">

        {{-- Encabezado con número y status --}}
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center header-print">
            <div>
                <p class="text-blue-200 text-[11px] font-bold uppercase tracking-wider">Número de seguimiento</p>
                <h3 class="text-white font-bold text-2xl">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</h3>
            </div>
            @php
                $statusColor = match($ticket->status) {
                    'Abierto'   => 'bg-yellow-100 text-yellow-700',
                    'Resuelto'  => 'bg-blue-100 text-blue-700',
                    'Reabierto' => 'bg-orange-100 text-orange-700',
                    'Cerrado'   => 'bg-green-100 text-green-700',
                    default     => 'bg-slate-100 text-slate-700',
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $statusColor }}">
                {{ strtoupper($ticket->status) }}
            </span>
        </div>

        {{-- ============================================ --}}
        {{-- AVISO: recordatorio sin explicación            --}}
        {{-- Se envió un recordatorio y nadie de soporte ha --}}
        {{-- comentado desde entonces. Bloquea "Resolver"   --}}
        {{-- (ver TicketController::resolve()) hasta que    --}}
        {{-- alguien del departamento explique qué pasa.     --}}
        {{-- ============================================ --}}
        @php
            $requiereJustificacion = $ticket->requiereJustificacionRecordatorio();
        @endphp
        @if($requiereJustificacion && in_array($ticket->status, ['Abierto', 'Reabierto']) && auth()->user()->puedeResponderTickets())
            <div class="px-6 py-4 bg-amber-50 border-b-4 border-amber-400 no-print">
                <p class="text-sm font-bold text-amber-800">⚠️ Este ticket tiene un recordatorio pendiente sin explicación.</p>
                <p class="text-xs text-amber-700 mt-1">
                    El usuario avisó que lleva días sin atención visible. Antes de poder resolverlo, cuenta en la bitácora qué está pasando (ej. "sin atender por falta de material", "en espera de refacción", "ya se agendó visita")
                    @if(auth()->user()->isAdmin())
                        — como Administrador puedes resolverlo directamente si lo prefieres.
                    @else
                        .
                    @endif
                </p>
                <a href="#agregar-nota" class="inline-block mt-2 text-xs font-bold text-amber-800 underline hover:text-amber-900">Comentar ahora ↓</a>
            </div>
        @endif

        {{-- Datos del ticket --}}
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
                @if($ticket->ubicacion || $ticket->asset)
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Habitación / área y equipo</h4>
                    <p class="text-slate-800 font-medium">
                        @if($ticket->ubicacion)
                            @if(auth()->user()->tieneModulo('seguimiento_equipos_ti'))
                                <a href="{{ route('ubicaciones.show', $ticket->ubicacion) }}" class="text-blue-800 hover:underline">{{ $ticket->ubicacion->etiqueta }}</a>
                            @else
                                {{ $ticket->ubicacion->etiqueta }}
                            @endif
                        @endif
                        @if($ticket->ubicacion && $ticket->asset) · @endif
                        @if($ticket->asset)
                            @if(auth()->user()->tieneModulo('seguimiento_equipos_ti'))
                                <a href="{{ route('assets.show', $ticket->asset->id) }}" class="font-mono text-blue-800 hover:underline">{{ $ticket->asset->nomenclatura }}</a>
                            @else
                                <span class="font-mono">{{ $ticket->asset->nomenclatura }}</span>
                            @endif
                            <span class="text-xs text-slate-500">{{ $ticket->asset->tipo_equipo }} {{ $ticket->asset->marca }} {{ $ticket->asset->modelo }}</span>
                        @endif
                    </p>
                </div>
                @endif
                @if($ticket->descripcion_ubicacion)
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Equipo / Ubicación</h4>
                    <p class="text-slate-800 font-medium">{{ $ticket->descripcion_ubicacion }}</p>
                </div>
                @endif
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dirigido a</h4>
                    <p class="text-slate-800 font-medium">{{ $ticket->departamento_destino }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Prioridad</h4>
                    <p class="font-bold {{ $ticket->prioridad == 'Alta' ? 'text-red-600' : ($ticket->prioridad == 'Media' ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $ticket->prioridad }}
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción</h4>
                    <p class="text-slate-700 bg-slate-50 p-3 rounded border border-slate-100 mt-1 italic">
                        "{{ $ticket->descripcion }}"
                    </p>
                </div>

                @if($ticket->campos_extra)
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Detalle de la solicitud</h4>
                    <div class="bg-slate-50 border border-slate-100 rounded p-3 space-y-1 text-sm text-slate-700">
                        @php
                            // campos_extra ya guarda un snapshot legible (etiquetas, no
                            // IDs) tomado en el momento en que se creó el ticket — ver
                            // TicketController::store(). Así el detalle sigue siendo
                            // legible aunque el catálogo (App\Models\TicketCategoria) se
                            // edite o borre después.
                            $ce = $ticket->campos_extra;
                        @endphp
                        <p><span class="font-bold">Tipo de solicitud:</span> {{ $ce['categoria_label'] ?? '—' }}</p>
                        @if(!empty($ce['subcategoria_label']))
                            <p><span class="font-bold">Sistema:</span> {{ $ce['subcategoria_label'] }}</p>
                        @endif
                        @foreach(($ce['campos'] ?? []) as $campo)
                            <p><span class="font-bold">{{ $campo['label'] ?? '?' }}:</span> {{ $campo['valor'] ?? '' }}</p>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(in_array($ticket->status, ['Resuelto', 'Cerrado']))
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider">Solución aplicada por soporte</h4>
                    <p class="text-slate-700 mt-1">{{ $ticket->notas_admin }}</p>
                    <p class="text-[10px] text-slate-400 mt-2 italic">
                        Resuelto el: {{ $ticket->fecha_resolucion?->format('d/m/Y H:i') }}
                        @if($ticket->resolvedBy)
                            — por {{ $ticket->resolvedBy->name }}
                        @endif
                        @if($ticket->operador)
                            (operador: {{ $ticket->operador->nombre }})
                        @endif
                    </p>
                </div>
                @endif

                @if($ticket->status === 'Reabierto' && $ticket->motivo_reapertura)
                <div class="pt-4 border-t border-orange-100">
                    <h4 class="text-xs font-bold text-orange-600 uppercase tracking-wider">Motivo de reapertura</h4>
                    <p class="text-slate-700 mt-1">{{ $ticket->motivo_reapertura }}</p>
                </div>
                @endif

                @if($ticket->status === 'Cerrado' && $ticket->confirmado_usuario)
                <div class="pt-4 border-t border-green-100">
                    <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider">Confirmación del usuario</h4>
                    <p class="text-slate-700 mt-1 text-sm">✅ Problema confirmado como solucionado por {{ $ticket->user->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-1 italic">{{ $ticket->fecha_confirmacion?->format('d/m/Y H:i') }}</p>
                    @if($ticket->csat_calificacion)
                        <p class="mt-2 text-sm"><span class="text-amber-500">{{ str_repeat('★', $ticket->csat_calificacion) }}</span><span class="text-slate-300">{{ str_repeat('★', 5 - $ticket->csat_calificacion) }}</span> <span class="text-xs text-slate-500">({{ $ticket->csat_calificacion }}/5)</span></p>
                        @if($ticket->csat_comentario)<p class="text-xs text-slate-600 italic mt-1">"{{ $ticket->csat_comentario }}"</p>@endif
                    @endif
                </div>
                @endif

                {{-- ============================================ --}}
                {{-- TOKENS DE AUDITORÍA (visible en show)       --}}
                {{-- ============================================ --}}
                @if($ticket->token_resolucion)
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tokens de Auditoría</h4>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-2 flex items-start gap-2">
                        <span class="text-blue-400 mt-0.5">🔐</span>
                        <div>
                            <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wide">Token de Resolución</p>
                            <p class="font-mono text-xs font-bold text-[#041b32]">{{ $ticket->token_resolucion }}</p>
                            <p class="text-[9px] text-slate-400">IP: {{ $ticket->ip_resolucion ?? '—' }}</p>
                        </div>
                    </div>
                    @if($ticket->token_confirmacion)
                    <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 flex items-start gap-2">
                        <span class="text-green-500 mt-0.5">✅</span>
                        <div>
                            <p class="text-[10px] text-green-600 font-bold uppercase tracking-wide">Token de Confirmación</p>
                            <p class="font-mono text-xs font-bold text-[#041b32]">{{ $ticket->token_confirmacion }}</p>
                            <p class="text-[9px] text-slate-400">IP: {{ $ticket->ip_confirmacion ?? '—' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- ============================================ --}}
            {{-- COLUMNA DERECHA: EVIDENCIA ORIGINAL + FOTOS --}}
            {{-- ============================================ --}}
            <div class="space-y-4 no-print-evidence">

                {{-- Evidencia original al crear el ticket (varios archivos, o el archivo
                     único de tickets creados antes de que se permitiera adjuntar más de uno) --}}
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">
                        Evidencia Adjunta
                        @if($ticket->archivos_evidencia && count($ticket->archivos_evidencia) > 1)
                            <span class="text-slate-300 font-normal">({{ count($ticket->archivos_evidencia) }})</span>
                        @endif
                    </h4>
                    @php
                        $rutasEvidencia = $ticket->archivos_evidencia ?: ($ticket->archivo_path ? [$ticket->archivo_path] : []);
                    @endphp
                    @if(count($rutasEvidencia) > 0)
                        <div class="grid {{ count($rutasEvidencia) > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                            @foreach($rutasEvidencia as $ruta)
                                @php $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION)); @endphp
                                <div class="rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'webp']))
                                        <img src="{{ Storage::url($ruta) }}"
                                             alt="Evidencia"
                                             class="w-full h-auto object-cover max-h-64 cursor-pointer"
                                             onclick="document.getElementById('modal-img').src=this.src; document.getElementById('img-modal').classList.remove('hidden')">
                                    @elseif(in_array($extension, ['mp4', 'mov']))
                                        <video controls class="w-full max-h-64">
                                            <source src="{{ Storage::url($ruta) }}" type="video/{{ $extension }}">
                                        </video>
                                    @else
                                        {{-- PDF/TXT (u otro archivo no previsualizable): no se puede
                                             incrustar como imagen/video, se ofrece como descarga. --}}
                                        <a href="{{ Storage::url($ruta) }}" target="_blank" rel="noopener"
                                           class="flex flex-col items-center justify-center gap-1 h-40 bg-slate-50 hover:bg-slate-100 transition text-slate-500">
                                            <span class="text-3xl">{{ $extension === 'pdf' ? '📄' : ($extension === 'txt' ? '📝' : '📎') }}</span>
                                            <span class="text-xs font-bold uppercase">Ver {{ strtoupper($extension) }}</span>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-center text-slate-400 py-1">Clic en una foto para ampliar</p>
                    @else
                        <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 text-center">
                            <p class="text-xs text-slate-400">Sin evidencia adjunta al crear</p>
                        </div>
                    @endif
                </div>

                {{-- ============================================ --}}
                {{-- FOTOS DE RESOLUCIÓN (ya guardadas)          --}}
                {{-- ============================================ --}}
                @if($ticket->fotos_resolucion && count($ticket->fotos_resolucion) > 0)
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">
                        Fotografías de Resolución
                        <span class="text-slate-300 font-normal">({{ count($ticket->fotos_resolucion) }}/4)</span>
                    </h4>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($ticket->fotos_resolucion as $foto)
                        <div class="relative border border-slate-200 rounded-lg overflow-hidden bg-slate-50 aspect-video cursor-pointer hover:opacity-90 transition"
                             onclick="document.getElementById('modal-img').src='{{ asset('storage/' . $foto) }}'; document.getElementById('img-modal').classList.remove('hidden')">
                            <img src="{{ asset('storage/' . $foto) }}"
                                 class="w-full h-full object-cover"
                                 alt="Foto resolución {{ $loop->iteration }}">
                            <span class="absolute bottom-1 right-1 bg-black/50 text-white text-[9px] px-1.5 py-0.5 rounded">
                                {{ $loop->iteration }}/{{ count($ticket->fotos_resolucion) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-center text-slate-400 mt-1">Clic en cada foto para ampliar</p>
                </div>
                @endif

            </div>
        </div>

        {{-- Modal imagen (evidencia y fotos resolución) --}}
        <div id="img-modal" class="hidden fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center no-print"
             onclick="this.classList.add('hidden')">
            <img id="modal-img" src="" alt="Evidencia ampliada" class="max-w-[90vw] max-h-[90vh] rounded shadow-2xl">
        </div>

        {{-- ============================================ --}}
        {{-- FASE 2: conocimiento, historial y material   --}}
        {{-- ============================================ --}}
        @if(auth()->user()->puedeResponderTickets())
            <div class="px-6 py-5 border-t border-slate-200 grid grid-cols-1 lg:grid-cols-3 gap-6 no-print">
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">📚 Base de conocimiento</h4>
                    @forelse($articulosSugeridos as $art)
                        <a href="{{ route('conocimiento.show', $art->id) }}" target="_blank" class="block text-sm text-blue-800 hover:underline py-1">{{ $art->titulo }}</a>
                    @empty
                        <p class="text-xs text-slate-400">Sin artículos para este departamento.</p>
                    @endforelse
                    <div class="mt-2 flex gap-3 text-xs">
                        <a href="{{ route('conocimiento.index', ['departamento' => $ticket->departamento_destino]) }}" target="_blank" class="text-slate-500 underline">Buscar más</a>
                        @if(in_array($ticket->status, ['Resuelto', 'Cerrado']))
                            <a href="{{ route('conocimiento.create', ['ticket' => $ticket->id]) }}" class="text-green-700 font-bold underline">+ Convertir esta solución en artículo</a>
                        @endif
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">🕘 Tickets anteriores de este equipo o lugar</h4>
                    @if(!$ticket->asset_id && !$ticket->ubicacion_id)
                        <p class="text-xs text-slate-400">El ticket no está ligado a un equipo ni a una ubicación.</p>
                    @else
                        @forelse($historialRelacionado as $h)
                            <a href="{{ route('tickets.show', $h->id) }}" class="block text-xs py-1 hover:bg-slate-50 rounded">
                                <span class="font-bold text-[#041b32]">#{{ $h->id }}</span>
                                <span class="text-slate-400">{{ $h->created_at->format('d/m/y') }} · {{ $h->status }}</span>
                                <span class="block text-slate-600 truncate">{{ \Illuminate\Support\Str::limit($h->descripcion, 70) }}</span>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">Es el primer ticket de este equipo o lugar.</p>
                        @endforelse
                    @endif
                </div>

                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">🧰 Material usado</h4>
                    @forelse($ticket->materiales as $m)
                        <p class="text-xs text-slate-700 py-0.5">{{ rtrim(rtrim(number_format($m->cantidad, 2), '0'), '.') }} {{ $m->item?->unidad }} · {{ $m->item?->nombre ?? 'Artículo eliminado' }} <span class="text-slate-400">({{ $m->usuario?->name }})</span></p>
                    @empty
                        <p class="text-xs text-slate-400">Sin material registrado.</p>
                    @endforelse
                    @if($ticket->status !== 'Cerrado' && $articulosInventario->isNotEmpty())
                        <form action="{{ route('tickets.material.store', $ticket->id) }}" method="POST" class="mt-2 flex gap-2 items-start">
                            @csrf
                            <select name="inventario_item_id" required class="flex-1 min-w-0 border border-slate-300 rounded text-xs p-1.5">
                                <option value="">Artículo…</option>
                                @foreach($articulosInventario as $it)
                                    <option value="{{ $it->id }}">{{ $it->nombre }} ({{ rtrim(rtrim(number_format($it->cantidad, 2), '0'), '.') }} {{ $it->unidad }})</option>
                                @endforeach
                            </select>
                            <input type="number" name="cantidad" step="0.01" min="0.01" value="1" required class="w-16 border border-slate-300 rounded text-xs p-1.5">
                            <button type="submit" class="bg-[#041b32] text-white text-xs font-bold px-3 py-1.5 rounded">Usar</button>
                        </form>
                        @error('cantidad')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @endif
                </div>

                {{-- Fase 5: ligar a un problema (causa común de varios tickets) --}}
                @php
                    $problemasTicket = $ticket->problemas()->get(['problemas.id', 'titulo', 'estado']);
                    $problemasAbiertos = \App\Models\Problema::whereNotIn('estado', ['resuelto', 'cerrado'])
                        ->whereNotIn('id', $problemasTicket->pluck('id'))->orderByDesc('id')->get(['id', 'titulo']);
                @endphp
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">🧩 Problema</h4>
                    @foreach($problemasTicket as $pt)
                        <a href="{{ route('problemas.show', $pt->id) }}" class="block text-xs py-0.5 text-[#041b32] hover:underline">P-{{ $pt->id }} · {{ $pt->titulo }} <span class="text-slate-400">({{ \App\Models\Problema::ESTADOS[$pt->estado] ?? $pt->estado }})</span></a>
                    @endforeach
                    @if($problemasAbiertos->isNotEmpty())
                        <form action="{{ route('problemas.ligar', $ticket->id) }}" method="POST" class="mt-2 flex gap-2">
                            @csrf
                            <select name="problema_id" required class="flex-1 min-w-0 border border-slate-300 rounded text-xs p-1.5">
                                <option value="">Ligar a problema abierto…</option>
                                @foreach($problemasAbiertos as $pa)
                                    <option value="{{ $pa->id }}">P-{{ $pa->id }} · {{ \Illuminate\Support\Str::limit($pa->titulo, 45) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-[#041b32] text-white text-xs font-bold px-3 py-1.5 rounded">Ligar</button>
                        </form>
                    @endif
                    <a href="{{ route('problemas.create', ['ticket' => $ticket->id]) }}" class="inline-block mt-2 text-xs font-bold text-[#041b32] hover:underline">+ Abrir problema con este ticket</a>
                </div>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE RESOLVER (técnico/admin)              --}}
        {{-- Oculto para técnicos mientras $requiereJustificacion --}}
        {{-- sea true (ver aviso arriba) — Admin sí lo ve, puede  --}}
        {{-- saltarse el requisito. Coincide con el bloqueo real  --}}
        {{-- del lado del servidor en TicketController::resolve().--}}
        {{-- ============================================ --}}
        @if(in_array($ticket->status, ['Abierto', 'Reabierto']) && auth()->user()->puedeResponderTickets() && auth()->id() !== $ticket->user_id && !($requiereJustificacion && !auth()->user()->isAdmin()))
            <div class="px-6 py-6 border-t border-slate-200 bg-slate-50 no-print"
                 x-data="fotoPreview()">
                <h4 class="text-sm font-bold text-slate-700 mb-4">Registrar solución y cerrar ticket</h4>
                <form action="{{ route('tickets.resolve', $ticket->id) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    {{-- Fase 2: respuestas predefinidas y artículo de conocimiento usado --}}
                    @if($respuestas->isNotEmpty())
                        <select onchange="if (this.value) { const t = this.form.querySelector('[name=notas_admin]'); t.value = (t.value ? t.value + '\n' : '') + this.value; this.value = ''; }"
                            class="w-full border border-slate-300 rounded-lg text-xs p-2 mb-2 focus:outline-none focus:border-[#041b32]">
                            <option value="">💬 Insertar respuesta predefinida…</option>
                            @foreach($respuestas as $r)
                                <option value="{{ $r->texto }}">{{ $r->titulo }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Notas de resolución --}}
                    <textarea name="notas_admin" required rows="3"
                        class="w-full border border-slate-300 rounded-lg mb-4 text-sm p-3 focus:outline-none focus:border-[#041b32]"
                        placeholder="Describe detalladamente cómo se solucionó el problema..."></textarea>

                    @if($articulosSugeridos->isNotEmpty())
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">¿Te sirvió un artículo de la base de conocimiento? <span class="text-slate-400 font-normal">(opcional)</span></label>
                            <select name="articulo_id" class="w-full border border-slate-300 rounded-lg text-sm p-2 focus:outline-none focus:border-[#041b32]">
                                <option value="">— Ninguno —</option>
                                @foreach($articulosSugeridos as $art)
                                    <option value="{{ $art->id }}">{{ $art->titulo }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Operador que ejecutó el trabajo (opcional, solo lista del propio técnico) --}}
                    @if(auth()->user()->isTecnico())
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">
                                Operador que ejecutó el trabajo
                                <span class="text-slate-400 font-normal">(opcional, solo para dejar constancia)</span>
                            </label>
                            @if($misOperadores->isEmpty())
                                <p class="text-xs text-slate-400">
                                    No tienes operadores registrados.
                                    <a href="{{ route('tickets.operadores.index') }}" class="text-blue-500 hover:underline">Agregar uno</a>.
                                </p>
                            @else
                                <select name="operador_id" class="w-full border border-slate-300 rounded-lg text-sm p-2 focus:outline-none focus:border-[#041b32]">
                                    <option value="">— Ninguno —</option>
                                    @foreach($misOperadores as $op)
                                        <option value="{{ $op->id }}">{{ $op->nombre }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endif

                    {{-- Upload fotos de resolución --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            Fotografías de evidencia de resolución
                            <span class="text-slate-400 font-normal">(máx. 4 — JPG, PNG, WEBP — 4 MB c/u)</span>
                        </label>
                        <input type="file"
                               name="fotos_resolucion[]"
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               multiple
                               @change="previsualizar($event)"
                               class="block w-full text-xs text-slate-600
                                      file:mr-3 file:py-1.5 file:px-4 file:rounded
                                      file:border-0 file:text-xs file:font-semibold
                                      file:bg-[#041b32] file:text-white hover:file:opacity-90
                                      cursor-pointer border border-slate-200 rounded-lg p-1">

                        {{-- Previews en grid 2x2 --}}
                        <div class="grid grid-cols-2 gap-2 mt-3" x-show="previews.length > 0" x-cloak>
                            <template x-for="(src, i) in previews" :key="i">
                                <div class="relative border border-slate-200 rounded-lg overflow-hidden bg-slate-100 aspect-video">
                                    <img :src="src" class="w-full h-full object-cover">
                                    <span class="absolute top-1 left-1 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded"
                                          x-text="'Foto ' + (i + 1)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-400 mb-3 italic">
                        🔐 Al guardar se generará automáticamente un token de resolución para auditoría.
                    </p>

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded shadow transition">
                        ✅ Marcar como Resuelto
                    </button>
                </form>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE: ENVIAR RECORDATORIO                    --}}
        {{-- Para cuando el ticket lleva varios días abierto --}}
        {{-- sin seguimiento visible. Distinto de la escalación --}}
        {{-- automática por SLA: esto lo dispara una persona,  --}}
        {{-- avisa de nuevo al departamento responsable. Quién  --}}
        {{-- puede: el reportante, o Admin/Gerente aunque no    --}}
        {{-- sea su ticket (ver TicketPolicy::recordatorio()).  --}}
        {{-- Umbral: 2 días desde la creación. Cooldown: 24h    --}}
        {{-- entre uno y el siguiente — aplica igual para todos. --}}
        {{-- ============================================ --}}
        @if(in_array($ticket->status, ['Abierto', 'Reabierto']) && (auth()->id() === $ticket->user_id || auth()->user()->puedeVerTodos()))
            @php
                $diasAbierto = $ticket->created_at->diffInDays(now());
                $cumpleUmbralRecordatorio = $diasAbierto >= 2;
                $horasDesdeUltimoRecordatorio = $ticket->ultimo_recordatorio_at
                    ? $ticket->ultimo_recordatorio_at->diffInHours(now())
                    : null;
                $enCooldownRecordatorio = $horasDesdeUltimoRecordatorio !== null && $horasDesdeUltimoRecordatorio < 24;
                $puedeRecordar = $cumpleUmbralRecordatorio && !$enCooldownRecordatorio;
            @endphp
            <div class="px-6 py-6 border-t border-slate-200 bg-slate-50 no-print">
                <h4 class="text-sm font-bold text-slate-700 mb-1">
                    {{ auth()->id() === $ticket->user_id ? '¿No ves seguimiento en tu ticket?' : '¿Sin seguimiento visible?' }}
                </h4>
                <p class="text-xs text-slate-500 mb-3">
                    Lleva {{ $diasAbierto }} {{ $diasAbierto == 1 ? 'día' : 'días' }} abierto.
                    @if($puedeRecordar)
                        Puedes avisarle de nuevo al departamento responsable.
                    @elseif($enCooldownRecordatorio)
                        Ya se envió un recordatorio; podrás enviar otro en {{ 24 - $horasDesdeUltimoRecordatorio }} horas.
                    @else
                        Podrás enviar un recordatorio a partir de los 2 días de abierto.
                    @endif
                </p>
                <form action="{{ route('tickets.recordatorio', $ticket->id) }}" method="POST"
                      onsubmit="return confirm('¿Enviar un recordatorio al departamento responsable?');">
                    @csrf
                    <button type="submit" @disabled(!$puedeRecordar)
                        class="w-full font-bold py-2 rounded shadow transition {{ $puedeRecordar ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }}">
                        🔔 Enviar recordatorio
                    </button>
                </form>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE: REASIGNAR A OTRO DEPARTAMENTO        --}}
        {{-- Para cuando el ticket se enrutó al departamento          --}}
        {{-- equivocado. Un técnico solo puede reasignar tickets de   --}}
        {{-- su propio departamento; un Admin puede reasignar         --}}
        {{-- cualquiera. Siempre exige un motivo (mínimo 10           --}}
        {{-- caracteres) para que quede registrado en la bitácora.    --}}
        {{-- ============================================ --}}
        @php
            $departamentosDisponibles = \App\Models\Departamento::activos()->orderBy('nombre')->pluck('nombre');
            $maxReasignaciones = 2;
            $puedeReasignar = in_array($ticket->status, ['Abierto', 'Reabierto'])
                && auth()->user()->puedeResponderTickets()
                && auth()->id() !== $ticket->user_id
                && (!auth()->user()->isTecnico() || $ticket->departamento_destino === auth()->user()->departamento)
                && ($ticket->veces_reasignado < $maxReasignaciones || auth()->user()->isAdmin());
        @endphp
        @if($puedeReasignar)
            <div class="px-6 py-4 border-t border-slate-200 no-print">
                <details>
                    <summary class="text-xs text-slate-500 font-bold cursor-pointer hover:text-slate-700">
                        🔀 ¿Este ticket no le corresponde a "{{ $ticket->departamento_destino }}"? Reasignar a otro departamento
                    </summary>
                    <form action="{{ route('tickets.reasignar', $ticket->id) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PATCH')

                        <label class="block text-xs font-bold text-slate-600 mb-1">Departamento correcto</label>
                        <select name="departamento_destino_nuevo" required
                            class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 mb-3 text-sm focus:outline-none focus:border-[#041b32]">
                            <option value="" disabled selected>Selecciona departamento...</option>
                            @foreach($departamentosDisponibles as $depto)
                                @if($depto !== $ticket->departamento_destino)
                                    <option value="{{ $depto }}">{{ $depto }}</option>
                                @endif
                            @endforeach
                        </select>

                        <label class="block text-xs font-bold text-slate-600 mb-1">Motivo de la reasignación</label>
                        <textarea name="motivo_reasignacion" required rows="2" minlength="10"
                            class="w-full border border-slate-300 rounded-lg text-sm p-3 mb-2 focus:outline-none focus:border-slate-400"
                            placeholder="Explica por qué este ticket no le corresponde a tu departamento (ej. 'es una falla eléctrica, no de sistemas')..."></textarea>

                        <p class="text-[10px] text-slate-400 mb-3 italic">
                            Quedará registrado en la bitácora y auditoría del ticket, con tu nombre y el motivo.
                            @if(!auth()->user()->isAdmin())
                                Un ticket solo puede reasignarse {{ $maxReasignaciones }} veces; después requiere que un Administrador lo mueva.
                            @endif
                        </p>

                        <button type="submit"
                            onclick="return confirm('¿Reasignar este ticket a otro departamento?')"
                            class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 rounded shadow transition text-sm">
                            Reasignar ticket
                        </button>
                    </form>
                </details>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE: DESHACER RESOLUCIÓN (error de dedo)  --}}
        {{-- Para quien resolvió el ticket (o un admin), cuando se marcó --}}
        {{-- como resuelto por equivocación y el usuario aún no confirmó --}}
        {{-- ni rechazó la solución. --}}
        {{-- ============================================ --}}
        @php
            $puedeDeshacerResolucion = $ticket->status === 'Resuelto'
                && auth()->user()->puedeResponderTickets()
                && (auth()->id() === $ticket->resolved_by || auth()->user()->isAdmin());
        @endphp
        @if($puedeDeshacerResolucion)
            <div class="px-6 py-4 border-t border-slate-200 no-print">
                <details>
                    <summary class="text-xs text-slate-500 font-bold cursor-pointer hover:text-slate-700">↩️ ¿Marcaste este ticket como resuelto por error? Deshacer resolución</summary>
                    <form action="{{ route('tickets.deshacer-resolucion', $ticket->id) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="motivo" required rows="2" minlength="10"
                            class="w-full border border-slate-300 rounded-lg text-sm p-3 mb-2 focus:outline-none focus:border-slate-400"
                            placeholder="Explica brevemente por qué se deshace (ej. 'se marcó por error, era otro ticket')..."></textarea>
                        <button type="submit"
                            onclick="return confirm('¿Deshacer la resolución de este ticket? Volverá a estado \'Abierto\' y quedará registrado en la bitácora.')"
                            class="w-full bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 rounded shadow transition text-sm">
                            Deshacer resolución
                        </button>
                    </form>
                </details>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE: CONFIRMACIÓN DEL USUARIO             --}}
        {{-- ============================================ --}}
        @if($ticket->status === 'Resuelto' && auth()->id() === $ticket->user_id)
            <div class="px-6 py-6 border-t border-blue-100 bg-blue-50 no-print">
                <h4 class="text-sm font-bold text-blue-800 mb-1">¿Tu problema fue solucionado?</h4>
                <p class="text-xs text-blue-600 mb-4">El equipo de soporte ha registrado una solución. Por favor confirma si el problema fue resuelto satisfactoriamente.</p>

                <form action="{{ route('tickets.confirmar', $ticket->id) }}" method="POST" class="mb-3" x-data="{ estrellas: {{ (int) old('csat_calificacion', 0) }} }">
                    @csrf
                    <div class="bg-white border border-blue-200 rounded-lg p-3 mb-3">
                        <p class="text-xs font-bold text-blue-800 mb-2">¿Cómo calificas la atención?</p>
                        <div class="flex gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer text-3xl leading-none" :class="estrellas >= {{ $i }} ? 'text-amber-400' : 'text-slate-300'">
                                    <input type="radio" name="csat_calificacion" value="{{ $i }}" class="sr-only" x-model.number="estrellas" required>★
                                </label>
                            @endfor
                        </div>
                        @error('csat_calificacion')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
                        <textarea name="csat_comentario" rows="2" maxlength="1000" class="w-full border border-slate-200 rounded text-sm p-2 focus:outline-none focus:border-[#041b32]" placeholder="Comentario (opcional)">{{ old('csat_comentario') }}</textarea>
                    </div>
                    <button type="submit"
                        onclick="return confirm('¿Confirmas que el problema fue solucionado? Al confirmar, el ticket será cerrado definitivamente.')"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow transition text-sm">
                        ✅ Sí, confirmo que el problema fue solucionado
                    </button>
                </form>

                <div class="bg-white border border-blue-200 rounded-lg p-3 mb-4 text-[11px] text-slate-500 italic">
                    Al confirmar, declaro bajo mi responsabilidad que el problema reportado ha sido solucionado satisfactoriamente. Las credenciales de acceso utilizadas en este sistema son personales, únicas e intransferibles, por lo que esta confirmación se realiza bajo mi identidad como <strong>{{ auth()->user()->name }}</strong>.
                </div>

                <details class="mt-2">
                    <summary class="text-xs text-red-600 font-bold cursor-pointer hover:text-red-800">❌ El problema NO fue resuelto — Reabrir ticket</summary>
                    <form action="{{ route('tickets.reabrir', $ticket->id) }}" method="POST" class="mt-3">
                        @csrf
                        <textarea name="motivo_reapertura" required rows="3" minlength="10"
                            class="w-full border border-red-200 rounded-lg text-sm p-3 mb-2 focus:outline-none focus:border-red-400"
                            placeholder="Explica por qué el problema no quedó resuelto..."></textarea>
                        <button type="submit"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 rounded shadow transition text-sm">
                            Reabrir Ticket
                        </button>
                    </form>
                </details>
            </div>
        @endif

        {{-- ============================================ --}}
        {{-- BLOQUE: BITÁCORA DE NOTAS                   --}}
        {{-- ============================================ --}}
        <div class="px-6 py-6 border-t border-slate-200 no-print">
            <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Bitácora de Seguimiento
            </h4>

            {{-- Lista de notas --}}
            <div class="space-y-3 mb-6">
                @forelse($ticket->notas as $nota)
                    @if($nota->esPublica() || auth()->user()->puedeResponderTickets())
                        @php
                            $esAutor      = auth()->id() === $nota->user_id;
                            $minutosDesde = now()->diffInMinutes($nota->created_at);
                            $puedeEditar  = $esAutor && $minutosDesde <= 15 && auth()->user()->puedeResponderTickets() && $nota->esEditable();
                        @endphp
                        <div class="flex gap-3" id="nota-{{ $nota->id }}">
                            <div class="flex-shrink-0 w-2 rounded-full {{ $nota->esPublica() ? 'bg-green-400' : 'bg-slate-400' }}"></div>
                            <div class="flex-1 {{ $nota->esInterna() ? 'bg-slate-50 border border-slate-200' : 'bg-green-50 border border-green-100' }} rounded-lg p-3">
                                <div class="flex justify-between items-start mb-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-slate-700">{{ $nota->user->name ?? 'Sistema' }}</span>
                                        @if($nota->esInterna())
                                            <span class="text-[9px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded uppercase font-bold">🔒 Interna</span>
                                        @else
                                            <span class="text-[9px] bg-green-200 text-green-700 px-1.5 py-0.5 rounded uppercase font-bold">🌐 Pública</span>
                                        @endif
                                        @if($nota->es_automatica)
                                            <span class="text-[9px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded uppercase font-bold" title="Registro generado automáticamente por el sistema, no editable">🔒 Sistema</span>
                                        @endif
                                        @if($nota->editado_at)
                                            <span class="text-[9px] text-slate-400 italic">✏️ Editada {{ $nota->editado_at->format('d/m H:i') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-400">{{ $nota->created_at->format('d/m/Y H:i') }}</span>
                                        @if($puedeEditar)
                                            <button onclick="toggleEditNota({{ $nota->id }})"
                                                class="text-[10px] text-blue-500 hover:text-blue-700 font-bold border border-blue-200 rounded px-2 py-0.5 hover:bg-blue-50 transition">
                                                ✏️ Editar
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Texto de la nota --}}
                                <p class="text-sm text-slate-700" id="texto-nota-{{ $nota->id }}">{{ $nota->nota }}</p>

                                {{-- Formulario de edición (oculto por defecto) --}}
                                @if($puedeEditar)
                                    <form action="{{ route('tickets.notas.edit', [$ticket->id, $nota->id]) }}"
                                          method="POST"
                                          id="form-nota-{{ $nota->id }}"
                                          class="hidden mt-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="nota" rows="2"
                                            class="w-full border border-blue-200 rounded text-sm p-2 mb-2 focus:outline-none focus:border-blue-400"
                                            >{{ $nota->nota }}</textarea>
                                        <div class="flex gap-2">
                                            <button type="submit"
                                                class="bg-blue-600 text-white text-xs font-bold px-4 py-1.5 rounded hover:bg-blue-700 transition">
                                                Guardar
                                            </button>
                                            <button type="button"
                                                onclick="toggleEditNota({{ $nota->id }})"
                                                class="bg-slate-200 text-slate-700 text-xs font-bold px-4 py-1.5 rounded hover:bg-slate-300 transition">
                                                Cancelar
                                            </button>
                                        </div>
                                        @php $tiempoRestante = 15 - (int)$minutosDesde; @endphp
                                        <p class="text-[9px] text-slate-400 mt-1 italic">
                                            ⏱ Puedes editar por {{ $tiempoRestante }} minuto(s) más
                                        </p>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-slate-400 italic text-center py-4">No hay notas de seguimiento aún.</p>
                @endforelse
            </div>

            {{-- Formulario agregar nota (solo técnico/admin/gerente) --}}
            @if(auth()->user()->puedeResponderTickets() || auth()->user()->isGerente())
                <form id="agregar-nota" action="{{ route('tickets.notas.store', $ticket->id) }}" method="POST" class="border-t border-slate-100 pt-4 scroll-mt-24">
                    @csrf
                    <label class="block text-xs font-bold text-slate-600 mb-2">
                        Agregar nota de seguimiento
                        @if($requiereJustificacion)
                            <span class="text-amber-600 font-normal normal-case">— explica aquí por qué el ticket sigue en pausa</span>
                        @endif
                    </label>
                    @if($respuestas->isNotEmpty())
                        <select onchange="if (this.value) { const t = this.form.querySelector('[name=nota]'); t.value = (t.value ? t.value + '\n' : '') + this.value; this.value = ''; }"
                            class="w-full border border-slate-300 rounded-lg text-xs p-2 mb-2 focus:outline-none focus:border-[#041b32]">
                            <option value="">💬 Insertar respuesta predefinida…</option>
                            @foreach($respuestas as $r)
                                <option value="{{ $r->texto }}">{{ $r->titulo }}</option>
                            @endforeach
                        </select>
                    @endif
                    <textarea name="nota" required rows="2"
                        class="w-full border border-slate-300 rounded-lg text-sm p-3 mb-2 focus:outline-none focus:border-[#041b32]"
                        placeholder="Escribe una nota de seguimiento..."></textarea>
                    <div class="flex items-center gap-3">
                        <select name="tipo" class="border border-slate-300 rounded-lg text-xs px-3 py-2 focus:outline-none focus:border-[#041b32]">
                            <option value="publica">🌐 Pública (visible para el usuario)</option>
                            <option value="interna">🔒 Interna (solo personal de soporte)</option>
                        </select>
                        <button type="submit" class="bg-[#041b32] text-white text-xs font-bold px-6 py-2 rounded-lg hover:opacity-90 transition">
                            Agregar Nota
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>
</div>

{{-- ================================================================ --}}
{{-- SECCIÓN DE IMPRESIÓN: FOTOS EN 4 CUADRANTES (solo en print)     --}}
{{-- ================================================================ --}}
@if($ticket->fotos_resolucion && count($ticket->fotos_resolucion) > 0)
@php
    $fotosImpresion = array_slice($ticket->fotos_resolucion, 0, 4);
    while (count($fotosImpresion) < 4) $fotosImpresion[] = null;
@endphp
<div class="print-fotos-page">
    <div class="print-fotos-header">
        <span>EVIDENCIA FOTOGRÁFICA — Ticket #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>

    <div class="print-fotos-grid">
        @foreach($fotosImpresion as $idx => $foto)
        <div class="print-foto-celda">
            @if($foto)
                <img src="{{ asset('storage/' . $foto) }}" alt="Foto {{ $idx + 1 }}">
                <p class="print-foto-label">Foto {{ $idx + 1 }} de {{ count($ticket->fotos_resolucion) }}</p>
            @else
                <p class="print-foto-vacia">— Sin imagen —</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Tokens al pie de la página de fotos --}}
    <div class="print-tokens">
        <div class="print-token-col">
            <p class="print-token-label">TOKEN DE RESOLUCIÓN</p>
            <p class="print-token-valor">{{ $ticket->token_resolucion ?? 'N/A' }}</p>
            <p class="print-token-meta">IP: {{ $ticket->ip_resolucion ?? '—' }}</p>
        </div>
        <div class="print-token-col">
            <p class="print-token-label">TOKEN DE CONFIRMACIÓN</p>
            <p class="print-token-valor">{{ $ticket->token_confirmacion ?? 'Pendiente de confirmación' }}</p>
            <p class="print-token-meta">IP: {{ $ticket->ip_confirmacion ?? '—' }}</p>
        </div>
    </div>
</div>
@endif

<style>
    /* ============================================================ */
    /* ESTILOS DE PANTALLA                                          */
    /* ============================================================ */
    [x-cloak] { display: none !important; }

    .print-fotos-page { display: none; }

    /* ============================================================ */
    /* ESTILOS DE IMPRESIÓN                                         */
    /* ============================================================ */
    @media print {
        /* Ocultar UI de navegación */
        nav, .no-print, button, form, footer, .mb-4 { display: none !important; }
        body { background: white !important; margin: 0; padding: 0; color: black !important; }

        /* Tarjeta principal */
        .printable-area {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            width: 100% !important;
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
        .grid { display: block !important; }
        .space-y-4 { margin-bottom: 20px; }

        /* -------------------------------------------------------- */
        /* PÁGINA DE FOTOS EN 4 CUADRANTES                         */
        /* -------------------------------------------------------- */
        .print-fotos-page {
            display: block !important;
            page-break-before: always;
            width: 100%;
            height: 100vh;
            padding: 16px;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .print-fotos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #041b32;
            padding-bottom: 8px;
            margin-bottom: 12px;
            font-size: 11px;
            font-weight: bold;
            color: #041b32;
        }

        /* Grid 2x2 que ocupa casi toda la hoja */
        .print-fotos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 8px;
            width: 100%;
            height: calc(100vh - 140px);
        }

        .print-foto-celda {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .print-foto-celda img {
            max-width: 100%;
            max-height: calc(100% - 20px);
            object-fit: contain;
            display: block;
        }

        .print-foto-label {
            margin: 4px 0 0;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }

        .print-foto-vacia {
            color: #d1d5db;
            font-size: 10px;
            font-style: italic;
        }

        /* Tokens al pie */
        .print-tokens {
            display: flex;
            border: 1px solid #e5e7eb;
            background: #f0f4ff;
            margin-top: 10px;
            padding: 6px 12px;
        }

        .print-token-col {
            flex: 1;
            padding: 0 8px;
            border-right: 1px solid #e5e7eb;
        }

        .print-token-col:last-child { border-right: none; }

        .print-token-label {
            margin: 0;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: bold;
        }

        .print-token-valor {
            margin: 2px 0 0;
            font-size: 11px;
            font-family: monospace;
            font-weight: bold;
            color: #041b32;
        }

        .print-token-meta {
            margin: 1px 0 0;
            font-size: 8px;
            color: #9ca3af;
        }
    }
</style>

<script>
function fotoPreview() {
    return {
        previews: [],
        previsualizar(event) {
            const archivos = Array.from(event.target.files).slice(0, 4);
            if (event.target.files.length > 4) {
                alert('Máximo 4 fotografías permitidas. Solo se tomarán las primeras 4.');
            }
            this.previews = [];
            archivos.forEach(f => {
                const reader = new FileReader();
                reader.onload = e => this.previews.push(e.target.result);
                reader.readAsDataURL(f);
            });
        }
    }
}

function toggleEditNota(notaId) {
    const texto = document.getElementById('texto-nota-' + notaId);
    const form  = document.getElementById('form-nota-' + notaId);
    texto.classList.toggle('hidden');
    form.classList.toggle('hidden');
}

function togglePdfMenu() {
    document.getElementById('pdf-menu').classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('pdf-dropdown-wrap');
    const menu = document.getElementById('pdf-menu');
    if (wrap && menu && !wrap.contains(e.target)) {
        menu.classList.add('hidden');
    }
});
</script>
@endsection