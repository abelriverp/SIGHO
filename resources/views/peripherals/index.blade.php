@extends('layouts.app')

@section('content')
<style>
    .table-container::-webkit-scrollbar { height: 10px; }
    .table-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .table-container::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
    .table-container::-webkit-scrollbar-thumb:hover { background: #64748b; }

    .text-vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        text-align: center;
        white-space: nowrap;
    }

    .excel-table th, .excel-table td {
        border: 1px solid #9ca3af;
        padding: 2px 5px;
        white-space: nowrap;
        font-size: 11px;
    }

    /* Columnas fijas al hacer scroll horizontal */
    .col-check {
        position: sticky;
        left: 0;
        z-index: 10;
        min-width: 30px;
        max-width: 30px;
        background: #f9fafb;
    }
    .col-division {
        position: sticky;
        left: 30px;
        z-index: 10;
        min-width: 36px;
        max-width: 36px;
    }
    .col-nombre {
        position: sticky;
        left: 66px;
        z-index: 10;
        min-width: 130px;
    }
    /* Sombra derecha para indicar que hay más contenido */
    .col-nombre {
        box-shadow: 3px 0 6px -2px rgba(0,0,0,0.15);
    }

    /* Columna de Acciones fija a la derecha: siempre visible, sin usar el slider horizontal */
    .col-acciones {
        position: sticky;
        right: 0;
        z-index: 10;
        background: #f9fafb;
        box-shadow: -3px 0 6px -2px rgba(0,0,0,0.15);
    }

    /* Barra flotante de movimiento en lote: fija al viewport, no al contenedor con scroll */
    #barraLote {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 50;
    }

    /* Fila resaltada al hacer clic */
    tr.fila-activa td {
        background-color: #fef08a !important;  /* amarillo suave */
        outline: 2px solid #eab308;
        outline-offset: -1px;
    }
    tr.fila-activa td.col-division {
        filter: brightness(0.9);
    }
</style>

<div class="m-4">
    <div class="flex justify-between items-end mb-4">
        <div>
            <h2 class="text-2xl font-bold text-[#041b32] uppercase tracking-widest">Inventario General</h2>
            <p class="text-gray-500 text-sm">Vista de hoja de cálculo maestra</p>
        </div>
       
        <div class="flex gap-3">
            <button onclick="document.getElementById('modalImport').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Importar Excel
            </button>
            <a href="{{ route('assets.export') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Exportar a Excel
            </a>
            <a href="{{ route('assets.create') }}" class="bg-[#041b32] hover:opacity-90 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                + Nuevo Registro
            </a>
        </div>
    </div>
