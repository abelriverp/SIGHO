@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-8 px-4">

    <div class="mb-6">
        <a href="{{ route('admin.activo-plantillas.index') }}" class="text-slate-400 text-sm hover:text-slate-700 font-medium">
            ← Volver a Áreas
        </a>
        <h2 class="text-3xl font-bold text-[#041b32] mt-2">
            {{ isset($plantilla) ? 'Editar Área: ' . $plantilla->area_nombre : 'Nueva Área de Activos' }}
        </h2>
        <p class="text-slate-500 text-sm mt-1">Define los campos que los usuarios llenarán al registrar un ítem en esta área.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ isset($plantilla) ? route('admin.activo-plantillas.update', $plantilla->id) : route('admin.activo-plantillas.store') }}"
          id="formPlantilla">
        @csrf
        @if(isset($plantilla)) @method('PUT') @endif

        {{-- Datos del área --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm mb-6">
            <h3 class="text-sm font-black text-slate-500 uppercase mb-4">Información del Área</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Nombre del Área *</label>
                    <input type="text" name="area_nombre"
                           value="{{ old('area_nombre', $plantilla->area_nombre ?? '') }}"
                           placeholder="Ej: Habitaciones, Restaurante, Gym..."
                           required
                           class="w-full border-slate-200 rounded-lg p-2 text-sm font-bold focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Descripción</label>
                    <input type="text" name="descripcion"
                           value="{{ old('descripcion', $plantilla->descripcion ?? '') }}"
                           placeholder="Ej: Activos tecnológicos en cuartos de huéspedes"
                           class="w-full border-slate-200 rounded-lg p-2 text-sm focus:ring-blue-500">
                </div>
            </div>

            @if(isset($plantilla))
            <div class="flex items-center gap-2">
                <input type="checkbox" name="activa" id="activa" value="1"
                       {{ old('activa', $plantilla->activa) ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600">
                <label for="activa" class="text-sm font-semibold text-slate-600">Área activa (disponible para registro de ítems)</label>
            </div>
            @endif
        </div>

        {{-- Constructor de campos --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-sm font-black text-slate-500 uppercase">Campos de la Plantilla</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Estos son los datos que se pedirán al registrar un ítem en esta área.</p>
                </div>
                <button type="button" onclick="agregarCampo()"
                        class="bg-[#041b32] text-white px-4 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition">
                    + Agregar Campo
                </button>
            </div>

            <div id="listaCampos" class="space-y-3">
                @php
                    $camposExistentes = old('campos', $plantilla->campos ?? []);
                @endphp
                @foreach($camposExistentes as $i => $campo)
                    <div class="campo-row bg-slate-50 border border-slate-200 rounded-xl p-4 flex gap-3 items-start" data-index="{{ $i }}">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Nombre del campo *</label>
                                <input type="text" name="campos[{{ $i }}][nombre]"
                                       value="{{ $campo['nombre'] ?? '' }}"
                                       placeholder="Ej: Modelo, Serie, MAC..."
                                       class="w-full border-slate-200 rounded-lg p-2 text-sm">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Tipo</label>
                                <select name="campos[{{ $i }}][tipo]"
                                        onchange="toggleOpciones(this)"
                                        class="w-full border-slate-200 rounded-lg p-2 text-sm">
                                    @foreach($tiposCampo as $val => $label)
                                        <option value="{{ $val }}" {{ ($campo['tipo'] ?? 'text') === $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="opciones-container {{ ($campo['tipo'] ?? 'text') !== 'select' ? 'hidden' : '' }}">
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Opciones (separadas por coma)</label>
                                <input type="text" name="campos[{{ $i }}][opciones]"
                                       value="{{ isset($campo['opciones']) ? implode(', ', (array)$campo['opciones']) : '' }}"
                                       placeholder="Bueno, Regular, Dañado"
                                       class="w-full border-slate-200 rounded-lg p-2 text-sm">
                            </div>
                        </div>
                        <div class="flex flex-col items-center gap-2 pt-5">
                            <label class="flex items-center gap-1 text-xs text-slate-500 cursor-pointer">
                                <input type="checkbox" name="campos[{{ $i }}][requerido]" value="1"
                                       {{ !empty($campo['requerido']) ? 'checked' : '' }}
                                       class="w-3 h-3">
                                Req.
                            </label>
                            <button type="button" onclick="eliminarCampo(this)"
                                    class="text-red-400 hover:text-red-600 text-lg font-bold leading-none">×</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <p id="sinCampos" class="text-center text-slate-400 text-sm italic py-6 {{ count($camposExistentes) > 0 ? 'hidden' : '' }}">
                Aún no has agregado campos. Haz clic en "+ Agregar Campo".
            </p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.activo-plantillas.index') }}"
               class="px-6 py-2 rounded-xl font-bold text-slate-400 hover:bg-slate-100">Cancelar</a>
            <button type="submit"
                    class="bg-[#041b32] text-white px-10 py-3 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                {{ isset($plantilla) ? 'GUARDAR CAMBIOS' : 'CREAR ÁREA' }}
            </button>
        </div>
    </form>
</div>

<script>
    let campoIndex = {{ count($camposExistentes ?? []) }};

    const tiposCampo = @json($tiposCampo);

    function agregarCampo() {
        const idx = campoIndex++;
        const selectOpts = Object.entries(tiposCampo)
            .map(([val, label]) => `<option value="${val}">${label}</option>`)
            .join('');

        const html = `
        <div class="campo-row bg-slate-50 border border-slate-200 rounded-xl p-4 flex gap-3 items-start" data-index="${idx}">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Nombre del campo *</label>
                    <input type="text" name="campos[${idx}][nombre]"
                           placeholder="Ej: Modelo, Serie, MAC..."
                           class="w-full border-slate-200 rounded-lg p-2 text-sm">
                </div>
                <div>
                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Tipo</label>
                    <select name="campos[${idx}][tipo]" onchange="toggleOpciones(this)"
                            class="w-full border-slate-200 rounded-lg p-2 text-sm">
                        ${selectOpts}
                    </select>
                </div>
                <div class="opciones-container hidden">
                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Opciones (separadas por coma)</label>
                    <input type="text" name="campos[${idx}][opciones]"
                           placeholder="Bueno, Regular, Dañado"
                           class="w-full border-slate-200 rounded-lg p-2 text-sm">
                </div>
            </div>
            <div class="flex flex-col items-center gap-2 pt-5">
                <label class="flex items-center gap-1 text-xs text-slate-500 cursor-pointer">
                    <input type="checkbox" name="campos[${idx}][requerido]" value="1" class="w-3 h-3">
                    Req.
                </label>
                <button type="button" onclick="eliminarCampo(this)"
                        class="text-red-400 hover:text-red-600 text-lg font-bold leading-none">×</button>
            </div>
        </div>`;

        document.getElementById('listaCampos').insertAdjacentHTML('beforeend', html);
        document.getElementById('sinCampos').classList.add('hidden');
    }

    function eliminarCampo(btn) {
        btn.closest('.campo-row').remove();
        if (document.querySelectorAll('.campo-row').length === 0) {
            document.getElementById('sinCampos').classList.remove('hidden');
        }
    }

    function toggleOpciones(select) {
        const container = select.closest('.grid').querySelector('.opciones-container');
        if (select.value === 'select') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }
</script>
@endsection
