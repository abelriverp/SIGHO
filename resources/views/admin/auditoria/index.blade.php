@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Bitácora de Auditoría</h2>
            <p class="text-gray-500 text-sm">Acciones administrativas críticas: usuarios y propiedades.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="accion" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todas las acciones</option>
                @foreach($acciones as $accion)
                    <option value="{{ $accion }}" {{ request('accion') === $accion ? 'selected' : '' }}>{{ $accion }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Fecha</th>
                    <th class="py-3 px-6">Usuario</th>
                    <th class="py-3 px-6">Acción</th>
                    <th class="py-3 px-6">Detalle</th>
                    <th class="py-3 px-6">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-b border-slate-50 hover:bg-slate-50 align-top">
                    <td class="py-4 px-6 text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-4 px-6 font-medium text-slate-800">{{ $log->usuario->name ?? 'Sistema' }}</td>
                    <td class="py-4 px-6">
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700">{{ $log->accion }}</span>
                    </td>
                    <td class="py-4 px-6 text-slate-700">{{ $log->descripcion }}</td>
                    <td class="py-4 px-6 font-mono text-[10px] text-slate-400">{{ $log->ip }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-gray-500">Todavía no hay movimientos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