</div> <div class="w-full overflow-x-auto table-container bg-white shadow-xl rounded-lg pb-4 border border-gray-400">
    <table class="excel-table w-max text-xs text-center text-gray-800 font-sans border-collapse">
        
        <thead>
            <tr class="text-xs uppercase tracking-wider font-bold">
                <th rowspan="2" class="bg-gray-200 col-check">
                    <input type="checkbox" id="chkTodos" onclick="toggleSeleccionarTodos(this)" title="Seleccionar todos">
                </th>
                <th rowspan="2" class="bg-gray-300 col-division">División</th>
                <th colspan="3" class="bg-green-300">Responsable</th>
                <th colspan="4" class="bg-blue-300">Nomenclatura / CPU / LAP</th>
                <th colspan="5" class="bg-green-300">Características</th>
                <th colspan="2" class="bg-blue-300">Software</th>
                <th colspan="3" class="bg-green-300">Monitor</th>
                <th colspan="3" class="bg-blue-300">Mouse</th>
                <th colspan="3" class="bg-green-300">Teclado</th>
                <th rowspan="2" class="bg-gray-200 col-acciones">Acciones</th>
            </tr>
            
            <tr class="bg-gray-100 text-[10px] uppercase font-bold text-gray-700">
                <th class="col-nombre bg-gray-100">Nombre PC</th>
                <th>Puesto</th>
                <th>Nombre Completo Asignación</th>
                
                <th>Tipo</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                
                <th>Procesador</th>
                <th>Ram (Gb)</th>
                <th>Tipo Ram</th>
                <th>Almacenamiento</th>
                <th>IP</th>
                
                <th>OS</th>
                <th>Office</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
                
                <th>Marca</th>
                <th>Modelo</th>
                <th>N/S</th>
            </tr>
        </thead>

        <tbody>
            {{-- LA MAGIA DE LOS COLORES EMPIEZA AQUÍ --}}
            @php
                $divColors = ['bg-red-200', 'bg-blue-200', 'bg-green-200', 'bg-purple-200', 'bg-orange-200', 'bg-teal-200', 'bg-pink-200', 'bg-indigo-200', 'bg-yellow-200', 'bg-lime-200'];
            @endphp

            @forelse($groupedAssets as $division => $assets)
                {{-- Escogemos un color basado en el índice actual del ciclo --}}
                @php
                    $currentColor = $divColors[$loop->index % count($divColors)];
                @endphp

                @foreach($assets as $index => $asset)
                    <tr class="transition-colors cursor-pointer" onclick="resaltarFila(this)">

                        <td class="col-check bg-white text-center">
                            <input type="checkbox" class="chk-equipo" data-id="{{ $asset->id }}"
                                onclick="event.stopPropagation(); toggleSeleccion(this)">
                        </td>

                        @if($index == 0)
                            <td rowspan="{{ $assets->count() }}" class="col-division {{ $currentColor }} font-black text-gray-800 align-middle shadow-inner">
                                <div class="text-vertical mx-auto">{{ strtoupper($division ?? 'SIN DIVISIÓN') }}</div>
                            </td>
                        @endif

                        <td class="col-nombre font-bold text-blue-900 bg-blue-50">
                            <a href="{{ route('assets.show', $asset->id) }}" class="hover:underline" onclick="event.stopPropagation()">{{ $asset->nomenclatura }}</a>
                        </td>
                        <td>{{ $asset->area }}</td>
                        <td class="font-bold">{{ $asset->encargado }}</td>

                        <td class="bg-blue-50/30">{{ $asset->tipo_equipo }}</td>
                        <td class="bg-blue-50/30">{{ $asset->marca }}</td>
                        <td class="bg-blue-50/30">{{ $asset->modelo }}</td>
                        <td class="bg-blue-50/30 font-mono text-[10px]">{{ $asset->numero_serie }}</td>

                        <td>{{ $asset->procesador }}</td>
                        <td class="font-bold">{{ $asset->ram }}</td>
                        <td>{{ $asset->tipo_ram }}</td>
                        <td>{{ $asset->almacenamiento }}</td>
                        <td class="font-mono text-[10px] text-blue-700 bg-blue-50/30">{{ $asset->ip_address }}</td>

                        <td class="bg-blue-50/30">{{ $asset->sistema_operativo }}</td>
                        <td class="bg-blue-50/30">{{ $asset->paqueteria_office }}</td>

                        <td>{{ $asset->monitor_marca }}</td>
                        <td>{{ $asset->monitor_modelo }}</td>
                        <td class="font-mono text-[10px]">{{ $asset->monitor_ns }}</td>

                        <td class="bg-blue-50/30">{{ $asset->mouse_marca }}</td>
                        <td class="bg-blue-50/30">{{ $asset->mouse_modelo }}</td>
                        <td class="bg-blue-50/30 font-mono text-[10px]">{{ $asset->mouse_ns }}</td>

                        <td>{{ $asset->teclado_marca }}</td>
                        <td>{{ $asset->teclado_modelo }}</td>
                        <td class="font-mono text-[10px]">{{ $asset->teclado_ns }}</td>

                        <td class="bg-gray-50 col-acciones">
                            <div class="flex flex-col items-center gap-1 py-1">
                                {{-- Editar --}}
                                <a href="{{ route('assets.edit', $asset->id) }}" title="Editar" class="text-blue-600 hover:text-blue-800 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                {{-- Cambiar División --}}
                                <button title="Cambiar División"
                                    onclick="abrirModalDivision({{ $asset->id }}, '{{ addslashes($asset->nomenclatura) }}', '{{ addslashes($asset->division ?? '') }}')"
                                    class="text-amber-500 hover:text-amber-700 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                </button>
                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('assets.destroy', $asset->id) }}"
                                    onsubmit="return confirm('¿Eliminar {{ addslashes($asset->nomenclatura) }}? Esta acción no se puede deshacer.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Eliminar" class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="25" class="py-10 text-gray-500 text-base font-bold">No hay equipos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ===== BARRA FLOTANTE: MOVER EQUIPOS EN LOTE ===== --}}
