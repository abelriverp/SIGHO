@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">Gestión de Usuarios</h2>
        <a href="{{ route('admin.users.create') }}" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Nuevo Usuario</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Nombre y Correo</th>
                    <th class="py-3 px-6">Rol</th>
                    <th class="py-3 px-6">Propiedad</th>
                    <th class="py-3 px-6 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-4 px-6">
                        <div class="font-bold text-slate-800">{{ $user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                    </td>
                    <td class="py-4 px-6">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ strtoupper($user->role) }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-xs">
                        @if($user->role === 'admin')
                            <span class="text-purple-600 font-bold">Todas</span>
                        @elseif($user->hotel)
                            {{ $user->hotel->nombre }}
                        @else
                            <span class="text-red-500 italic">Sin asignar</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-right">
                        <div class="flex justify-end items-center gap-3">

                            {{-- Botón Módulos solo para no-admin --}}
                            @if($user->role !== 'admin')
                            <a href="{{ route('admin.users.modulos', $user->id) }}"
                               class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                                🧩 Módulos
                                @php $count = $user->modulos()->where('modulos.activo', true)->count(); @endphp
                                @if($count > 0)
                                    <span class="ml-1 bg-indigo-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">{{ $count }}</span>
                                @endif
                            </a>
                            @endif

                            <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-sm">Editar</a>

                            @if($user->email !== 'admin@emporio.com')
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Eliminar</button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection