<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; }
        .header { background: #041b32; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
        .info-section { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .label { font-weight: bold; color: #666; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 14px; margin-top: 2px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; }

        /* Bitácora */
        .bitacora-section { margin-top: 24px; }
        .bitacora-title { font-size: 12px; font-weight: bold; color: #041b32; border-bottom: 2px solid #041b32; padding-bottom: 4px; margin-bottom: 10px; text-transform: uppercase; }
        .nota-item { margin-bottom: 8px; padding: 6px 10px; border-radius: 4px; font-size: 10px; }
        .nota-publica  { background: #f0fdf4; border-left: 3px solid #16a34a; }
        .nota-interna  { background: #f8fafc; border-left: 3px solid #94a3b8; }
        .nota-meta { font-size: 9px; color: #94a3b8; margin-bottom: 2px; }
        .nota-texto { color: #334155; }

        /* Contenedor relativo para la página de evidencias */
        .page-evidencia-container { position: relative; min-height: 650px; }
        .bottom-tokens-footer { position: absolute; bottom: 0; left: 0; width: 100%; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0;">HOTEL EMPORIO MAZATLÁN</h2>
        <p style="margin:5px 0 0 0;">Reporte de Soporte - Ticket #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</p>
    </div>

    {{-- Bloque de Información del Ticket --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 10px;">
                <div class="info-section">
                    <div class="label">Solicitante</div>
                    <div class="value">{{ $ticket->user->name }}</div>
                </div>
                <div class="info-section">
                    <div class="label">Departamento / Área</div>
                    <div class="value">{{ $ticket->departamento_destino }} / {{ $ticket->area }}</div>
                </div>
                <div class="info-section">
                    <div class="label">Prioridad</div>
                    <div class="value">{{ ucfirst($ticket->prioridad) }}</div>
                </div>
            </td>
            <td width="50%" style="vertical-align: top; padding-left: 10px;">
                <div class="info-section">
                    <div class="label">Fecha de Apertura</div>
                    <div class="value">{{ $ticket->created_at->format('d/m/Y H:i') }} hrs.</div>
                </div>
                <div class="info-section">
                    <div class="label">Estado Actual</div>
                    <div class="value" style="font-weight: bold; color: {{ $ticket->status === 'Cerrado' ? '#16a34a' : ($ticket->status === 'Resuelto' ? '#2563eb' : '#dc2626') }}">
                        {{ strtoupper($ticket->status) }}
                    </div>
                </div>
                @if($ticket->fecha_resolucion)
                <div class="info-section">
                    <div class="label">Fecha de Resolución</div>
                    <div class="value">{{ $ticket->fecha_resolucion->format('d/m/Y H:i') }} hrs.</div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <div class="info-section" style="margin-bottom: 25px;">
        <div class="label">Descripción del Problema</div>
        <div class="value" style="background: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0; margin-top: 5px;">
            {{ $ticket->descripcion }}
        </div>
    </div>

    {{-- EVIDENCIA ADJUNTA AL REPORTAR (fotos que subió el solicitante al abrir el ticket) --}}
    @if($fotosEvidenciaBase64 && count($fotosEvidenciaBase64) > 0)
    <div class="info-section" style="margin-bottom: 25px;">
        <div class="label">Evidencia Adjunta al Reportar</div>
        <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin-top: 5px;">
            @foreach(array_chunk($fotosEvidenciaBase64, 2) as $fila)
            <tr>
                @foreach($fila as $foto)
                <td width="50%" style="border: 1px solid #d1d5db; vertical-align: middle; text-align: center; height: 170px; background: #f9fafb;">
                    <img src="{{ $foto }}" style="max-width: 100%; max-height: 150px; object-fit: contain; display: block; margin: 0 auto;">
                </td>
                @endforeach
                @if(count($fila) === 1)
                <td width="50%"></td>
                @endif
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @php
        // PDF/TXT adjuntados al reportar: no se pueden incrustar como imagen
        // en este reporte (a diferencia de jpg/png/webp, que sí se embeben
        // arriba como $fotosEvidenciaBase64), así que aquí solo se listan
        // para que quede constancia de que existen y dónde verlos.
        $rutasEvidenciaTicket = $ticket->archivos_evidencia ?: ($ticket->archivo_path ? [$ticket->archivo_path] : []);
        $documentosEvidencia  = collect($rutasEvidenciaTicket)
            ->filter(fn ($ruta) => in_array(strtolower(pathinfo($ruta, PATHINFO_EXTENSION)), ['pdf', 'txt']))
            ->values();
    @endphp
    @if($documentosEvidencia->count() > 0)
    <div class="info-section" style="margin-bottom: 25px;">
        <div class="label">Documentos Adjuntos al Reportar</div>
        <div class="value" style="margin-top: 5px;">
            @foreach($documentosEvidencia as $i => $ruta)
                @php $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION)); @endphp
                <div>{{ $i + 1 }}. Documento {{ strtoupper($ext) }} — ver en el sistema, sección "Evidencia Adjunta" del ticket.</div>
            @endforeach
        </div>
    </div>
    @endif

    @if($ticket->notas_admin)
    <div class="info-section" style="margin-bottom: 25px;">
        <div class="label">Resolución Técnica</div>
        <div class="value" style="background: #f0fdf4; padding: 10px; border-radius: 4px; border: 1px solid #bbf7d0; margin-top: 5px;">
            {{ $ticket->notas_admin }}
        </div>
    </div>
    @endif

    {{-- Tokens de auditoría en primera página si no hay fotos --}}
    @if(!($fotosBase64 && count($fotosBase64) > 0) && ($ticket->token_resolucion || $ticket->token_confirmacion))
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 6px;">
                @if($ticket->token_resolucion)
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #bfdbfe; border-radius: 6px; overflow: hidden;">
                    <tr><td style="background: #041b32; padding: 6px 12px;">
                        <span style="font-size: 9px; font-weight: bold; color: #bfdbfe; text-transform: uppercase;">Constancia de Resolución Técnica</span>
                    </td></tr>
                    <tr><td style="background: #eff6ff; padding: 8px 12px; font-family: monospace; font-size: 10px; color: #334155;">
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Resuelto por :</span> <strong style="color: #041b32;">{{ $ticket->resolvedByUser->name ?? 'Técnico asignado' }}</strong></p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Fecha y hora :</span> {{ $ticket->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Token        :</span> <strong style="color: #1d4ed8;">{{ $ticket->token_resolucion }}</strong></p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">IP de origen :</span> {{ $ticket->ip_resolucion ?? '—' }}</p>
                    </td></tr>
                </table>
                @endif
            </td>
            <td width="50%" style="vertical-align: top; padding-left: 6px;">
                @if($ticket->token_confirmacion)
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #bbf7d0; border-radius: 6px; overflow: hidden;">
                    <tr><td style="background: #15803d; padding: 6px 12px;">
                        <span style="font-size: 9px; font-weight: bold; color: #dcfce7; text-transform: uppercase;">Constancia de Aceptación del Usuario</span>
                    </td></tr>
                    <tr><td style="background: #f0fdf4; padding: 8px 12px; font-family: monospace; font-size: 10px; color: #334155;">
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Confirmado por:</span> <strong style="color: #041b32;">{{ $ticket->user->name }}</strong></p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Fecha y hora :</span> {{ $ticket->fecha_confirmacion?->format('d/m/Y H:i') ?? '—' }}</p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">Token        :</span> <strong style="color: #15803d;">{{ $ticket->token_confirmacion }}</strong></p>
                        <p style="margin: 2px 0;"><span style="color: #94a3b8;">IP de origen :</span> {{ $ticket->ip_confirmacion ?? '—' }}</p>
                    </td></tr>
                </table>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- BITÁCORA (solo si se solicitó) --}}
    @if($incluirBitacora && $notasPDF->count() > 0)
    <div class="bitacora-section" style="page-break-before: {{ ($fotosBase64 && count($fotosBase64) > 0) ? 'auto' : 'always' }};">
        <div class="bitacora-title">Bitácora de Seguimiento</div>
        @foreach($notasPDF as $nota)
        <div class="nota-item {{ $nota->tipo === 'publica' ? 'nota-publica' : 'nota-interna' }}">
            <div class="nota-meta">
                {{ $nota->user->name ?? 'Sistema' }} &nbsp;·&nbsp;
                {{ $nota->tipo === 'publica' ? '🌐 Pública' : '🔒 Interna' }} &nbsp;·&nbsp;
                {{ $nota->created_at->format('d/m/Y H:i') }}
            </div>
            <div class="nota-texto">{{ $nota->nota }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- SECCIÓN DE EVIDENCIA FOTOGRÁFICA --}}
    @if($fotosBase64 && count($fotosBase64) > 0)
    <div style="page-break-before: always;" class="page-evidencia-container">

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px; border-bottom: 2px solid #041b32;">
            <tr>
                <td style="padding: 6px 0;">
                    <span style="font-size: 13px; font-weight: bold; color: #041b32;">EVIDENCIA FOTOGRÁFICA DE RESOLUCIÓN</span>
                </td>
                <td align="right" style="padding: 6px 0;">
                    <span style="font-size: 11px; color: #666;">Ticket #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>

        @php
            $totalFotos  = count($fotosBase64);
            $fotos       = array_slice($fotosBase64, 0, 4);
            while (count($fotos) < 4) $fotos[] = null;
            $filasGrid   = ($totalFotos <= 2) ? [[0,1]] : [[0,1],[2,3]];
            $alturaCelda = ($totalFotos <= 2) ? '210px' : '170px';
        @endphp

        <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin-bottom: 30px;">
            @foreach($filasGrid as $fila)
            <tr>
                @foreach($fila as $idx)
                <td width="50%" style="border: 1px solid #d1d5db; vertical-align: middle; text-align: center; height: {{ $alturaCelda }}; background: #f9fafb;">
                    @if($fotos[$idx])
                    {{-- ✅ FIX CLAVE: src con base64, no storage_path --}}
                    <img src="{{ $fotos[$idx] }}"
                         style="max-width: 100%; max-height: calc({{ $alturaCelda }} - 20px); object-fit: contain; display: block; margin: 0 auto;">
                    <p style="margin: 4px 0 0; font-size: 9px; color: #9ca3af;">
                        Foto {{ $idx + 1 }} de {{ $totalFotos }}
                    </p>
                    @else
                    <p style="color: #d1d5db; font-size: 11px; font-style: italic;">— Sin imagen —</p>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </table>

        {{-- TOKENS AL PIE DE LA PÁGINA DE FOTOS --}}
        <div class="bottom-tokens-footer">
            <table width="100%" cellpadding="0" cellspacing="0" style="table-layout: fixed;">
                <tr>
                    <td width="50%" style="vertical-align: top; padding-right: 6px;">
                        @if($ticket->token_resolucion)
                        <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #bfdbfe; border-radius: 6px; overflow: hidden;">
                            <tr><td style="background: #041b32; padding: 6px 12px;">
                                <span style="font-size: 9px; font-weight: bold; color: #bfdbfe; text-transform: uppercase; letter-spacing: 0.05em;">Constancia de Resolución Técnica</span>
                            </td></tr>
                            <tr><td style="background: #eff6ff; padding: 8px 12px; font-family: monospace; font-size: 10px; color: #334155;">
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Resuelto por :</span> <strong style="color: #041b32;">{{ $ticket->resolvedByUser->name ?? 'Técnico asignado' }}</strong></p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Fecha y hora :</span> {{ $ticket->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Token        :</span> <strong style="color: #1d4ed8;">{{ $ticket->token_resolucion }}</strong></p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">IP de origen :</span> {{ $ticket->ip_resolucion ?? '—' }}</p>
                                <p style="margin: 4px 0 0; font-size: 7.5px; color: #94a3b8; font-style: italic; font-family: sans-serif; line-height: 1.3;">
                                    El personal técnico declaró el problema como atendido y resuelto de forma única e irrepetible.
                                </p>
                            </td></tr>
                        </table>
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td width="50%" style="vertical-align: top; padding-left: 6px;">
                        @if($ticket->token_confirmacion)
                        <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #bbf7d0; border-radius: 6px; overflow: hidden;">
                            <tr><td style="background: #15803d; padding: 6px 12px;">
                                <span style="font-size: 9px; font-weight: bold; color: #dcfce7; text-transform: uppercase; letter-spacing: 0.05em;">Constancia de Aceptación del Usuario</span>
                            </td></tr>
                            <tr><td style="background: #f0fdf4; padding: 8px 12px; font-family: monospace; font-size: 10px; color: #334155;">
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Confirmado por:</span> <strong style="color: #041b32;">{{ $ticket->user->name }}</strong></p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Fecha y hora :</span> {{ $ticket->fecha_confirmacion?->format('d/m/Y H:i') ?? '—' }}</p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">Token        :</span> <strong style="color: #15803d;">{{ $ticket->token_confirmacion }}</strong></p>
                                <p style="margin: 2px 0;"><span style="color: #94a3b8;">IP de origen :</span> {{ $ticket->ip_confirmacion ?? '—' }}</p>
                                <p style="margin: 4px 0 0; font-size: 7.5px; color: #94a3b8; font-style: italic; font-family: sans-serif; line-height: 1.3;">
                                    El usuario declaró la resolución como satisfactoria y autorizó el cierre definitivo del ticket.
                                </p>
                            </td></tr>
                        </table>
                        @else
                            &nbsp;
                        @endif
                    </td>
                </tr>

                @if($ticket->token_resolucion || $ticket->token_confirmacion)
                <tr>
                    <td colspan="2" style="padding: 10px 0 0;">
                        <table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 4px;">
                            <tr><td style="text-align: center;">
                                <p style="margin: 0; font-size: 8px; color: #64748b; font-family: sans-serif; line-height: 1.4;">
                                    Los tokens de auditoría contenidos en este documento son generados automáticamente por el
                                    <strong>SIGHO - Sistema Integral de Gestión de Hospitalidad y Operaciones</strong> y constituyen un registro
                                    digital vinculante de las acciones realizadas por los usuarios identificados.<br>
                                    <span style="color: #94a3b8;">Documento generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }} hrs.</span>
                                </p>
                            </td></tr>
                        </table>
                    </td>
                </tr>
                @endif
            </table>
        </div>

    </div>
    @endif

</body>
</html>