<div id="barraLote" class="hidden bg-[#041b32] text-white shadow-2xl border-t-4 border-amber-400">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-3">
        <span class="font-bold text-sm whitespace-nowrap">
            <span id="contadorLote">0</span> equipo(s) seleccionado(s)
        </span>

        <div class="flex items-center gap-2">
            <label class="text-xs text-slate-300 whitespace-nowrap">Nueva División:</label>
            <select id="selectDivisionLote" class="text-sm rounded-md px-2 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option value="">— no cambiar —</option>
                @foreach($divisiones as $div)
                    <option value="{{ $div }}">{{ $div }}</option>
                @endforeach
                <option value="__nueva__">— Escribir nueva división —</option>
            </select>
            <input type="text" id="inputNuevaDivisionLote" placeholder="Nombre de la nueva división"
                class="hidden text-sm rounded-md px-2 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs text-slate-300 whitespace-nowrap">Nuevo Puesto/Área:</label>
            <input type="text" id="inputAreaLote" list="listaAreas" placeholder="Ej: Recepción Nocturno"
                class="text-sm rounded-md px-2 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400">
            <datalist id="listaAreas">
                @foreach($areas as $a)
                    <option value="{{ $a }}"></option>
                @endforeach
            </datalist>
        </div>

        <button onclick="moverLote()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm py-1.5 px-4 rounded-lg shadow transition">
            Mover
        </button>
        <button onclick="cancelarSeleccion()" class="text-slate-300 hover:text-white text-sm font-medium underline">
            Cancelar selección
        </button>

        <p id="msgLoteError" class="hidden text-red-300 text-xs w-full"></p>
    </div>
</div>

