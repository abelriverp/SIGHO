@extends('layouts.app')

@section('content')
@php
    $u = auth()->user();
    $propio = $cambio->solicitado_por === $u->id && ! $u->isAdmin();
    $acciones = [];
    foreach (\App\Models\Cambio::TRANSICIONES[$cambio->estado] ?? [] as $dest) {
        $acciones[] = $dest;
    }
    if ($cambio->tipo === 'emergencia' && $cambio->estado === 'borrador') {
        $acciones[] = 'en_ejecucion';
    }
    $etiquetas = [
        'pendiente_aprobacion' => $cambio->tipo === 'estandar' ? 'Enviar (preaprobado)' : 'Enviar a aprobación',
        'aprobado' => 'Aprobar', 'rechazado' => 'Rechazar', 'en_ejecucion' => 'Iniciar ejecución',
        'completado' => 'Marcar completado', 'fallido' => 'Falló (se hizo rollback)', 'cancelado' => 'Cancelar', 'borrador' => 'Regresar a borrador',
    ];
    $conComentario = ['rechazado', 'completado', 'fallido', 'aprobado'];
@endphp
<div class="max-w-5xl mx-auto mb-10">
    <a href="{{ route('cambios.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Cambios</a>
    <div class="flex flex-wrap justify-between items-start gap-4 mt-1 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">C-{{ $cambio->id }} · {{ $cambio->titulo }}</h2>
            <p class="text-sm text-slate-600 mt-1">@include('cambios._badge', ['estado' => $cambio->estado]) <span class="ml-2">{{ $tipos[$cambio->tipo] ?? $cambio->tipo }} · riesgo <strong>{{ $cambio->riesgo }}</strong> · solicitó {{ $cambio->solicitante?->name }}</span></p>
            <p class="text-sm text-slate-500 mt-1">Ventana: {{ $cambio->ventana_inicio?->format('d/m/Y H:i') ?? 'sin definir' }}{{ $cambio->ventana_fin ? ' a '.$cambio->ventana_fin->format('d/m/Y H:i') : '' }}
                @if($cambio->problema) · <a href="{{ route('problemas.show', $cambio->problema) }}" class="underline">P-{{ $cambio->problema->id }} {{ $cambio->problema->titulo }}</a>@endif</p>
        </div>
        @if($cambio->editable())
            <a href="{{ route('cambios.edit', $cambio) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded">Editar</a>
        @endif
    </div>

    @if($acciones)
        <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6" x-data="{ destino: null }">
            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Siguiente paso</p>
            <div class="flex flex-wrap gap-2">
                @foreach($acciones as $dest)
                    @php
                        $soloSupervisor = in_array($dest, ['aprobado', 'rechazado'], true);
                        if ($soloSupervisor && (! $u->puedeVerTodos() || $propio)) continue;
                    @endphp
                    @if(in_array($dest, $conComentario, true))
                        <button type="button" @click="destino = '{{ $dest }}'" class="text-sm font-bold py-2 px-4 rounded border {{ in_array($dest, ['rechazado', 'fallido'], true) ? 'border-red-300 text-red-700' : 'border-[#041b32] text-[#041b32]' }}">{{ $etiquetas[$dest] ?? $dest }}</button>
                    @else
                        <form method="POST" action="{{ route('cambios.transicion', $cambio) }}">@csrf<input type="hidden" name="estado" value="{{ $dest }}"><button class="text-sm font-bold py-2 px-4 rounded {{ $dest === 'cancelado' ? 'border border-slate-300 text-slate-600' : 'bg-[#041b32] text-white' }}">{{ $etiquetas[$dest] ?? $dest }}</button></form>
                    @endif
                @endforeach
            </div>
            @if($cambio->estado === 'pendiente_aprobacion' && $propio)
                <p class="text-xs text-amber-700 mt-2">Tú lo solicitaste: debe aprobarlo otro gerente o un administrador.</p>
            @endif
            <form method="POST" action="{{ route('cambios.transicion', $cambio) }}" x-show="destino" x-cloak class="mt-3 space-y-2">
                @csrf
                <input type="hidden" name="estado" :value="destino">
                <textarea name="comentario" rows="3" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-sm" :placeholder="destino === 'aprobado' ? 'Comentario (opcional)' : (destino === 'rechazado' ? 'Motivo del rechazo (obligatorio)' : 'Resultado: qué se hizo y cómo quedó (obligatorio)')"></textarea>
                <div class="flex gap-2"><button class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded">Confirmar</button><button type="button" @click="destino = null" class="text-sm text-slate-500">Cancelar</button></div>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-slate-200 p-5 mb-4"><p class="text-xs font-bold text-slate-400 uppercase mb-1">Descripción</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $cambio->descripcion }}</p>
        @if($cambio->impacto)<p class="text-xs font-bold text-slate-400 uppercase mt-4 mb-1">Impacto</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $cambio->impacto }}</p>@endif
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-blue-700 uppercase mb-1">Plan de implementación</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $cambio->plan_implementacion }}</p></div>
        <div class="bg-white rounded-lg border border-slate-200 p-4"><p class="text-xs font-bold text-red-700 uppercase mb-1">Plan de regreso (rollback)</p><p class="text-sm text-slate-700 whitespace-pre-line">{{ $cambio->plan_rollback }}</p></div>
    </div>
    @if($cambio->aprobado_at || $cambio->comentario_aprobacion || $cambio->resultado)
        <div class="bg-white rounded-lg border border-slate-200 p-4 mb-4 text-sm space-y-2">
            @if($cambio->aprobado_at || $cambio->comentario_aprobacion)
                <p><strong>{{ $cambio->estado === 'rechazado' ? 'Rechazado' : 'Aprobado' }}</strong>{{ $cambio->aprobador ? ' por '.$cambio->aprobador->name : '' }}{{ $cambio->aprobado_at ? ' el '.$cambio->aprobado_at->format('d/m/Y H:i') : '' }}{{ $cambio->comentario_aprobacion ? ': '.$cambio->comentario_aprobacion : '' }}</p>
            @endif
            @if($cambio->resultado)<p><strong>Resultado</strong> ({{ $cambio->completado_at?->format('d/m/Y H:i') }}): {{ $cambio->resultado }}</p>@endif
        </div>
    @endif
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 font-bold text-slate-700">{{ $cambio->activos->count() }} equipo(s) afectado(s)</div>
        @forelse($cambio->activos as $a)
            <a href="{{ route('assets.show', $a->id) }}" class="flex justify-between px-6 py-2 border-b border-gray-50 hover:bg-slate-50 text-sm"><span><span class="font-mono text-xs">{{ $a->nomenclatura }}</span> {{ $a->tipo_equipo ?: $a->nombre }}</span><span class="text-xs text-slate-500">{{ $a->ubicacion?->etiqueta }}</span></a>
        @empty
            <p class="px-6 py-4 text-sm text-slate-400">Sin equipos indicados.</p>
        @endforelse
    </div>
</div>
@endsection
