@extends('layouts.app')

@section('content')
@php
    $badgeEstado = [
        'Activo'        => 'bg-green-100 text-green-800',
        'En Reparación' => 'bg-yellow-100 text-yellow-800',
        'En almacén'    => 'bg-slate-100 text-slate-700',
        'Baja'          => 'bg-red-100 text-red-800',
    ];
    $g = $asset->estadoGarantia();
    $dato = function ($etiqueta, $valor, $mono = false) {
        if ($valor === null || $valor === '') return '';
        return '<div><span class="block text-xs text-gray-400 uppercase font-bold">'.e($etiqueta).'</span><span class="text-sm text-slate-700 '.($mono ? 'font-mono' : '').'">'.e($valor).'</span></div>';
    };
@endphp
<div class="max-w-7xl mx-auto mt-2">

    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Activos</a>
            <h2 class="text-3xl font-bold text-[#041b32] mt-1">{{ $asset->nomenclatura }}</h2>
            <p class="text-slate-600">{{ $asset->nombre }} · {{ $asset->categoria }}{{ $asset->tipo_equipo ? ' · '.$asset->tipo_equipo : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <span class="py-1 px-3 rounded-full text-sm font-bold {{ $badgeEstado[$asset->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $asset->status }}</span>
            <a href="{{ route('assets.edit', $asset->id) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded shadow">Editar</a>
            @can('delete', $asset)
                <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('¿Eliminar {{ $asset->nomenclatura }}? Para equipos dados de baja es mejor cambiar el estado a Baja y conservar el historial.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="border border-red-300 text-red-700 text-sm font-bold py-2 px-4 rounded">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="font-bold text-slate-700 mb-4">Datos generales</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {!! $dato('Marca', $asset->marca) !!}
                    {!! $dato('Modelo', $asset->modelo) !!}
                    {!! $dato('Número de serie', $asset->numero_serie, true) !!}
                    {!! $dato('IP', $asset->ip_address, true) !!}
                    {!! $dato('MAC', $asset->mac_address, true) !!}
                    {!! $dato('División', $asset->division) !!}
                    {!! $dato('Área / puesto', $asset->area) !!}
                    {!! $dato('Procesador', $asset->procesador) !!}
                    {!! $dato('RAM', $asset->ram ? $asset->ram.' GB '.$asset->tipo_ram : null) !!}
                    {!! $dato('Almacenamiento', $asset->almacenamiento) !!}
                    {!! $dato('Sistema operativo', $asset->sistema_operativo) !!}
                    {!! $dato('Office', $asset->paqueteria_office) !!}
                </div>
                @if ($asset->notas)
                    <div class="border-t border-gray-100 pt-4 mt-4">
                        <span class="block text-xs text-gray-400 uppercase font-bold mb-1">Notas</span>
                        <p class="text-gray-600 text-sm whitespace-pre-line">{{ $asset->notas }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-700">Ligados a este activo</h3>
                    <a href="{{ route('assets.create', ['parent_id' => $asset->id, 'categoria' => 'Periférico', 'ubicacion_id' => $asset->ubicacion_id]) }}" class="text-sm text-blue-700 font-bold hover:underline">+ Agregar periférico</a>
                </div>
                @forelse ($asset->componentes as $c)
                    <a href="{{ route('assets.show', $c->id) }}" class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0 hover:bg-slate-50 px-2 rounded">
                        <span class="text-sm"><span class="font-mono font-bold text-[#041b32]">{{ $c->nomenclatura }}</span> · {{ $c->tipo_equipo ?? $c->categoria }} {{ $c->marca }} {{ $c->modelo }}</span>
                        <span class="text-xs text-slate-500 font-mono">{{ $c->numero_serie }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">Sin periféricos ni componentes ligados.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 space-y-3">
                <h3 class="font-bold text-slate-700">Ubicación y resguardo</h3>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Ubicación</span>
                    @if ($asset->ubicacion)
                        <a href="{{ route('ubicaciones.show', $asset->ubicacion) }}" class="text-sm font-bold text-blue-800 hover:underline">{{ $asset->ubicacion->etiqueta }}</a>
                        <span class="text-xs text-slate-500">({{ $asset->ubicacion->tipo_nombre }}{{ $asset->ubicacion->piso !== null ? ', piso '.$asset->ubicacion->piso : '' }})</span>
                    @else
                        <span class="text-sm text-slate-400">Sin ubicación</span>
                    @endif
                </div>
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Resguardo</span>
                    <span class="text-sm text-slate-700">{{ $asset->asignado?->name ?? $asset->encargado ?? '—' }}</span>
                </div>
                @if ($asset->padre)
                    <div>
                        <span class="block text-xs text-gray-400 uppercase font-bold">Ligado a</span>
                        <a href="{{ route('assets.show', $asset->padre->id) }}" class="text-sm font-mono font-bold text-blue-800 hover:underline">{{ $asset->padre->nomenclatura }}</a>
                    </div>
                @endif
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-100 space-y-3">
                <h3 class="font-bold text-slate-700">Compra y garantía</h3>
                {!! $dato('Proveedor', $asset->proveedor) !!}
                {!! $dato('Factura', $asset->factura) !!}
                {!! $dato('Costo', $asset->costo !== null ? '$'.number_format($asset->costo, 2).' MXN' : null) !!}
                {!! $dato('Fecha de compra', $asset->fecha_adquisicion?->format('d/m/Y')) !!}
                <div>
                    <span class="block text-xs text-gray-400 uppercase font-bold">Garantía</span>
                    @if ($g === 'vigente')
                        <span class="text-sm text-green-700 font-bold">Vigente hasta {{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                    @elseif ($g === 'por_vencer')
                        <span class="text-sm text-orange-700 font-bold">Vence el {{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                    @elseif ($g === 'vencida')
                        <span class="text-sm text-slate-500">Vencida el {{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                    @else
                        <span class="text-sm text-slate-400">Sin dato</span>
                    @endif
                </div>
                @if ($asset->status === 'Baja')
                    <div class="bg-red-50 border border-red-100 rounded p-3">
                        <span class="block text-xs text-red-700 uppercase font-bold">Baja {{ $asset->fecha_baja?->format('d/m/Y') }}</span>
                        <span class="text-sm text-red-800">{{ $asset->motivo_baja ?? 'Sin motivo registrado' }}</span>
                    </div>
                @endif
            </div>

            @if ($asset->imagen_path)
                <div class="bg-white p-2 rounded-lg shadow-sm border border-gray-100">
                    <img src="{{ Storage::url($asset->imagen_path) }}" class="w-full rounded object-cover" alt="Foto de {{ $asset->nomenclatura }}">
                </div>
            @endif
            @if ($asset->pdf_path)
                <a href="{{ Storage::url($asset->pdf_path) }}" target="_blank" class="block bg-blue-50 border border-blue-100 p-3 rounded-lg text-sm font-bold text-blue-800">Ver documento PDF</a>
            @endif
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow sm:rounded-lg mb-10">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-slate-700 text-lg">Tickets</h3>
            <span class="text-xs text-slate-500">Últimos {{ $tickets->count() }}</span>
        </div>
        @forelse($tickets as $t)
            <a href="{{ route('tickets.show', $t->id) }}" class="flex justify-between gap-4 px-6 py-3 border-b border-gray-50 hover:bg-slate-50 text-sm">
                <span><span class="font-bold text-[#041b32]">#{{ $t->id }}</span> {{ \Illuminate\Support\Str::limit($t->descripcion, 90) }}</span>
                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $t->created_at->format('d/m/Y') }} · {{ $t->status }}</span>
            </a>
        @empty
            <p class="px-6 py-6 text-sm text-slate-400">Sin tickets ligados.</p>
        @endforelse
    </div>

    <div class="bg-white overflow-hidden shadow sm:rounded-lg mb-10">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-slate-700 text-lg">Historial de mantenimiento</h3>
            <a href="{{ route('revisions.create', $asset->id) }}" class="bg-[#041b32] hover:opacity-90 text-white text-sm font-bold py-2 px-4 rounded shadow transition">+ Nueva revisión</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#041b32] text-white uppercase font-bold text-xs">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Problema</th>
                        <th class="py-3 px-4">Diagnóstico</th>
                        <th class="py-3 px-4">Solución</th>
                        <th class="py-3 px-4">Resultado</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($asset->revisions as $revision)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $revision->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $revision->problema }}</td>
                            <td class="py-3 px-4">{{ Str::limit($revision->diagnostico, 60) }}</td>
                            <td class="py-3 px-4">{{ Str::limit($revision->solucion, 60) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-xs font-bold
                                    {{ $revision->resultado == 'Exitoso' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $revision->resultado == 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $revision->resultado == 'Sin Solución' ? 'bg-red-100 text-red-800' : '' }}">{{ $revision->resultado ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-gray-400">Sin revisiones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
