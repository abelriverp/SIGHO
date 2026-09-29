@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">
            @if($esAdmin)
                Operadores
            @else
                Operadores de {{ auth()->user()->departamento ?? 'tu departamento' }}
            @endif
        </h2>
        <p class="text-sm text-slate-500">
            Personas que ejecutan trabajo en campo pero no tienen cuenta en el sistema.
            @if($esAdmin)
                Catálogo completo, agrupado por propiedad y departamento.
            @else
                Este catálogo es <strong>compartido con todos los técnicos de tu mismo departamento</strong> — cualquiera de ustedes puede agregar, renombrar o desactivar un operador. Regístralos aquí para poder marcarlos al resolver un ticket y llevar constancia de cuántos tickets sacó cada quien.
            @endif
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    {{-- Alta de operador --}}
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form action="{{ route('tickets.operadores.store') }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-slate-500 mb-1">Nombre del operador</label>
                <input type="text" name="nombre" required maxlength="120" placeholder="Ej. Juan Pérez"
                    class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            @if($esAdmin)
            <div class="min-w-[220px]">
                <label class="block text-xs font-bold text-slate-500 mb-1">Técnico (define propiedad y departamento)</label>
                <select name="tecnico_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    @foreach($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id }}">{{ $tecnico->name }} — {{ $tecnico->departamento ?? 'sin depto.' }} ({{ $tecnico->hotel_code }})</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">+ Agregar</button>
        </form>
    </div>

    @if($esAdmin)
        @forelse($operadores as $grupoLabel => $grupo)
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="px-6 py-3 border-b bg-slate-50">
                    <h3 class="font-bold text-slate-700 text-sm">{{ $grupoLabel }}</h3>
                </div>
                @include('operadores._tabla', ['operadores' => $grupo])
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-6 text-center text-slate-400">Todavía no hay operadores registrados.</div>
        @endforelse
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            @include('operadores._tabla', ['operadores' => $operadores])
        </div>
    @endif
</div>
@endsection
