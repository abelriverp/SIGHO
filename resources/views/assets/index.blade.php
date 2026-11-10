@extends('layouts.app')

@section('content')
@php
    $badgeEstado = [
        'Activo'        => 'bg-green-100 text-green-800',
        'En Reparación' => 'bg-yellow-100 text-yellow-800',
        'En almacén'    => 'bg-slate-100 text-slate-700',
        'Baja'          => 'bg-red-100 text-red-800',
    ];
    $sel = 'bg-white border border-slate-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:border-[#041b32]';
@endphp

<div x-data="{ seleccion: [], panelImportar: false }">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Activos</h2>
            <p class="text-gray-500 text-sm mt-1">Catálogo único: cómputo, red, TV, seguridad, energía y periféricos</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('ubicaciones.index') }}" class="bg-white border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg shadow-sm hover:bg-slate-50 transition text-sm">Ubicaciones</a>
            <button type="button" @click="panelImportar = !panelImportar" class="bg-white border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg shadow-sm hover:bg-slate-50 transition text-sm">Importar / exportar</button>
            <a href="{{ route('assets.create') }}" class="hotel-blue hover:opacity-90 text-white font-bold py-2 px-4 rounded-lg shadow transition text-sm">+ Nuevo activo</a>
        </div>
    </div>

    {{-- Importar / exportar --}}
    <div x-show="panelImportar" x-cloak class="bg-white rounded-lg shadow border border-slate-200 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <p class="font-bold text-slate-700">Importar desde CSV</p>
                <p class="text-xs text-slate-500">Usa la plantilla. Si la nomenclatura ya existe, se actualiza. Las ubicaciones que no existan se crean solas. En Excel: Guardar como → CSV UTF-8.</p>
                <input type="file" name="archivo" accept=".csv,text/csv" required class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2">
                <div class="flex gap-2">
                    <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Importar</button>
                    <a href="{{ route('assets.plantilla') }}" class="border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded-md text-sm">Descargar plantilla</a>
                </div>
            </form>
            <div class="space-y-3">
                <p class="font-bold text-slate-700">Exportar</p>
                <p class="text-xs text-slate-500">Todo el catálogo de esta propiedad en CSV, con las mismas columnas de la plantilla.</p>
                <a href="{{ route('assets.export') }}" class="inline-block border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded-md text-sm">Exportar CSV</a>
            </div>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        @foreach ([
            ['Total', $resumen['total'], 'text-slate-800', []],
            ['Activos', $resumen['activos'], 'text-green-700', ['status' => 'Activo']],
            ['En reparación', $resumen['reparacion'], 'text-yellow-700', ['status' => 'En Reparación']],
            ['En almacén', $resumen['almacen'], 'text-slate-600', ['status' => 'En almacén']],
            ['Baja', $resumen['baja'], 'text-red-700', ['status' => 'Baja']],
            ['Garantía por vencer (30 d)', $resumen['garantia'], 'text-orange-700', ['garantia' => 'por_vencer']],
        ] as [$etq, $num, $color, $q])
            <a href="{{ route('assets.index', $q) }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 hover:border-[#041b32] transition">
                <div class="text-xs text-slate-500 font-bold uppercase">{{ $etq }}</div>
                <div class="text-2xl font-bold {{ $color }}">{{ $num }}</div>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('assets.index') }}" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nomenclatura, serie, IP, MAC, marca, encargado…" class="w-full {{ $sel }}">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Categoría</label>
            <select name="categoria" class="{{ $sel }}">
                <option value="">Todas</option>
                @foreach ($categorias as $c)<option value="{{ $c }}" @selected(($filtros['categoria'] ?? '') === $c)>{{ $c }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Estado</label>
            <select name="status" class="{{ $sel }}">
                <option value="">Todos</option>
                @foreach ($estados as $e)<option value="{{ $e }}" @selected(($filtros['status'] ?? '') === $e)>{{ $e }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Ubicación</label>
            <select name="ubicacion_id" class="{{ $sel }} max-w-[220px]">
                <option value="">Todas</option>
                @foreach ($ubicaciones as $u)<option value="{{ $u->id }}" @selected((string) ($filtros['ubicacion_id'] ?? '') === (string) $u->id)>{{ $u->etiqueta }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Filtrar</button>
        @if (array_filter($filtros))
            <a href="{{ route('assets.index') }}" class="text-sm text-slate-500 underline py-2">Limpiar</a>
        @endif
    </form>

    {{-- Acciones en lote --}}
    <form x-show="seleccion.length > 0" x-cloak method="POST" action="{{ route('assets.moverLote') }}" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 flex flex-wrap gap-3 items-end">
        @csrf
        <template x-for="id in seleccion" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <div class="text-sm font-bold text-blue-900 py-2"><span x-text="seleccion.length"></span> seleccionado(s):</div>
        <select name="ubicacion_id" class="{{ $sel }}">
            <option value="">Mover a ubicación…</option>
            @foreach ($ubicaciones as $u)<option value="{{ $u->id }}">{{ $u->etiqueta }}</option>@endforeach
        </select>
        <select name="status" class="{{ $sel }}">
            <option value="">Cambiar estado…</option>
            @foreach ($estados as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
        </select>
        <input type="text" name="division" placeholder="Nueva división" class="{{ $sel }}">
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Aplicar</button>
        <button type="button" @click="seleccion = []" class="text-sm text-slate-600 underline py-2">Cancelar</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="hotel-blue text-white uppercase text-xs leading-normal">
                    <th class="py-3 px-3 w-8"></th>
                    <th class="py-3 px-4 font-semibold">Nomenclatura</th>
                    <th class="py-3 px-4 font-semibold">Activo</th>
                    <th class="py-3 px-4 font-semibold">Categoría</th>
                    <th class="py-3 px-4 font-semibold">Ubicación</th>
                    <th class="py-3 px-4 font-semibold">Resguardo</th>
                    <th class="py-3 px-4 font-semibold">IP</th>
                    <th class="py-3 px-4 font-semibold text-center">Estado</th>
                    <th class="py-3 px-4 font-semibold text-center">Garantía</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm">
                @forelse ($assets as $asset)
                    @php $g = $asset->estadoGarantia(); @endphp
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-3"><input type="checkbox" value="{{ $asset->id }}" x-model.number="seleccion" class="rounded"></td>
                        <td class="py-3 px-4 font-mono text-xs">
                            <a href="{{ route('assets.show', $asset->id) }}" class="text-[#041b32] font-bold hover:underline">{{ $asset->nomenclatura }}</a>
                            @if ($asset->padre)
                                <div class="text-[11px] text-slate-400">↳ {{ $asset->padre->nomenclatura }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800">{{ $asset->nombre }}</div>
                            <div class="text-xs text-slate-500">{{ trim(($asset->tipo_equipo ? $asset->tipo_equipo.' · ' : '').$asset->marca.' '.$asset->modelo) }}</div>
                            @if ($asset->componentes_count)
                                <div class="text-[11px] text-blue-700">+{{ $asset->componentes_count }} ligado(s)</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs">{{ $asset->categoria }}</td>
                        <td class="py-3 px-4 text-xs">
                            @if ($asset->ubicacion)
                                <a href="{{ route('ubicaciones.show', $asset->ubicacion) }}" class="bg-blue-50 text-blue-800 py-1 px-2 rounded-full font-bold hover:underline">{{ $asset->ubicacion->etiqueta }}</a>
                            @else
                                {{ $asset->area }}
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs">{{ $asset->asignado?->name ?? $asset->encargado }}</td>
                        <td class="py-3 px-4 font-mono text-xs">{{ $asset->ip_address }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="py-1 px-2 rounded-full text-xs font-bold whitespace-nowrap {{ $badgeEstado[$asset->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $asset->status ?? '—' }}</span>
                        </td>
                        <td class="py-3 px-4 text-center text-xs whitespace-nowrap">
                            @if ($g === 'vigente')
                                <span class="text-green-700">{{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                            @elseif ($g === 'por_vencer')
                                <span class="text-orange-700 font-bold">{{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                            @elseif ($g === 'vencida')
                                <span class="text-slate-400 line-through">{{ $asset->garantia_hasta->format('d/m/Y') }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-gray-400">
                            @if (array_filter($filtros))
                                No hay activos con esos filtros.
                            @else
                                Aún no hay activos. Empieza por <a href="{{ route('ubicaciones.index') }}" class="underline">crear las ubicaciones</a> y luego <a href="{{ route('assets.create') }}" class="underline">registra un activo</a> o importa la plantilla CSV.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $assets->links() }}</div>
</div>
@endsection
