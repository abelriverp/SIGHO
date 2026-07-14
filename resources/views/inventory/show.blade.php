@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 mt-6">

    {{-- Cabecera --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-[#041b32] text-sm font-medium flex items-center gap-1 mb-1">
                ← Volver al inventario
            </a>
            <h2 class="text-2xl font-bold text-[#041b32]">{{ $item->nombre }}</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="bg-indigo-50 text-indigo-800 py-1 px-2 rounded-full text-xs font-bold">{{ $item->area ?? 'Sin área' }}</span>
                <span class="bg-blue-50 text-blue-800 py-1 px-2 rounded-full text-xs font-bold">{{ $item->categoria }}</span>
                @php
                    $badgeClass = match($item->status) {
                        'Disponible' => 'bg-green-100 text-green-800',
                        'Agotado'    => 'bg-yellow-100 text-yellow-800',
                        'Baja'       => 'bg-red-100 text-red-800',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="py-1 px-2 rounded-full text-xs font-bold {{ $badgeClass }}">{{ $item->status }}</span>
            </div>
        </div>
        <a href="{{ route('inventory.edit', $item->id) }}" class="bg-[#041b32] text-white text-sm font-bold py-2 px-4 rounded shadow hover:opacity-90">
            Editar
        </a>
    </div>

    {{-- Alertas de sesión --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Info principal --}}
        <div class="md:col-span-2 space-y-4">

            {{-- Tarjeta de stock + botones de movimiento --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Stock Actual</h3>
                <div class="flex items-end gap-6 mb-5">
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block">Cantidad</span>
                        <span class="text-4xl font-bold {{ $item->bajoPorStock() ? 'text-red-600' : 'text-[#041b32]' }}">
                            {{ $item->cantidad }}
                        </span>
                        <span class="text-sm text-gray-500 ml-1">{{ $item->unidad }}</span>
                    </div>
                    @if($item->stock_minimo > 0)
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-bold block">Stock mínimo</span>
                        <span class="text-2xl font-bold text-gray-400">{{ $item->stock_minimo }} <span class="text-sm">{{ $item->unidad }}</span></span>
                    </div>
                    @endif
                    @if($item->bajoPorStock())
                        <div class="flex-1">
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-lg block text-center">
                                ⚠ Stock bajo — Reabastecer pronto
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Botones + formulario de movimiento rápido (Alpine.js) --}}
                <div x-data="{ tipo: '', tipoSalida: '' }" class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-3">Registrar movimiento</p>

                    <div class="flex gap-2 mb-3">
                        <button @click="tipo = tipo === 'entrada' ? '' : 'entrada'; tipoSalida=''"
                            :class="tipo === 'entrada' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-800 hover:bg-green-100'"
                            class="flex-1 text-sm font-bold py-2 px-4 rounded-lg border border-green-200 transition">
                            ↑ Entrada
                        </button>
                        <button @click="tipo = tipo === 'salida' ? '' : 'salida'"
                            :class="tipo === 'salida' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-800 hover:bg-red-100'"
                            class="flex-1 text-sm font-bold py-2 px-4 rounded-lg border border-red-200 transition">
                            ↓ Salida
                        </button>
                    </div>

                    <form x-show="tipo !== ''" x-cloak
                        action="{{ route('inventory.movimiento', $item->id) }}" method="POST"
                        class="bg-gray-50 rounded-lg p-4 space-y-3 border border-gray-200">
                        @csrf
                        <input type="hidden" name="tipo" :value="tipo">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">
                                    Cantidad a <span x-text="tipo === 'entrada' ? 'ingresar' : 'retirar'"></span>
                                </label>
                                <input type="number" name="cantidad" min="0.01" step="0.01" required
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                                    placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Motivo <span class="text-red-500">*</span></label>
                                <input type="text" name="motivo" required maxlength="255"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                                    :placeholder="tipo === 'entrada' ? 'Ej: Reabastecimiento semanal' : 'Ej: Amenidad solicitada por el huésped'">
                            </div>
                        </div>

                        {{-- Campos exclusivos de SALIDA --}}
                        <div x-show="tipo === 'salida'" x-cloak class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Tipo de salida <span class="text-red-500">*</span></label>
                                <select name="tipo_salida" x-model="tipoSalida" :required="tipo === 'salida'"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100">
                                    <option value="">Selecciona...</option>
                                    @foreach(\App\Models\InventarioMovimiento::TIPOS_SALIDA as $clave => $label)
                                        <option value="{{ $clave }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">
                                    Habitación <span class="text-red-500" x-show="tipoSalida === 'solicitud_huesped'">*</span>
                                </label>
                                <input type="text" name="habitacion" maxlength="20"
                                    :required="tipoSalida === 'solicitud_huesped'"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                                    placeholder="Ej: 305">
                            </div>
                            <div class="col-span-2" x-show="tipoSalida === 'solicitud_huesped'" x-cloak>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nombre del huésped (opcional)</label>
                                <input type="text" name="huesped_nombre" maxlength="150"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-900 focus:ring focus:ring-blue-100"
                                    placeholder="Ej: Juan Pérez">
                            </div>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="tipo = ''"
                                class="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                :class="tipo === 'entrada' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                                class="px-4 py-2 text-sm text-white font-bold rounded-lg transition">
                                Confirmar <span x-text="tipo"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Descripción --}}
            @if($item->descripcion)
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">Descripción</h3>
                <p class="text-gray-600 text-sm">{{ $item->descripcion }}</p>
            </div>
            @endif

            {{-- Historial de movimientos --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-gray-500 uppercase">Historial de movimientos</h3>
                    <span class="text-xs text-gray-400">{{ $item->movimientos->count() }} registro(s)</span>
                </div>
                @if($item->movimientos->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                    <th class="py-2 px-4 text-left">Fecha</th>
                                    <th class="py-2 px-4 text-left">Tipo</th>
                                    <th class="py-2 px-4 text-right">Cantidad</th>
                                    <th class="py-2 px-4 text-right">Stock resultante</th>
                                    <th class="py-2 px-4 text-left">Habitación</th>
                                    <th class="py-2 px-4 text-left">Motivo</th>
                                    <th class="py-2 px-4 text-left">Registró</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($item->movimientos->sortByDesc('created_at') as $mov)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4 text-gray-500 text-xs whitespace-nowrap">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2 px-4">
                                        <span class="text-xs font-bold {{ $mov->tipo == 'entrada' ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }} px-2 py-1 rounded-full">
                                            {{ $mov->tipo == 'entrada' ? '↑ Entrada' : '↓ Salida' }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 text-right font-bold {{ $mov->tipo == 'entrada' ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $mov->tipo == 'entrada' ? '+' : '-' }}{{ $mov->cantidad }} {{ $item->unidad }}
                                    </td>
                                    <td class="py-2 px-4 text-right text-gray-600 font-bold">{{ $mov->cantidad_nueva }} {{ $item->unidad }}</td>
                                    <td class="py-2 px-4 text-gray-500 text-xs">
                                        @if($mov->habitacion)
                                            <span class="font-bold text-gray-700">Hab. {{ $mov->habitacion }}</span>
                                            @if($mov->huesped_nombre)<br><span class="text-gray-400">{{ $mov->huesped_nombre }}</span>@endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-gray-500 text-xs">
                                        {{ $mov->motivo ?? '—' }}
                                        @if($mov->tipoSalidaLabel())
                                            <br><span class="text-[10px] uppercase font-bold text-indigo-500">{{ $mov->tipoSalidaLabel() }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-gray-500 text-xs">{{ $mov->usuario->name ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-10 text-center text-gray-400 text-sm">
                        Sin movimientos registrados aún.
                    </div>
                @endif
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-4">
            {{-- Foto --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2">
                <div class="aspect-square bg-gray-50 rounded overflow-hidden flex items-center justify-center">
                    @if($item->imagen_path)
                        <img src="{{ Storage::url($item->imagen_path) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-medium text-gray-400">Sin Foto</span>
                    @endif
                </div>
            </div>

            {{-- PDF --}}
            @if($item->pdf_path)
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-800 uppercase">Documentación</span>
                    <a href="{{ Storage::url($item->pdf_path) }}" target="_blank"
                        class="bg-white hover:bg-gray-50 text-blue-700 border border-blue-200 text-xs font-bold py-2 px-3 rounded shadow-sm">
                        Ver PDF
                    </a>
                </div>
            @endif

            {{-- Meta --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-2 text-xs text-gray-500">
                <div><span class="font-bold text-gray-700">Hotel:</span> {{ $item->hotel_code ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Unidad:</span> {{ $item->unidad ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Registrado por:</span> {{ $item->usuario->name ?? '—' }}</div>
                <div><span class="font-bold text-gray-700">Creado:</span> {{ $item->created_at->format('d/m/Y') }}</div>
                <div><span class="font-bold text-gray-700">Última actualización:</span> {{ $item->updated_at->format('d/m/Y H:i') }}</div>
                <div><span class="font-bold text-gray-700">Movimientos:</span> {{ $item->movimientos->count() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection