@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto mt-8 px-4">

    {{-- Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Inventario de Activos</h2>
            <p class="text-slate-500 font-medium">
                Gestión multi-área · cada ubicación y sus equipos.
                <span class="ml-2 bg-[#041b32] text-white text-xs font-black px-2 py-0.5 rounded">{{ $hotelCode }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            @if($plantillas->isNotEmpty())
            <button onclick="abrirModalRegistrar()"
                    class="bg-[#041b32] text-white px-6 py-2 rounded-lg font-bold shadow hover:opacity-90 transition no-print">
                + Agregar Ítem
            </button>
            @endif
            <button onclick="window.print()"
                    class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg font-bold border border-slate-300 hover:bg-slate-200 transition no-print">
                Imprimir
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">{{ session('error') }}</div>
    @endif

    @if($plantillas->isEmpty())
        <div class="py-20 text-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
            <p class="text-2xl mb-2">🏗️</p>
            <p class="text-slate-600 text-lg font-bold">Primero debes configurar las áreas</p>
            <p class="text-slate-400 text-sm mt-1 mb-6">Define qué áreas existen (Habitaciones, Restaurante, Gym...) y qué campos registran.</p>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.activo-plantillas.create') }}"
                   class="inline-block bg-[#041b32] text-white px-8 py-3 rounded-xl font-black shadow hover:opacity-90 transition">
                    ⚙️ Crear primera área
                </a>
                <p class="text-slate-300 text-xs mt-3">O ve a <strong>Admin → Áreas de Activos</strong> en el menú superior.</p>
            @else
                <p class="text-slate-400 text-sm">Pide a un administrador que configure las áreas desde el panel Admin.</p>
            @endif
        </div>

    @elseif($itemsPorArea->isEmpty())
        <div class="py-20 text-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
            <p class="text-slate-400 text-lg font-bold">No hay ítems registrados todavía.</p>
            <button onclick="abrirModalRegistrar()" class="mt-4 bg-[#041b32] text-white px-6 py-2 rounded-lg font-bold">
                + Agregar primer ítem
            </button>
        </div>

    @else
        {{-- Tabs por área --}}
        <div class="flex flex-wrap gap-2 mb-8 no-print">
            <button onclick="filtrarArea('all')" id="tab-all"
                    class="tab-btn bg-[#041b32] text-white px-4 py-1.5 rounded-full text-sm font-bold">
                Todas las áreas
            </button>
            @foreach($itemsPorArea as $area => $porUbicacion)
            <button onclick="filtrarArea('{{ Str::slug($area) }}')" id="tab-{{ Str::slug($area) }}"
                    class="tab-btn bg-slate-100 text-slate-600 px-4 py-1.5 rounded-full text-sm font-bold hover:bg-slate-200">
                {{ $area }}
                <span class="ml-1 text-xs opacity-60">{{ $porUbicacion->count() }}</span>
            </button>
            @endforeach
        </div>

        {{-- Sección por área --}}
        @foreach($itemsPorArea as $area => $porUbicacion)
        <div class="area-section mb-14" data-area="{{ Str::slug($area) }}">

            {{-- Encabezado del área --}}
            <h3 class="text-xl font-black text-slate-700 mb-6 flex items-center gap-3 border-b-2 border-slate-200 pb-2">
                <span class="bg-[#041b32] text-white px-3 py-1 rounded-lg text-sm">{{ strtoupper($area) }}</span>
                <span class="text-slate-400 text-sm font-medium">{{ $porUbicacion->count() }} ubicaciones registradas</span>
                <button onclick="abrirModalRegistrar('{{ addslashes($area) }}')"
                        class="ml-auto text-xs font-bold bg-slate-100 text-slate-500 hover:bg-[#041b32] hover:text-white px-3 py-1 rounded-lg transition no-print">
                    + Ítem en {{ $area }}
                </button>
            </h3>

            {{-- Grid de ubicaciones --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($porUbicacion as $ubicacion => $items)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-xl hover:border-blue-300 transition-all duration-300 group overflow-hidden">

                    {{-- Cabecera de la ubicación --}}
                    <div class="bg-slate-50 border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-[9px] uppercase font-bold text-slate-400">{{ $area }}</p>
                            <p class="text-xl font-black text-[#041b32] group-hover:text-blue-600 leading-tight">
                                {{ $ubicacion }}
                            </p>
                        </div>
                        <button onclick="abrirModalRegistrar('{{ addslashes($area) }}', '{{ addslashes($ubicacion) }}')"
                                title="Agregar ítem a {{ $ubicacion }}"
                                class="bg-blue-50 text-blue-500 hover:bg-blue-600 hover:text-white p-1.5 rounded-lg transition text-sm font-black no-print">
                            +
                        </button>
                    </div>

                    {{-- Ítems de la ubicación --}}
                    <div class="divide-y divide-slate-50">
                        @foreach($items as $item)
                        <div class="px-4 py-3">
                            <div class="flex justify-between items-start mb-1.5">
                                <p class="text-xs font-black text-slate-600">{{ $item->nombre_item }}</p>
                                <div class="flex gap-1 no-print">
                                    <button onclick='abrirModalEditar(@json($item))'
                                            class="text-blue-400 hover:text-blue-600 p-0.5 transition" title="Editar">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('activos.destroy', $item->id) }}"
                                          onsubmit="return confirm('¿Eliminar {{ $item->nombre_item }} de {{ $ubicacion }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-300 hover:text-red-500 p-0.5 transition" title="Eliminar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Valores del ítem --}}
                            @if($item->valores)
                            <div class="space-y-0.5">
                                @foreach($item->valores as $campo => $valor)
                                    @if(!empty($valor))
                                    <div class="flex justify-between gap-2">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 shrink-0">{{ $campo }}</span>
                                        <span class="text-[10px] font-mono text-slate-600 text-right truncate">{{ $valor }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            @endif

                            @if($item->notas)
                            <p class="text-[9px] italic text-yellow-600 mt-1 bg-yellow-50 px-1.5 py-0.5 rounded">📝 {{ $item->notas }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>

                </div>
                @endforeach
            </div>

        </div>
        @endforeach
    @endif
</div>

{{-- ===================== MODAL: REGISTRAR ÍTEM ===================== --}}
<div id="modalRegistrar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
        <div class="bg-[#041b32] p-5 text-white flex justify-between items-center">
            <div>
                <h3 class="text-lg font-black uppercase tracking-tight">Agregar Ítem</h3>
                <p class="text-blue-300 text-xs font-bold">Selecciona el área, la ubicación y qué estás registrando</p>
            </div>
            <button onclick="cerrarModalRegistrar()" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">✕</button>
        </div>

        <form method="POST" action="{{ route('activos.store') }}" class="p-6 max-h-[80vh] overflow-y-auto">
            @csrf

            {{-- Área --}}
            <div class="grid grid-cols-2 gap-4 mb-5 p-4 bg-blue-50 rounded-xl border border-blue-100">
                <div>
                    <label class="text-[10px] font-bold text-blue-600 uppercase block mb-1">Área *</label>
                    <select name="plantilla_id" id="selectorArea" required
                            onchange="cargarCamposPlantilla(this.value)"
                            class="w-full border-blue-200 rounded-lg p-2 text-sm font-bold">
                        <option value="">— Selecciona —</option>
                        @foreach($plantillas as $p)
                            <option value="{{ $p->id }}">{{ $p->area_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-blue-600 uppercase block mb-1">Ubicación * <span class="text-slate-400 font-normal normal-case">(Hab., Mesa, Zona...)</span></label>
                    <input type="text" name="ubicacion" id="input_ubicacion" required
                           placeholder="Ej: 101, Mesa 5, Lobby..."
                           class="w-full border-blue-200 rounded-lg p-2 text-sm font-bold">
                </div>
            </div>

            {{-- Nombre del ítem --}}
            <div class="mb-5">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">¿Qué estás registrando? *</label>
                <input type="text" name="nombre_item" required
                       placeholder="Ej: Access Point, TV, Cafetera, Laptop..."
                       class="w-full border-slate-200 rounded-lg p-2 text-sm">
            </div>

            {{-- Campos dinámicos de la plantilla --}}
            <div id="camposDinamicos" class="space-y-3 mb-4"></div>
            <p id="hintArea" class="text-center text-slate-300 text-xs italic py-2">
                Selecciona un área para ver los campos disponibles.
            </p>

            {{-- Notas --}}
            <div class="mb-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Notas</label>
                <textarea name="notas" rows="2" placeholder="Observaciones, estado físico, etc."
                          class="w-full border-slate-200 rounded-lg p-2 text-sm italic"></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="cerrarModalRegistrar()"
                        class="px-5 py-2 rounded-xl font-bold text-slate-400 hover:bg-slate-100">Cancelar</button>
                <button type="submit"
                        class="bg-[#041b32] text-white px-8 py-2.5 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                    REGISTRAR
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL: EDITAR ÍTEM ===================== --}}
<div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
        <div class="bg-[#041b32] p-5 text-white flex justify-between items-center">
            <div>
                <h3 class="text-lg font-black uppercase tracking-tight">Editar Ítem</h3>
                <p id="editSubtitle" class="text-blue-300 text-xs font-bold"></p>
            </div>
            <button onclick="cerrarModalEditar()" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">✕</button>
        </div>

        <form id="formEditar" method="POST" class="p-6 max-h-[80vh] overflow-y-auto">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Ubicación *</label>
                    <input type="text" name="ubicacion" id="edit_ubicacion" required
                           class="w-full border-slate-200 rounded-lg p-2 text-sm font-bold">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Nombre del ítem *</label>
                    <input type="text" name="nombre_item" id="edit_nombre" required
                           class="w-full border-slate-200 rounded-lg p-2 text-sm">
                </div>
            </div>

            <div id="editCampos" class="space-y-3 mb-4"></div>

            <div class="mb-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Notas</label>
                <textarea name="notas" id="edit_notas" rows="2"
                          class="w-full border-slate-200 rounded-lg p-2 text-sm italic"></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="cerrarModalEditar()"
                        class="px-5 py-2 rounded-xl font-bold text-slate-400 hover:bg-slate-100">Cancelar</button>
                <button type="submit"
                        class="bg-[#041b32] text-white px-8 py-2.5 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                    GUARDAR
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const plantillasData = @json($plantillas->keyBy('id'));

// Convierte nombre de campo en slug para el atributo name del input
function slugCampo(nombre) {
    return nombre.toLowerCase()
        .replace(/[áàä]/g,'a').replace(/[éèë]/g,'e')
        .replace(/[íìï]/g,'i').replace(/[óòö]/g,'o')
        .replace(/[úùü]/g,'u').replace(/ñ/g,'n')
        .replace(/[\s\-]/g,'_').replace(/[^a-z0-9_]/g,'');
}

function renderCampos(campos, valores, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    if (!campos || campos.length === 0) {
        container.innerHTML = '<p class="text-xs text-slate-400 italic text-center py-2">Esta área no tiene campos adicionales.</p>';
        return;
    }
    campos.forEach(campo => {
        const slug  = slugCampo(campo.nombre);
        const valor = valores ? (valores[campo.nombre] || '') : '';
        const req   = campo.requerido ? 'required' : '';
        const star  = campo.requerido ? '<span class="text-red-400">*</span>' : '';

        let input = '';
        if (campo.tipo === 'select' && campo.opciones) {
            const opts = campo.opciones.map(o =>
                `<option value="${o}" ${valor === o ? 'selected' : ''}>${o}</option>`
            ).join('');
            input = `<select name="campo_${slug}" ${req} class="w-full border-slate-200 rounded-lg p-2 text-sm">
                        <option value="">— Seleccionar —</option>${opts}</select>`;
        } else {
            input = `<input type="${campo.tipo || 'text'}" name="campo_${slug}"
                           value="${String(valor).replace(/"/g, '&quot;')}" ${req}
                           class="w-full border-slate-200 rounded-lg p-2 text-sm">`;
        }

        container.insertAdjacentHTML('beforeend', `
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">${campo.nombre} ${star}</label>
                ${input}
            </div>`);
    });
}

// ---- Modal Registrar ----
function abrirModalRegistrar(areaNombre = '', ubicacion = '') {
    document.getElementById('modalRegistrar').classList.remove('hidden');

    // Pre-seleccionar área si viene del botón de sección
    if (areaNombre) {
        const select = document.getElementById('selectorArea');
        for (let opt of select.options) {
            if (opt.text === areaNombre) {
                opt.selected = true;
                cargarCamposPlantilla(opt.value);
                break;
            }
        }
    }
    // Pre-rellenar ubicación si viene del botón "+" en una tarjeta
    if (ubicacion) {
        document.getElementById('input_ubicacion').value = ubicacion;
    }
}

function cerrarModalRegistrar() {
    document.getElementById('modalRegistrar').classList.add('hidden');
    document.getElementById('selectorArea').value = '';
    document.getElementById('input_ubicacion').value = '';
    document.getElementById('camposDinamicos').innerHTML = '';
    document.getElementById('hintArea').classList.remove('hidden');
}

function cargarCamposPlantilla(plantillaId) {
    const hint = document.getElementById('hintArea');
    if (!plantillaId) {
        document.getElementById('camposDinamicos').innerHTML = '';
        hint.classList.remove('hidden');
        return;
    }
    hint.classList.add('hidden');
    const plantilla = plantillasData[plantillaId];
    if (plantilla) renderCampos(plantilla.campos, null, 'camposDinamicos');
}

// ---- Modal Editar ----
function abrirModalEditar(item) {
    document.getElementById('modalEditar').classList.remove('hidden');
    document.getElementById('editSubtitle').textContent = `${item.area_nombre} · ${item.ubicacion} · ${item.nombre_item}`;
    document.getElementById('edit_ubicacion').value = item.ubicacion || '';
    document.getElementById('edit_nombre').value    = item.nombre_item || '';
    document.getElementById('edit_notas').value     = item.notas || '';
    document.getElementById('formEditar').action    = `/inventario/activos/${item.id}`;

    const plantilla = plantillasData[item.plantilla_id];
    if (plantilla) renderCampos(plantilla.campos, item.valores, 'editCampos');
}

function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
}

// ---- Filtro por área ----
function filtrarArea(slug) {
    document.querySelectorAll('.area-section').forEach(s => {
        s.style.display = (slug === 'all' || s.dataset.area === slug) ? '' : 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-[#041b32]','text-white');
        b.classList.add('bg-slate-100','text-slate-600');
    });
    const active = document.getElementById('tab-' + slug);
    if (active) {
        active.classList.add('bg-[#041b32]','text-white');
        active.classList.remove('bg-slate-100','text-slate-600');
    }
}
</script>

<style>
@media print {
    nav, .no-print { display: none !important; }
    body { background: white !important; }
    .bg-white { border: 1px solid #ddd !important; break-inside: avoid; margin-bottom: 12px; }
}
</style>
@endsection