{{-- ===== MODAL CAMBIAR DIVISIÓN ===== --}}
<div id="modalDivision" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="bg-amber-500 text-white px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">Cambiar División</h3>
            <button onclick="document.getElementById('modalDivision').classList.add('hidden')" class="text-white hover:text-amber-100 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Equipo: <strong id="divNomenclatura" class="text-[#041b32]"></strong></p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">División actual</label>
                <p id="divActual" class="text-sm text-gray-500 italic"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva División</label>
                <select id="selectDivision" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mb-2">
                    @foreach($divisiones as $div)
                        <option value="{{ $div }}">{{ $div }}</option>
                    @endforeach
                    <option value="__nueva__">— Escribir nueva división —</option>
                </select>
                <input type="text" id="inputNuevaDivision" placeholder="Nombre de la nueva división"
                    class="hidden w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <p id="msgDivError" class="text-red-600 text-sm hidden"></p>
        </div>
        <div class="px-6 pb-6 flex justify-end gap-3">
            <button onclick="document.getElementById('modalDivision').classList.add('hidden')"
                class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Cancelar</button>
            <button onclick="guardarDivision()"
                class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold">Guardar</button>
        </div>
    </div>
</div>

{{-- ===== MODAL IMPORTAR EXCEL ===== --}}
<div id="modalImport" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
        <div class="bg-[#041b32] text-white px-6 py-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">Importar inventario desde Excel</h3>
            <button onclick="cerrarModal()" class="text-white hover:text-gray-300 text-2xl leading-none">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            {{-- Paso 1: seleccionar archivo --}}
            <div id="paso1">
                <p class="text-sm text-gray-600 mb-3">
                    Selecciona el archivo <strong>.xlsx</strong> o <strong>.xls</strong> con el inventario.<br>
                    La primera fila debe contener los encabezados. Las columnas reconocidas son:<br>
                    <span class="text-xs text-gray-400">División, Nombre PC, Puesto, Nombre Completo Asignacion, Tipo, Marca, Modelo, Serie, Procesador, Ram (Gb), Tipo Ram, Almacenamiento, IP, OS, Office, Monitor Marca, Monitor Modelo, Monitor N/S, Mouse Marca, Mouse Modelo, Mouse N/S, Teclado Marca, Teclado Modelo, Teclado N/S</span>
                </p>
                <input type="file" id="archivoExcel" accept=".xlsx,.xls"
                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p id="msgError" class="text-red-600 text-sm mt-2 hidden"></p>
            </div>

            {{-- Paso 2: vista previa --}}
            <div id="paso2" class="hidden">
                <p id="resumenPreview" class="text-sm text-gray-700 mb-2"></p>
                <div class="overflow-x-auto max-h-64 border border-gray-200 rounded-lg">
                    <table id="tablaPreview" class="text-xs w-max border-collapse">
                        <thead id="theadPreview" class="bg-gray-100 sticky top-0"></thead>
                        <tbody id="tbodyPreview"></tbody>
                    </table>
                </div>
                <p class="text-xs text-amber-600 mt-2">⚠ Los registros existentes se actualizarán por <strong>Nombre PC</strong>; los nuevos se crearán.</p>
            </div>

            {{-- Barra de progreso --}}
            <div id="progreso" class="hidden">
                <p class="text-sm text-gray-600 mb-1">Importando...</p>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div id="barraProgreso" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
                <p id="textoProgreso" class="text-xs text-gray-500 mt-1">0 / 0</p>
            </div>

            {{-- Resultado final --}}
            <div id="resultado" class="hidden">
                <p id="textoResultado" class="text-sm font-semibold"></p>
            </div>
        </div>

        <div class="px-6 pb-6 flex justify-end gap-3">
            <button onclick="cerrarModal()" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Cancelar</button>
            <button id="btnPreview" onclick="previewExcel()" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold">Vista previa</button>
            <button id="btnImportar" onclick="importarDatos()" class="hidden px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-bold">Importar</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
let datosImportar = [];

// Mapeo FIJO por índice de columna (coincide con el formato del inventario HEMZT)
// Col: 0=Depto, 1=Id, 2=skip, 3=Nombre PC, 4=Puesto, 5=Nombre Completo, 6=Tipo,
//      7=Marca, 8=Modelo, 9=Serie, 10=Fecha, 11=Factura,
//      12=Procesador, 13=Ram, 14=TipoRam, 15=Almacenamiento, 16=IP, 17=OS, 18=Office,
//      19=Mon.Marca, 20=Mon.Modelo, 21=Mon.NS,
//      22=Mouse.Marca, 23=Mouse.Modelo, 24=Mouse.NS,
//      25=Tec.Marca, 26=Tec.Modelo, 27=Tec.NS
const COL_MAP = {
    division:          0,
    nomenclatura:      3,
    area:              4,
    encargado:         5,
    tipo_equipo:       6,
    marca:             7,
    modelo:            8,
    numero_serie:      9,
    procesador:        12,
    ram:               13,
    tipo_ram:          14,
    almacenamiento:    15,
    ip_address:        16,
    sistema_operativo: 17,
    paqueteria_office: 18,
    monitor_marca:     19,
    monitor_modelo:    20,
    monitor_ns:        21,
    mouse_marca:       22,
    mouse_modelo:      23,
    mouse_ns:          24,
    teclado_marca:     25,
    teclado_modelo:    26,
    teclado_ns:        27,
};

// Encuentra la fila donde están los encabezados reales (busca "nombre pc")
function encontrarFilaEncabezado(raw) {
    for (let i = 0; i < Math.min(raw.length, 6); i++) {
        const fila = raw[i].map(c => String(c ?? '').trim().toLowerCase());
        if (fila.includes('nombre pc') || fila.includes('nomenclatura')) return i;
    }
    return -1;
}

function parsearHoja(ws) {
    const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
    if (raw.length < 2) return [];

    const filaEncabezado = encontrarFilaEncabezado(raw);
    const inicioData = filaEncabezado >= 0 ? filaEncabezado + 1 : 1;

    const datos = [];
    let ultimaDivision = '';

    for (let i = inicioData; i < raw.length; i++) {
        const fila = raw[i];
        // Saltar filas completamente vacías
        if (fila.every(c => c === '' || c === null || c === undefined)) continue;

        // Llevar la División hacia adelante (celdas combinadas)
        const divVal = String(fila[COL_MAP.division] ?? '').trim();
        if (divVal) ultimaDivision = divVal;

        const nomenclatura = String(fila[COL_MAP.nomenclatura] ?? '').trim();
        if (!nomenclatura) continue; // Necesita Nombre PC

        const obj = { division: ultimaDivision };
        for (const [campo, col] of Object.entries(COL_MAP)) {
            if (campo === 'division') continue;
            obj[campo] = String(fila[col] ?? '').trim();
        }
        datos.push(obj);
    }
    return datos;
}

function cerrarModal() {
    document.getElementById('modalImport').classList.add('hidden');
    resetModal();
}

function resetModal() {
    document.getElementById('archivoExcel').value = '';
    document.getElementById('paso1').classList.remove('hidden');
    document.getElementById('paso2').classList.add('hidden');
    document.getElementById('progreso').classList.add('hidden');
    document.getElementById('resultado').classList.add('hidden');
    document.getElementById('btnPreview').classList.remove('hidden');
    document.getElementById('btnImportar').classList.add('hidden');
    document.getElementById('msgError').classList.add('hidden');
    datosImportar = [];
}

function previewExcel() {
    const file = document.getElementById('archivoExcel').files[0];
    const errEl = document.getElementById('msgError');
    errEl.classList.add('hidden');

    if (!file) { mostrarError('Selecciona un archivo primero.'); return; }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const wb = XLSX.read(e.target.result, { type: 'array' });

            datosImportar = [];
            // Procesar todas las hojas
            for (const sheetName of wb.SheetNames) {
                const ws = wb.Sheets[sheetName];
                const filas = parsearHoja(ws);
                datosImportar.push(...filas);
            }

            if (datosImportar.length === 0) {
                mostrarError('No se encontraron registros válidos. Verifica que el archivo tenga la columna "Nombre PC".');
                return;
            }

            // Vista previa (primeras 5 filas)
            const visibleCols = ['division','nomenclatura','area','encargado','tipo_equipo','marca','modelo','ip_address'];
            const thead = document.getElementById('theadPreview');
            const tbody = document.getElementById('tbodyPreview');
            thead.innerHTML = '<tr>' + visibleCols.map(c => `<th class="border border-gray-300 px-2 py-1 bg-gray-100 whitespace-nowrap">${c}</th>`).join('') + '</tr>';
            tbody.innerHTML = datosImportar.slice(0, 5).map(row =>
                '<tr>' + visibleCols.map(c => `<td class="border border-gray-200 px-2 py-1 whitespace-nowrap">${row[c] ?? ''}</td>`).join('') + '</tr>'
            ).join('');

            document.getElementById('resumenPreview').textContent = `Se encontraron ${datosImportar.length} registros en ${wb.SheetNames.length} hoja(s). Mostrando los primeros 5:`;
            document.getElementById('paso2').classList.remove('hidden');
            document.getElementById('btnPreview').classList.add('hidden');
            document.getElementById('btnImportar').classList.remove('hidden');

        } catch(err) {
            mostrarError('Error al leer el archivo: ' + err.message);
        }
    };
    reader.readAsArrayBuffer(file);
}

