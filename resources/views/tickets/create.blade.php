@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">Registrar Falla o Requerimiento</h3>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <p class="font-bold">No se pudo guardar el reporte:</p>
                    <ul class="list-disc ml-5 mt-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Fila 1: Usuario y Área --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Usuario que reporta</label>
                    <input type="text" value="{{ auth()->user()->name }}" 
                        class="w-full bg-gray-100 border border-slate-300 rounded-md py-2 px-3 text-slate-500 cursor-not-allowed" readonly>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Tu Área o Departamento</label>
                    <select name="area" required class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                        <option value="" disabled selected>Selecciona tu área...</option>
                        @foreach(\App\Models\Area::orderBy('nombre')->get() as $area)
                            <option value="{{ $area->nombre }}" {{ old('area') == $area->nombre ? 'selected' : '' }}>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Fila 2: Ubicación específica y Departamento destino --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Equipo o Ubicación Específica</label>
                    <input type="text" name="descripcion_ubicacion" 
                        value="{{ old('descripcion_ubicacion') }}"
                        placeholder="Ej: PC Recepción, Impresora Piso 2..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                    <p class="text-[10px] text-slate-400 mt-1 italic">Opcional pero ayuda a atenderte más rápido.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Enviar reporte a</label>
                    <select name="departamento_destino" required class="w-full bg-blue-50 border border-blue-200 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="" disabled selected>Selecciona departamento...</option>
                        <option value="Sistemas" {{ old('departamento_destino') == 'Sistemas' ? 'selected' : '' }}>Sistemas / IT</option>
                        <option value="Mantenimiento" {{ old('departamento_destino') == 'Mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                        <option value="Seguridad" {{ old('departamento_destino') == 'Seguridad' ? 'selected' : '' }}>Seguridad</option>
                        <option value="Ama de Llaves" {{ old('departamento_destino') == 'Ama de Llaves' ? 'selected' : '' }}>Ama de Llaves</option>
                    </select>
                </div>
            </div>

            {{-- Nivel de urgencia --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nivel de Urgencia</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="flex items-center gap-2 border border-green-300 bg-green-50 rounded-md px-3 py-2 cursor-pointer hover:bg-green-100 transition">
                        <input type="radio" name="prioridad" value="Baja" {{ old('prioridad', 'Media') == 'Baja' ? 'checked' : '' }} class="accent-green-600">
                        <div>
                            <p class="text-sm font-bold text-green-700">Baja</p>
                            <p class="text-[10px] text-green-600">No afecta operación</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 border border-yellow-300 bg-yellow-50 rounded-md px-3 py-2 cursor-pointer hover:bg-yellow-100 transition">
                        <input type="radio" name="prioridad" value="Media" {{ old('prioridad', 'Media') == 'Media' ? 'checked' : '' }} class="accent-yellow-600">
                        <div>
                            <p class="text-sm font-bold text-yellow-700">Media</p>
                            <p class="text-[10px] text-yellow-600">Molesto pero operable</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 border border-red-300 bg-red-50 rounded-md px-3 py-2 cursor-pointer hover:bg-red-100 transition">
                        <input type="radio" name="prioridad" value="Alta" {{ old('prioridad') == 'Alta' ? 'checked' : '' }} class="accent-red-600">
                        <div>
                            <p class="text-sm font-bold text-red-700">Alta</p>
                            <p class="text-[10px] text-red-600">Operación detenida</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Descripción Detallada del Problema</label>
                <textarea name="descripcion" rows="4" required 
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]" 
                    placeholder="Explica detalladamente qué sucede, desde cuándo y qué intentaste hacer...">{{ old('descripcion') }}</textarea>
            </div>

            {{-- Archivo --}}
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Foto o Video de Evidencia <span class="text-slate-400 font-normal">(Opcional)</span></label>
                <input type="file" name="archivo" accept="image/*,video/*" 
                    class="w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:py-2 file:px-4 file:rounded file:border-0 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1 italic">Formatos permitidos: JPG, PNG, MP4. Máximo 20MB.</p>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('tickets.index') }}" 
                    class="bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-300 transition">
                    Cancelar
                </a>
                <button type="submit" 
                    class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90 transition">
                    Enviar Reporte
                </button>
            </div>
        </form>
    </div>
</div>
@endsection