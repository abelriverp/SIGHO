{{-- Tabla reutilizable de operadores (partial): recibe $operadores --}}
<table class="w-full text-left text-sm">
    <thead class="bg-slate-50 text-slate-600 border-b">
        <tr>
            <th class="py-3 px-6">Nombre</th>
            <th class="py-3 px-6">Tickets resueltos</th>
            <th class="py-3 px-6">Estado</th>
            <th class="py-3 px-6 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($operadores as $operador)
        <tr class="border-b border-slate-50 hover:bg-slate-50">
            <td class="py-3 px-6 font-medium text-slate-800">
                <form action="{{ route('tickets.operadores.update', $operador->id) }}" method="POST" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <input type="text" name="nombre" value="{{ $operador->nombre }}" maxlength="120"
                        class="border rounded px-2 py-1 text-sm w-40">
                    <button type="submit" class="text-blue-500 hover:text-blue-700 text-xs font-bold">Guardar</button>
                </form>
            </td>
            <td class="py-3 px-6 text-slate-600">{{ $operador->tickets_count }}</td>
            <td class="py-3 px-6">
                @if($operador->activo)
                    <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-green-100 text-green-700">Activo</span>
                @else
                    <span class="text-[10px] font-bold px-2 py-1 rounded uppercase bg-slate-200 text-slate-500">Inactivo</span>
                @endif
            </td>
            <td class="py-3 px-6 text-right">
                <form action="{{ route('tickets.operadores.estado', $operador->id) }}" method="POST"
                    onsubmit="return confirm('{{ $operador->activo ? '¿Desactivar' : '¿Activar' }} a {{ $operador->nombre }}?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-sm font-bold {{ $operador->activo ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }}">
                        {{ $operador->activo ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="py-6 px-6 text-center text-slate-400">No hay operadores registrados todavía.</td></tr>
        @endforelse
    </tbody>
</table>