async function importarDatos() {
    document.getElementById('paso2').classList.add('hidden');
    document.getElementById('btnImportar').classList.add('hidden');
    document.getElementById('progreso').classList.remove('hidden');

    const barra = document.getElementById('barraProgreso');
    const texto = document.getElementById('textoProgreso');
    const total = datosImportar.length;
    const LOTE = 50;
    let importados = 0, errores = 0;

    for (let i = 0; i < total; i += LOTE) {
        const lote = datosImportar.slice(i, i + LOTE);
        try {
            const resp = await fetch('{{ route("assets.import") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ rows: lote })
            });
            const json = await resp.json();
            importados += json.importados ?? lote.length;
            errores += json.errores ?? 0;
        } catch(e) {
            errores += lote.length;
        }

        const pct = Math.round(((i + lote.length) / total) * 100);
        barra.style.width = pct + '%';
        texto.textContent = `${Math.min(i + LOTE, total)} / ${total}`;
    }

    document.getElementById('progreso').classList.add('hidden');
    document.getElementById('resultado').classList.remove('hidden');
    const res = document.getElementById('textoResultado');
    res.textContent = `✅ Importación completa: ${importados} registros procesados${errores > 0 ? `, ${errores} con error` : ''}.`;
    res.className = 'text-sm font-semibold ' + (errores > 0 ? 'text-amber-600' : 'text-green-600');
    document.getElementById('btnPreview').textContent = 'Cerrar';
    document.getElementById('btnPreview').classList.remove('hidden');
    document.getElementById('btnPreview').onclick = () => { cerrarModal(); location.reload(); };
}

