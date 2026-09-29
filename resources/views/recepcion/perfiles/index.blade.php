@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32]">Correos de Llegada</h2>
            <p class="text-sm text-slate-500">Historial de reportes "Profiles with e-mails" analizados.</p>
        </div>
        <a href="{{ route('recepcion.perfiles.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nuevo análisis</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Archivo</th>
                    <th class="py-3 px-6">Fecha</th>
                    <th class="py-3 px-6 text-center">Reservas</th>
                    <th class="py-3 px-6 text-center">OK</th>
                    <th class="py-3 px-6 text-center">Sin correo</th>
                    <th class="py-3 px-6 text-center">Sufijo malo</th>
                    <th class="py-3 px-6 text-center">Dominio malo</th>
                    <th class="py-3 px-6 text-center">Rechazados</th>
                    <th class="py-3 px-6">Subido por</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($validaciones as $v)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-3 px-6 font-medium text-slate-800">
                        <a href="{{ route('recepcion.perfiles.show', $v) }}" class="hover:underline">{{ $v->archivo_nombre }}</a>
                    </td>
                    <td class="py-3 px-6 text-slate-500">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-6 text-center">{{ $v->total }}</td>
                    <td class="py-3 px-6 text-center text-green-600 font-bold">{{ $v->ok }}</td>
                    <td class="py-3 px-6 text-center text-amber-600 font-bold">{{ $v->sin_correo }}</td>
                    <td class="py-3 px-6 text-center text-yellow-600 font-bold">{{ $v->sufijo_invalido }}</td>
                    <td class="py-3 px-6 text-center text-red-600 font-bold">{{ $v->dominio_invalido }}</td>
                    <td class="py-3 px-6 text-center text-red-800 font-bold">{{ $v->rechazado_smtp }}</td>
                    <td class="py-3 px-6 text-slate-500">{{ $v->usuario->name ?? '—' }}</td>
                    <td class="py-3 px-6 text-right whitespace-nowrap">
                        <a href="{{ route('recepcion.perfiles.show', $v) }}" class="text-blue-500 hover:text-blue-700 text-xs font-bold mr-3">Ver</a>
                        <a href="{{ route('recepcion.perfiles.pdf', $v) }}" class="text-[#2980B9] hover:text-[#1f618d] text-xs font-bold mr-3">PDF</a>
                        @can('delete', $v)
                        <form action="{{ route('recepcion.perfiles.destroy', $v) }}" method="POST" class="inline"
                            onsubmit="return confirm('¿Eliminar este análisis? No se puede deshacer.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold">Eliminar</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="py-6 px-6 text-center text-slate-400">Todavía no hay análisis registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $validaciones->links() }}</div>
</div>
@endsection
