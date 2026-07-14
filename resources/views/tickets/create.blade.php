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
                    <select id="departamentoDestino" name="departamento_destino" required class="w-full bg-blue-50 border border-blue-200 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="" disabled selected>Selecciona departamento...</option>
                        @foreach(\App\Models\Departamento::activos()->orderBy('nombre')->get() as $depto)
                            <option value="{{ $depto->nombre }}" {{ old('departamento_destino') == $depto->nombre ? 'selected' : '' }}>
                                {{ $depto->nombre === 'Sistemas' ? 'Sistemas / IT' : $depto->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Sección dinámica exclusiva de Sistemas --}}
            <div id="sistemasExtra" class="hidden bg-blue-50/50 border border-blue-200 rounded-lg p-4 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Tipo de solicitud a Sistemas</label>
                    <select id="tipoSolicitudSistemas" name="tipo_solicitud_sistemas"
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="" selected>Selecciona una opción...</option>
                        <option value="alta_colaborador" {{ old('tipo_solicitud_sistemas') == 'alta_colaborador' ? 'selected' : '' }}>Dar alta de colaborador</option>
                        <option value="modificacion_sistema" {{ old('tipo_solicitud_sistemas') == 'modificacion_sistema' ? 'selected' : '' }}>Modificaciones de sistema</option>
                        <option value="otro" {{ old('tipo_solicitud_sistemas') == 'otro' ? 'selected' : '' }}>Otro (especificar)</option>
                    </select>
                </div>

                {{-- OTRO --}}
                <div id="seccionOtro" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Especifica el problema</label>
                    <textarea name="otro_detalle" rows="3"
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]"
                        placeholder="Describe el problema o solicitud...">{{ old('otro_detalle') }}</textarea>
                </div>

                {{-- ALTA DE COLABORADOR --}}
                <div id="seccionAlta" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Escoge el aplicativo</label>
                        <select id="aplicativoSelect" name="aplicativo"
                            class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                            <option value="" selected>Selecciona el aplicativo...</option>
                            <option value="symphony" {{ old('aplicativo') == 'symphony' ? 'selected' : '' }}>Symphony</option>
                            <option value="opera" {{ old('aplicativo') == 'opera' ? 'selected' : '' }}>Opera</option>
                            <option value="mail" {{ old('aplicativo') == 'mail' ? 'selected' : '' }}>Mail</option>
                            <option value="sap" {{ old('aplicativo') == 'sap' ? 'selected' : '' }}>SAP</option>
                            <option value="wintb" {{ old('aplicativo') == 'wintb' ? 'selected' : '' }}>WinTB</option>
                            <option value="guesthotel" {{ old('aplicativo') == 'guesthotel' ? 'selected' : '' }}>GuestHotel</option>
                        </select>
                    </div>

                    <div id="camposAplicativo" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">No. Colaborador</label>
                            <input type="text" name="no_colaborador" value="{{ old('no_colaborador') }}"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                            <input type="text" name="nombre_completo" value="{{ old('nombre_completo') }}"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Puesto</label>
                            <input type="text" name="puesto" value="{{ old('puesto') }}"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                        <div id="campoPerfilReplicar" class="hidden">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Perfil a replicar <span class="text-slate-400 font-normal">(Opcional)</span></label>
                            <input type="text" name="perfil_replicar" value="{{ old('perfil_replicar') }}"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                        <div id="campoCorreoInstitucional" class="hidden">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Correo electrónico institucional</label>
                            <input type="email" name="correo_institucional" value="{{ old('correo_institucional') }}"
                                placeholder="usuario@hotelesemporio.com"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                        <div id="campoJefeDirecto" class="hidden">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Jefe Directo</label>
                            <input type="text" name="jefe_directo" value="{{ old('jefe_directo') }}"
                                class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        </div>
                    </div>
                </div>

                {{-- MODIFICACIONES DE SISTEMA --}}
                <div id="seccionMod" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Escoge el aplicativo</label>
                        <select id="aplicativoModSelect" name="aplicativo_mod"
                            class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                            <option value="" selected>Selecciona el aplicativo...</option>
                            <option value="symphony" {{ old('aplicativo_mod') == 'symphony' ? 'selected' : '' }}>Symphony</option>
                            <option value="opera" {{ old('aplicativo_mod') == 'opera' ? 'selected' : '' }}>Opera</option>
                            <option value="mail" {{ old('aplicativo_mod') == 'mail' ? 'selected' : '' }}>Mail</option>
                            <option value="sap" {{ old('aplicativo_mod') == 'sap' ? 'selected' : '' }}>SAP</option>
                            <option value="wintb" {{ old('aplicativo_mod') == 'wintb' ? 'selected' : '' }}>WinTB</option>
                            <option value="guesthotel" {{ old('aplicativo_mod') == 'guesthotel' ? 'selected' : '' }}>GuestHotel</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Detalle de la modificación</label>
                        <textarea name="detalle_modificacion" rows="3"
                            class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]"
                            placeholder="Describe qué cambio necesitas...">{{ old('detalle_modificacion') }}</textarea>
                    </div>
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

            {{-- Fotos / Video de Evidencia --}}
            <div x-data="evidenciaMultiple()">
                <label class="block text-sm font-bold text-slate-700 mb-1">Foto o Video de Evidencia <span class="text-slate-400 font-normal">(Opcional)</span></label>
                <input type="file" id="inputArchivos" name="archivos[]"
                    accept="image/jpeg,image/png,image/jpg,video/mp4,video/quicktime"
                    multiple
                    @change="agregarArchivos($event)"
                    class="w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:py-2 file:px-4 file:rounded file:border-0 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1 italic">
                    Puedes seleccionar varias fotos a la vez (mantén Ctrl o Shift al hacer clic), o volver a hacer clic en el botón para ir agregando de una en una.
                    Máximo 5 archivos, 6 MB cada uno. Formatos: JPG, PNG, MP4, MOV.
                </p>

                {{-- Previsualización con opción de quitar --}}
                <div class="grid grid-cols-3 gap-2 mt-3" x-show="archivos.length > 0" x-cloak>
                    <template x-for="(item, i) in archivos" :key="item.nombre + '-' + i">
                        <div class="relative border border-slate-200 rounded-lg overflow-hidden bg-slate-100 aspect-square">
                            <template x-if="item.preview">
                                <img :src="item.preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.preview">
                                <div class="w-full h-full flex flex-col items-center justify-center text-[9px] text-slate-500 p-1 text-center break-all">
                                    <span class="text-lg">🎬</span>
                                    <span x-text="item.nombre"></span>
                                </div>
                            </template>
                            <button type="button" @click="quitar(i)"
                                class="absolute top-1 right-1 bg-black/60 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs leading-none">
                                ✕
                            </button>
                        </div>
                    </template>
                </div>
                <p class="text-[10px] text-slate-400 mt-1" x-show="archivos.length > 0" x-text="archivos.length + ' de 5 archivos seleccionados'"></p>
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