function mostrarError(msg) {
    const el = document.getElementById('msgError');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ===== RESALTAR FILA =====
function resaltarFila(tr) {
    // Si ya está activa, quitamos el resaltado (toggle)
    if (tr.classList.contains('fila-activa')) {
        tr.classList.remove('fila-activa');
        return;
    }
    // Quitar resaltado de cualquier otra fila
    document.querySelectorAll('tr.fila-activa').forEach(r => r.classList.remove('fila-activa'));
    tr.classList.add('fila-activa');
}

// ===== CAMBIAR DIVISIÓN =====
let _divAssetId = null;

function abrirModalDivision(id, nomenclatura, divisionActual) {
    _divAssetId = id;
    document.getElementById('divNomenclatura').textContent = nomenclatura;
    document.getElementById('divActual').textContent = divisionActual || '(sin división)';
    document.getElementById('msgDivError').classList.add('hidden');
    document.getElementById('inputNuevaDivision').classList.add('hidden');
    document.getElementById('inputNuevaDivision').value = '';

    // Pre-seleccionar la división actual si existe en la lista
    const sel = document.getElementById('selectDivision');
    const opts = Array.from(sel.options).map(o => o.value);
    sel.value = opts.includes(divisionActual) ? divisionActual : sel.options[0].value;

    document.getElementById('modalDivision').classList.remove('hidden');
}

document.getElementById('selectDivision').addEventListener('change', function () {
    const input = document.getElementById('inputNuevaDivision');
    if (this.value === '__nueva__') {
        input.classList.remove('hidden');
        input.focus();
    } else {
        input.classList.add('hidden');
    }
});

async function guardarDivision() {
    const sel   = document.getElementById('selectDivision');
    const input = document.getElementById('inputNuevaDivision');
    const errEl = document.getElementById('msgDivError');
    errEl.classList.add('hidden');

    let nuevaDivision = sel.value === '__nueva__' ? input.value.trim() : sel.value;

    if (sel.value === '__nueva__' && !nuevaDivision) {
        errEl.textContent = 'Escribe el nombre de la nueva división.';
        errEl.classList.remove('hidden');
        return;
    }

    try {
        const resp = await fetch(`/assets/${_divAssetId}/division`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ division: nuevaDivision })
        });

        if (!resp.ok) throw new Error('Error del servidor');

        document.getElementById('modalDivision').classList.add('hidden');
        location.reload();
    } catch(e) {
        errEl.textContent = 'No se pudo guardar. Intenta de nuevo.';
        errEl.classList.remove('hidden');
    }
}

