@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Detector de Extensiones</h2>
            <p class="text-sm text-slate-500">Historial de comparaciones salidas vs llegadas.</p>
        </div>
        <a href="{{ route('recepcion.extensiones.create') }}" class="bg-[#27AE60] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nueva comparación</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Salidas</th>
                    <th class="py-3 px-6">Llegadas</th>
                    <th class="py-3 px-6">Fecha</th>
                    <th class="py-3 px-6 text-center">Posibles extensiones</th>
                    <th class="py-3 px-6">Comparado por</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detecciones as $d)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-3 px-6 font-medium text-slate-800">
                        <a href="{{ route('recepcion.extensiones.show', $d) }}" class="hover:underline">{{ $d->archivo_salidas }}</a>
                    </td>
                    <td class="py-3 px-6 text-slate-600">{{ $d->archivo_llegadas }}</td>
                    <td class="py-3 px-6 text-slate-500">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-6 text-center">
                        <span class="text-[10px] font-bold px-2 py-1 rounded uppercase {{ $d->total_coincidencias > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-200 text-slate-500' }}">
                            {{ $d->total_coincidencias }}
                        </span>
                    </td>
                    <td class="py-3 px-6 text-slate-500">{{ $d->usuario->name ?? '—' }}</td>
                    <td class="py-3 px-6 text-right">
                        <a href="{{ route('recepcion.extensiones.show', $d) }}" class="text-blue-500 hover:text-blue-700 text-xs font-bold mr-3">Ver</a>
                        @can('delete', $d)
                        <form action="{{ route('recepcion.extensiones.destroy', $d) }}" method="POST" class="inline"
                            onsubmit="return confirm('¿Eliminar esta comparación? No se puede deshacer.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold">Eliminar</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-6 px-6 text-center text-slate-400">Todavía no hay comparaciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $detecciones->links() }}</div>
</div>
@endsection