<script>
    // Maneja la selección acumulada de evidencia (fotos/video) al crear un ticket:
    // - Ctrl/Shift + clic en el explorador de archivos ya selecciona varias de una vez.
    // - Volver a hacer clic en "Seleccionar archivo" AGREGA a lo ya elegido, en vez de
    //   reemplazarlo (el input nativo por sí solo siempre reemplaza la selección).
    function evidenciaMultiple() {
        return {
            archivos: [], // [{ file, nombre, preview }]

            agregarArchivos(event) {
                const nuevos = Array.from(event.target.files);
                let combinados = this.archivos.map(a => a.file).concat(nuevos);

                if (combinados.length > 5) {
                    alert('Máximo 5 archivos. Se tomarán solo los primeros 5.');
                    combinados = combinados.slice(0, 5);
                }

                this.archivos = combinados.map(file => ({
                    file,
                    nombre: file.name,
                    preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                }));

                this.sincronizarInput();
            },

            quitar(i) {
                this.archivos.splice(i, 1);
                this.sincronizarInput();
            },

            // El <input type="file"> es el que realmente se envía en el formulario,
            // así que reconstruimos su lista de archivos cada vez que cambia el estado.
            sincronizarInput() {
                const dt = new DataTransfer();
                this.archivos.forEach(a => dt.items.add(a.file));
                document.getElementById('inputArchivos').files = dt.files;
            },
        };
    }

    const departamentoSelect       = document.getElementById('departamentoDestino');
    const sistemasExtra            = document.getElementById('sistemasExtra');
    const tipoSolicitudSistemas    = document.getElementById('tipoSolicitudSistemas');
    const seccionOtro              = document.getElementById('seccionOtro');
    const seccionAlta              = document.getElementById('seccionAlta');
    const seccionMod               = document.getElementById('seccionMod');
    const aplicativoSelect         = document.getElementById('aplicativoSelect');
    const aplicativoModSelect      = document.getElementById('aplicativoModSelect');
    const camposAplicativo         = document.getElementById('camposAplicativo');
    const campoPerfilReplicar      = document.getElementById('campoPerfilReplicar');
    const campoCorreoInstitucional = document.getElementById('campoCorreoInstitucional');
    const campoJefeDirecto         = document.getElementById('campoJefeDirecto');

    const otroDetalle          = document.querySelector('textarea[name="otro_detalle"]');
    const noColaborador        = document.querySelector('input[name="no_colaborador"]');
    const nombreCompleto       = document.querySelector('input[name="nombre_completo"]');
    const puesto                = document.querySelector('input[name="puesto"]');
    const correoInstitucional  = document.querySelector('input[name="correo_institucional"]');
    const jefeDirecto          = document.querySelector('input[name="jefe_directo"]');
    const detalleModificacion  = document.querySelector('textarea[name="detalle_modificacion"]');

    function actualizarRequeridos() {
        const esSistemas = departamentoSelect.value === 'Sistemas';
        const tipo = esSistemas ? tipoSolicitudSistemas.value : '';

        tipoSolicitudSistemas.required = esSistemas;
        otroDetalle.required = (tipo === 'otro');

        const esAlta = (tipo === 'alta_colaborador');
        aplicativoSelect.required = esAlta;
        const aplicativo = esAlta ? aplicativoSelect.value : '';
        const mostrarCampos = esAlta && aplicativo !== '';

        noColaborador.required  = mostrarCampos;
        nombreCompleto.required = mostrarCampos;
        puesto.required          = mostrarCampos;
        correoInstitucional.required = (mostrarCampos && aplicativo === 'sap');
        jefeDirecto.required         = (mostrarCampos && aplicativo === 'mail');

        const esMod = (tipo === 'modificacion_sistema');
        aplicativoModSelect.required = esMod;
        detalleModificacion.required = esMod;
    }

    function toggleAplicativo() {
        const aplicativo = aplicativoSelect.value;
        const mostrar = aplicativo !== '';
        camposAplicativo.classList.toggle('hidden', !mostrar);
        campoPerfilReplicar.classList.toggle('hidden', aplicativo !== 'opera');
        campoCorreoInstitucional.classList.toggle('hidden', aplicativo !== 'sap');
        campoJefeDirecto.classList.toggle('hidden', aplicativo !== 'mail');
        actualizarRequeridos();
    }

    function toggleTipoSolicitud() {
        const tipo = tipoSolicitudSistemas.value;
        seccionOtro.classList.toggle('hidden', tipo !== 'otro');
        seccionAlta.classList.toggle('hidden', tipo !== 'alta_colaborador');
        seccionMod.classList.toggle('hidden', tipo !== 'modificacion_sistema');
        if (tipo !== 'alta_colaborador') {
            aplicativoSelect.value = '';
            toggleAplicativo();
        }
        actualizarRequeridos();
    }

    function toggleSistemasExtra() {
        const esSistemas = departamentoSelect.value === 'Sistemas';
        sistemasExtra.classList.toggle('hidden', !esSistemas);
        if (!esSistemas) {
            tipoSolicitudSistemas.value = '';
            toggleTipoSolicitud();
        }
        actualizarRequeridos();
    }

    departamentoSelect.addEventListener('change', toggleSistemasExtra);
    tipoSolicitudSistemas.addEventListener('change', toggleTipoSolicitud);
    aplicativoSelect.addEventListener('change', toggleAplicativo);

    // Estado inicial (por si el formulario recarga con errores de validación)
    toggleSistemasExtra();
    if (tipoSolicitudSistemas.value) toggleTipoSolicitud();
    if (aplicativoSelect.value) toggleAplicativo();
</script>
@endsection