// ===== SELECCIÓN MÚLTIPLE Y MOVIMIENTO EN LOTE =====
const seleccionados = new Set();

function toggleSeleccion(checkbox) {
    const id = parseInt(checkbox.dataset.id, 10);
    if (checkbox.checked) {
        seleccionados.add(id);
    } else {
        seleccionados.delete(id);
        document.getElementById('chkTodos').checked = false;
    }
    actualizarBarraLote();
}

function toggleSeleccionarTodos(checkboxTodos) {
    document.querySelectorAll('.chk-equipo').forEach(cb => {
        cb.checked = checkboxTodos.checked;
        const id = parseInt(cb.dataset.id, 10);
        if (checkboxTodos.checked) seleccionados.add(id);
        else seleccionados.delete(id);
    });
    actualizarBarraLote();
}

function actualizarBarraLote() {
    const barra = document.getElementById('barraLote');
    const hayActivos = seleccionados.size > 0;
    document.getElementById('contadorLote').textContent = seleccionados.size;
    barra.classList.toggle('hidden', !hayActivos);
    // Evita que la barra fija tape la última fila de la tabla mientras está visible
    document.body.style.paddingBottom = hayActivos ? '70px' : '';
}

function cancelarSeleccion() {
    seleccionados.clear();
    document.querySelectorAll('.chk-equipo').forEach(cb => cb.checked = false);
    const chkTodos = document.getElementById('chkTodos');
    if (chkTodos) chkTodos.checked = false;
    document.getElementById('msgLoteError').classList.add('hidden');
    actualizarBarraLote();
}

document.getElementById('selectDivisionLote').addEventListener('change', function () {
    const input = document.getElementById('inputNuevaDivisionLote');
    if (this.value === '__nueva__') {
        input.classList.remove('hidden');
        input.focus();
    } else {
        input.classList.add('hidden');
    }
});

async function moverLote() {
    const msgEl = document.getElementById('msgLoteError');
    msgEl.classList.add('hidden');

    if (seleccionados.size === 0) return;

    const selDiv = document.getElementById('selectDivisionLote');
    const inputNuevaDiv = document.getElementById('inputNuevaDivisionLote');
    const inputArea = document.getElementById('inputAreaLote');

    let division = '';
    if (selDiv.value === '__nueva__') {
        division = inputNuevaDiv.value.trim();
    } else {
        division = selDiv.value;
    }
    const area = inputArea.value.trim();

    if (!division && !area) {
        msgEl.textContent = 'Indica una nueva División y/o un nuevo Puesto/Área.';
        msgEl.classList.remove('hidden');
        return;
    }

    const payload = { ids: Array.from(seleccionados) };
    if (division) payload.division = division;
    if (area) payload.area = area;

    try {
        const resp = await fetch('{{ route("assets.moverLote") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!resp.ok || !json.ok) throw new Error(json.message || 'Error del servidor');
        location.reload();
    } catch (e) {
        msgEl.textContent = e.message || 'No se pudo mover. Intenta de nuevo.';
        msgEl.classList.remove('hidden');
    }
}
</script>
@endsection