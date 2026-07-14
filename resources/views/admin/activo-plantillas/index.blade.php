@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-8 px-4">

    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Áreas & Plantillas de Activos</h2>
            <p class="text-slate-500 font-medium">Define qué campos registra cada área de tu propiedad.</p>
        </div>
        <a href="{{ route('admin.activo-plantillas.create') }}"
           class="bg-[#041b32] text-white px-6 py-2 rounded-lg font-bold shadow hover:opacity-90 transition">
            + Nueva Área
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($plantillas->isEmpty())
        <div class="py-20 text-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
            <p class="text-slate-400 text-lg font-bold">No hay áreas configuradas.</p>
            <p class="text-slate-300 text-sm mt-1">Crea tu primera área para comenzar a registrar activos.</p>
            <a href="{{ route('admin.activo-plantillas.create') }}"
               class="inline-block mt-4 bg-[#041b32] text-white px-6 py-2 rounded-lg font-bold">
                + Crear primera área
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($plantillas as $p)
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-lg font-black text-[#041b32]">{{ $p->area_nombre }}</h3>
                            @if($p->activa)
                                <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded-full">ACTIVA</span>
                            @else
                                <span class="text-[10px] font-bold bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">INACTIVA</span>
                            @endif
                            <span class="text-[10px] font-bold bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
                                {{ $p->items()->count() }} ítems
                            </span>
                        </div>
                        @if($p->descripcion)
                            <p class="text-sm text-slate-500 mb-3">{{ $p->descripcion }}</p>
                        @endif

                        {{-- Chips de campos --}}
                        <div class="flex flex-wrap gap-2">
                            @forelse($p->campos as $campo)
                                <span class="flex items-center gap-1 text-xs font-semibold bg-slate-100 text-slate-600 px-3 py-1 rounded-full">
                                    {{ $campo['nombre'] }}
                                    <span class="text-[9px] text-slate-400 font-normal">({{ $campo['tipo'] }})</span>
                                    @if(!empty($campo['requerido']))
                                        <span class="text-red-400 font-bold">*</span>
                                    @endif
                                </span>
                            @empty
                                <span class="text-xs text-slate-400 italic">Sin campos definidos</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex gap-2 ml-6">
                        <a href="{{ route('admin.activo-plantillas.edit', $p->id) }}"
                           class="bg-blue-50 text-blue-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 hover:text-white transition">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('admin.activo-plantillas.destroy', $p->id) }}"
                              onsubmit="return confirm('¿Eliminar el área {{ $p->area_nombre }}? Esta acción no se puede deshacer.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="bg-red-50 text-red-500 px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-500 hover:text-white transition">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
