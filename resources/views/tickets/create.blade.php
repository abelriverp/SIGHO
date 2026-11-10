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
                    @if(auth()->user()->area)
                        {{-- Fija desde tu cuenta (la configura un administrador en
                             Usuarios), para que no cambie de un ticket a otro. --}}
                        <input type="text" name="area" value="{{ auth()->user()->area }}" readonly
                            class="w-full bg-gray-100 border border-slate-300 rounded-md py-2 px-3 text-slate-500 cursor-not-allowed">
                    @else
                        <select name="area" required class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                            <option value="" disabled selected>Selecciona tu área...</option>
                            @foreach(\App\Models\Area::orderBy('nombre')->get() as $area)
                                <option value="{{ $area->nombre }}" {{ old('area') == $area->nombre ? 'selected' : '' }}>
                                    {{ $area->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-amber-600 mt-1 italic">Tu cuenta aún no tiene un área asignada. Pide a un administrador que la configure en Usuarios; mientras tanto, selecciónala aquí.</p>
                    @endif
                </div>
            </div>

            {{-- Fila 2 (Fase 2): Ubicación y equipo afectado, ligados al catálogo de Activos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6"
                 x-data="{ ubicacion: @js((string) old('ubicacion_id', '')), activo: @js((string) old('asset_id', '')), activos: @js($activosTicket->map(fn ($a) => ['id' => (string) $a->id, 'texto' => $a->nomenclatura . ' · ' . ($a->tipo_equipo ?: $a->nombre), 'ubicacion' => (string) $a->ubicacion_id])->values()) }">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Habitación o área</label>
                    <select name="ubicacion_id" x-model="ubicacion" @change="if (activo && !activos.find(a => a.id === activo && (!ubicacion || a.ubicacion === ubicacion))) activo = ''"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="">— No aplica / no sé —</option>
                        @foreach($ubicacionesTicket->groupBy('tipo') as $tipo => $grupo)
                            <optgroup label="{{ \App\Models\Ubicacion::TIPOS[$tipo] ?? $tipo }}">
                                @foreach($grupo as $u)
                                    <option value="{{ $u->id }}">{{ $u->etiqueta }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1 italic">Así el historial de la habitación o área queda completo.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Equipo afectado</label>
                    <select name="asset_id" x-model="activo"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="">— No aplica / no sé —</option>
                        <template x-for="a in activos.filter(a => !ubicacion || a.ubicacion === ubicacion)" :key="a.id">
                            <option :value="a.id" x-text="a.texto" :selected="a.id === activo"></option>
                        </template>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1 italic">Si eliges la habitación o área, solo verás sus equipos.</p>
                </div>
            </div>

            {{-- Fila 3: Detalle libre de ubicación y Departamento destino --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Detalle de ubicación o equipo</label>
                    <input type="text" name="descripcion_ubicacion" 
                        value="{{ old('descripcion_ubicacion') }}"
                        placeholder="Ej: impresora junto a caja 2, TV del lobby..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                    <p class="text-[10px] text-slate-400 mt-1 italic">Opcional: úsalo si el equipo no aparece en la lista.</p>
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

            {{-- Sección dinámica exclusiva de Sistemas — pintada por JS a partir
                 del catálogo único config/tickets_sistemas.php --}}
            <div id="sistemasExtra" class="hidden bg-blue-50/50 border border-blue-200 rounded-lg p-4 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Tipo de solicitud</label>
                    <select id="tipoSolicitudSelect" name="tipo_solicitud"
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="" selected>Selecciona una opción...</option>
                    </select>
                </div>

                <div id="sistemaWrapper" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Sistema</label>
                    <select id="sistemaSelect" name="sistema"
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                        <option value="" selected>Selecciona un sistema...</option>
                    </select>
                </div>

                <p id="notaTipoSolicitud" class="hidden text-xs text-blue-700 bg-blue-100 border border-blue-200 rounded p-2"></p>

                <div id="camposDinamicos" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
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

            {{-- Foto / Video / Documento de Evidencia --}}
            <div x-data="evidenciaMultiple()">
                <label class="block text-sm font-bold text-slate-700 mb-1">Foto, Video o Documento de Evidencia <span class="text-slate-400 font-normal">(Opcional)</span></label>
                <input type="file" id="inputArchivos" name="archivos[]"
                    accept="image/jpeg,image/png,image/jpg,video/mp4,video/quicktime,application/pdf,text/plain"
                    multiple
                    @change="agregarArchivos($event)"
                    class="w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:py-2 file:px-4 file:rounded file:border-0 cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1 italic">
                    Puedes seleccionar varias fotos a la vez (mantén Ctrl o Shift al hacer clic), o volver a hacer clic en el botón para ir agregando de una en una.
                    Máximo 6 archivos, 6 MB cada uno. Formatos: JPG, PNG, MP4, MOV, PDF, TXT.
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
                                    <span class="text-lg" x-text="iconoPara(item.nombre)"></span>
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
                <p class="text-[10px] text-slate-400 mt-1" x-show="archivos.length > 0" x-text="archivos.length + ' de 6 archivos seleccionados'"></p>
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

                if (combinados.length > 6) {
                    alert('Máximo 6 archivos. Se tomarán solo los primeros 6.');
                    combinados = combinados.slice(0, 6);
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

            // Ícono de la previsualización cuando el archivo no es una imagen
            // (no tiene "preview" de URL.createObjectURL). Antes siempre se
            // mostraba 🎬 sin importar el tipo real, lo cual tenía sentido
            // cuando solo se aceptaban fotos/video pero confundía en cuanto
            // se permitieron PDF/TXT.
            iconoPara(nombre) {
                const ext = nombre.split('.').pop().toLowerCase();
                if (ext === 'pdf') return '📄';
                if (ext === 'txt') return '📝';
                if (['mp4', 'mov'].includes(ext)) return '🎬';
                return '📎';
            },
        };
    }

    // Catálogo dinámico de tipo de solicitud/sistema, administrable desde
    // Tickets -> Categorías de solicitud (App\Models\TicketCategoria). Ya
    // no es exclusivo de "Sistemas": viene agrupado por departamento, y el
    // bloque solo se muestra si el departamento elegido tiene categorías
    // activas para esta propiedad. Este mismo JSON es lo que valida el
    // controlador, así el formulario nunca puede desincronizarse de lo que
    // el backend acepta. Se navega Tipo -> Sistema: algunos tipos (ej.
    // "Otra falla / Otro") no tienen 'sistemas' y van directo a sus 'campos'.
    const catalogoPorDepartamento = @json($catalogoPorDepartamento ?? []);

    const departamentoSelect  = document.getElementById('departamentoDestino');
    const sistemasExtra       = document.getElementById('sistemasExtra');
    const tipoSolicitudSelect = document.getElementById('tipoSolicitudSelect');
    const sistemaWrapper      = document.getElementById('sistemaWrapper');
    const sistemaSelect       = document.getElementById('sistemaSelect');
    const notaTipoSolicitud   = document.getElementById('notaTipoSolicitud');
    const camposDinamicos     = document.getElementById('camposDinamicos');

    const oldTipoSolicitud = @json(old('tipo_solicitud'));
    const oldSistema        = @json(old('sistema'));
    const oldCampos          = @json(old('campos', []));

    // Catálogo del departamento actualmente seleccionado (o {} si no tiene).
    function catalogoTipos() {
        return catalogoPorDepartamento[departamentoSelect.value] || {};
    }

    // Pinta las opciones del select "Tipo de solicitud" a partir del catálogo.
    function poblarTipos() {
        tipoSolicitudSelect.innerHTML = '<option value="" selected>Selecciona una opción...</option>';
        Object.entries(catalogoTipos()).forEach(([clave, tipo]) => {
            const opt = document.createElement('option');
            opt.value = clave;
            opt.textContent = tipo.label;
            if (clave === oldTipoSolicitud) opt.selected = true;
            tipoSolicitudSelect.appendChild(opt);
        });
    }

    // Al elegir un tipo de solicitud: si define 'sistemas', repuebla ese
    // select; si no (ej. "Otra falla / Otro"), lo oculta y pinta sus
    // campos directamente.
    function poblarSistemas() {
        const tipo = tipoSolicitudSelect.value;
        sistemaSelect.innerHTML = '<option value="" selected>Selecciona un sistema...</option>';
        camposDinamicos.innerHTML = '';
        notaTipoSolicitud.classList.add('hidden');

        const definicion = catalogoTipos()[tipo];
        const tieneSistemas = definicion && definicion.sistemas;
        sistemaWrapper.classList.toggle('hidden', !tieneSistemas);
        sistemaSelect.required = Boolean(tieneSistemas);

        if (!definicion) return;

        if (!tieneSistemas) {
            poblarCampos();
            return;
        }

        Object.entries(definicion.sistemas).forEach(([clave, sistema]) => {
            const opt = document.createElement('option');
            opt.value = clave;
            opt.textContent = sistema.label;
            if (clave === oldSistema) opt.selected = true;
            sistemaSelect.appendChild(opt);
        });

        if (sistemaSelect.value) poblarCampos();
    }

    // Dibuja los campos (y la nota, si aplica) que correspondan al
    // tipo/sistema elegidos.
    function poblarCampos() {
        camposDinamicos.innerHTML = '';
        notaTipoSolicitud.classList.add('hidden');

        const tipo    = tipoSolicitudSelect.value;
        const tipoDef = catalogoTipos()[tipo];
        if (!tipoDef) return;

        const sistema = sistemaSelect.value;
        const definicion = tipoDef.sistemas ? tipoDef.sistemas[sistema] : tipoDef;
        if (!definicion) return;

        definicion.campos.forEach(campo => {
            const wrapper = document.createElement('div');
            const valorPrevio = (oldTipoSolicitud === tipo && (!tipoDef.sistemas || oldSistema === sistema))
                ? (oldCampos?.[campo.name] ?? '')
                : '';

            if (campo.type === 'textarea') {
                wrapper.className = 'md:col-span-2';
                wrapper.innerHTML = `
                    <label class="block text-sm font-bold text-slate-700 mb-1">${campo.label}</label>
                    <textarea name="campos[${campo.name}]" rows="3" ${campo.required ? 'required' : ''}
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]"></textarea>
                `;
                wrapper.querySelector('textarea').value = valorPrevio;
            } else {
                wrapper.innerHTML = `
                    <label class="block text-sm font-bold text-slate-700 mb-1">${campo.label}</label>
                    <input type="${campo.type}" name="campos[${campo.name}]" ${campo.required ? 'required' : ''}
                        class="w-full bg-white border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
                `;
                wrapper.querySelector('input').value = valorPrevio;
            }

            camposDinamicos.appendChild(wrapper);
        });

        if (definicion.nota) {
            notaTipoSolicitud.textContent = 'Nota: ' + definicion.nota;
            notaTipoSolicitud.classList.remove('hidden');
        }
    }

    function toggleSistemasExtra() {
        const tieneCategorias = Object.keys(catalogoTipos()).length > 0;
        sistemasExtra.classList.toggle('hidden', !tieneCategorias);
        tipoSolicitudSelect.required = tieneCategorias;
        poblarTipos();
        if (!tieneCategorias) {
            tipoSolicitudSelect.value = '';
        }
        poblarSistemas();
    }

    departamentoSelect.addEventListener('change', toggleSistemasExtra);
    tipoSolicitudSelect.addEventListener('change', poblarSistemas);
    sistemaSelect.addEventListener('change', poblarCampos);

    // Estado inicial (por si el formulario recarga con errores de validación)
    toggleSistemasExtra();
</script>
@endsection