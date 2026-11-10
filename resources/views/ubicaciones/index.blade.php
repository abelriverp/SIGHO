@extends('layouts.app')

@section('content')
@php $sel = 'bg-white border border-slate-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:border-[#041b32]'; @endphp
<div x-data="{ generar: {{ $ubicaciones->total() === 0 ? 'true' : 'false' }} }">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end gap-4 mb-6">
        <div>
            <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-[#041b32]">← Activos</a>
            <h2 class="text-2xl font-bold text-slate-800">Ubicaciones y habitaciones</h2>
            <p class="text-gray-500 text-sm mt-1">Dónde vive cada activo: habitaciones, áreas, oficinas y sites</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="generar = !generar" class="bg-white border border-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg shadow-sm hover:bg-slate-50 text-sm">Generar habitaciones por piso</button>
            <a href="{{ route('ubicaciones.create') }}" class="hotel-blue hover:opacity-90 text-white font-bold py-2 px-4 rounded-lg shadow text-sm">+ Nueva ubicación</a>
        </div>
    </div>

    <form x-show="generar" x-cloak method="POST" action="{{ route('ubicaciones.generar') }}" class="bg-white rounded-lg shadow border border-slate-200 p-5 mb-6 flex flex-wrap gap-4 items-end">
        @csrf
        <div class="w-full text-sm text-slate-600">Crea de un jalón las habitaciones de un piso. Ejemplo: piso 12, de la 1 a la 30, con 2 dígitos → 1201 a 1230. Las que ya existan se omiten.</div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Piso</label><input type="number" name="piso" min="0" max="200" required class="{{ $sel }} w-24"></div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Desde</label><input type="number" name="desde" min="0" value="1" required class="{{ $sel }} w-24"></div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Hasta</label><input type="number" name="hasta" min="0" value="30" required class="{{ $sel }} w-24"></div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Dígitos</label>
            <select name="digitos" class="{{ $sel }}"><option value="2">2 (1201)</option><option value="3">3 (12001)</option></select>
        </div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Torre / zona (opcional)</label><input type="text" name="zona" class="{{ $sel }}"></div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Generar</button>
    </form>

    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('ubicaciones.index') }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ empty($filtros['tipo']) ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">Todas ({{ $conteoPorTipo->sum() }})</a>
        @foreach ($tipos as $clave => $nombre)
            <a href="{{ route('ubicaciones.index', ['tipo' => $clave]) }}" class="px-3 py-1.5 rounded-full text-xs font-bold border {{ ($filtros['tipo'] ?? '') === $clave ? 'bg-[#041b32] text-white border-[#041b32]' : 'bg-white text-slate-600 border-slate-300' }}">{{ $nombre }} ({{ $conteoPorTipo[$clave] ?? 0 }})</a>
        @endforeach
    </div>

    <form method="GET" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <input type="hidden" name="tipo" value="{{ $filtros['tipo'] ?? '' }}">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-500 mb-1">Buscar</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código, nombre o zona" class="w-full {{ $sel }}">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Piso</label>
            <select name="piso" class="{{ $sel }}">
                <option value="">Todos</option>
                @foreach ($pisos as $p)<option value="{{ $p }}" @selected((string) ($filtros['piso'] ?? '') === (string) $p)>{{ $p }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-4 rounded-md text-sm">Filtrar</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="hotel-blue text-white uppercase text-xs">
                    <th class="py-3 px-4">Código</th>
                    <th class="py-3 px-4">Nombre</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4 text-center">Piso</th>
                    <th class="py-3 px-4">Zona</th>
                    <th class="py-3 px-4 text-center">Activos</th>
                    <th class="py-3 px-4 text-center">En reparación</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600">
                @forelse ($ubicaciones as $u)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $u->activa ? '' : 'opacity-50' }}">
                        <td class="py-2.5 px-4 font-mono font-bold"><a href="{{ route('ubicaciones.show', $u) }}" class="text-[#041b32] hover:underline">{{ $u->codigo }}</a></td>
                        <td class="py-2.5 px-4">{{ $u->nombre }}</td>
                        <td class="py-2.5 px-4 text-xs">{{ $u->tipo_nombre }}</td>
                        <td class="py-2.5 px-4 text-center">{{ $u->piso }}</td>
                        <td class="py-2.5 px-4 text-xs">{{ $u->zona }}</td>
                        <td class="py-2.5 px-4 text-center font-bold">{{ $u->activos_count }}</td>
                        <td class="py-2.5 px-4 text-center {{ $u->activos_en_reparacion_count ? 'text-yellow-700 font-bold' : 'text-slate-300' }}">{{ $u->activos_en_reparacion_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-gray-400">Sin ubicaciones. Genera las habitaciones por piso y crea las áreas (Lobby, Recepción, Site, etc.).</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="mt-4">{{ $ubicaciones->links() }}</div>
</div>
@endsection
