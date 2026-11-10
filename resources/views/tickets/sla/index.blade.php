@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Política de SLA</h2>
            <p class="text-sm text-slate-500">Horas para resolver un ticket según departamento y prioridad. Lo que no se configure aquí usa el valor por defecto del sistema.</p>
        </div>
        <a href="{{ route('tickets.sla.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nueva política</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    {{-- Resumen: valores EFECTIVOS por departamento (incluye los defaults del sistema si no hay override) --}}
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-3 border-b bg-slate-50">
            <h3 class="font-bold text-slate-700 text-sm">Vista general (valores que aplican hoy)</h3>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Departamento</th>
                    <th class="py-3 px-6">Alta</th>
                    <th class="py-3 px-6">Media</th>
                    <th class="py-3 px-6">Baja</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumen as $fila)
                <tr class="border-b border-slate-50">
                    <td class="py-3 px-6 font-medium text-slate-800">{{ $fila['departamento']->nombre }}</td>
                    @foreach(['Alta', 'Media', 'Baja'] as $prioridad)
                        <td class="py-3 px-6 text-slate-600">
                            {{ $fila['valores'][$prioridad]['horas_resolucion'] }}h
                            <span class="text-slate-400 text-xs">(avisa {{ $fila['valores'][$prioridad]['horas_alerta_temprana'] }}h antes)</span>
                        </td>
                    @endforeach
                </tr>
                @endforeach
                @if(count($resumen) === 0)
                <tr><td colspan="4" class="py-6 px-6 text-center text-slate-400">No hay departamentos activos.</td></tr>
                @endif
            </tbody>
        </table>
        </div>
    </div>

    {{-- Overrides configurados explícitamente para esta propiedad --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-3 border-b bg-slate-50">
            <h3 class="font-bold text-slate-700 text-sm">Políticas configuradas ({{ $hotelActivo }})</h3>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Departamento</th>
                    <th class="py-3 px-6">Prioridad</th>
                    <th class="py-3 px-6">Horas de resolución</th>
                    <th class="py-3 px-6">Alerta previa</th>
                    <th class="py-3 px-6">Estado</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($politicas as $politica)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $politica->departamento->nombre ?? 'Todos los departamentos' }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $politica->prioridad }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $politica->horas_resolucion }}h</td>
                    <td class="py-4 px-6 text-slate-600">{{ $politica->horas_alerta_temprana ?? '—' }}</td>
                    <td class="py-4 px-6">
                        @if($politica->activo)
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activa</span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactiva</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-right flex justify-end gap-3">
                        <a href="{{ route('tickets.sla.edit', $politica->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>
                        <form action="{{ route('tickets.sla.destroy', $politica->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta política? Ese departamento/prioridad volverá a usar el valor por defecto del sistema.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-6 px-6 text-center text-slate-400">No hay políticas configuradas todavía — se está usando el default del sistema (Alta 24h, Media 72h, Baja 168h) para todos los departamentos.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
