{{--
    Formulario compartido de alta/edición de activos (Fase 1, nov 2026).
    Recibe: $asset, $ubicaciones, $usuarios, $padres, $categorias, $estados, $tipos.
--}}
@php
    $in = 'w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors';
    $lbl = 'block text-sm font-bold text-slate-700 mb-1';
    $v = fn ($campo) => old($campo, $asset->{$campo});
    $fechaV = fn ($campo) => old($campo, $asset->{$campo}?->format('Y-m-d'));
@endphp

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4 text-sm">
        <p class="font-bold mb-1">Revisa estos campos:</p>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div x-data="{ categoria: @js($v('categoria') ?? 'Cómputo'), estado: @js($v('status') ?? 'Activo'), tipos: @js($tipos) }" class="space-y-6">

    <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2">Identificación</h4>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="{{ $lbl }}">Nomenclatura (ID) *</label>
            <input type="text" name="nomenclatura" value="{{ $v('nomenclatura') }}" required class="{{ $in }} font-mono" placeholder="Ej: CUN-PC-001">
        </div>
        <div class="md:col-span-2">
            <label class="{{ $lbl }}">Nombre descriptivo</label>
            <input type="text" name="nombre" value="{{ $v('nombre') }}" class="{{ $in }}" placeholder="Ej: PC Recepción 1">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="{{ $lbl }}">Categoría *</label>
            <select name="categoria" x-model="categoria" class="{{ $in }}">
                @foreach ($categorias as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $lbl }}">Tipo</label>
            <input type="text" name="tipo_equipo" value="{{ $v('tipo_equipo') }}" list="tipos-sugeridos" class="{{ $in }}" placeholder="Ej: Access Point">
            <datalist id="tipos-sugeridos">
                <template x-for="t in (tipos[categoria] || [])" :key="t"><option :value="t"></option></template>
            </datalist>
        </div>
        <div>
            <label class="{{ $lbl }}">Estado *</label>
            <select name="status" x-model="estado" class="{{ $in }}">
                @foreach ($estados as $e)
                    <option value="{{ $e }}">{{ $e }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="{{ $lbl }}">Marca</label>
            <input type="text" name="marca" value="{{ $v('marca') }}" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Modelo</label>
            <input type="text" name="modelo" value="{{ $v('modelo') }}" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Número de serie</label>
            <input type="text" name="numero_serie" value="{{ $v('numero_serie') }}" class="{{ $in }} font-mono">
        </div>
    </div>

    <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Ubicación y resguardo</h4>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="{{ $lbl }}">Ubicación</label>
            <select name="ubicacion_id" class="{{ $in }}">
                <option value="">— Sin ubicación —</option>
                @foreach ($ubicaciones->groupBy('tipo') as $tipo => $grupo)
                    <optgroup label="{{ \App\Models\Ubicacion::TIPOS[$tipo] ?? $tipo }}">
                        @foreach ($grupo as $u)
                            <option value="{{ $u->id }}" @selected((string) $v('ubicacion_id') === (string) $u->id)>{{ $u->etiqueta }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">¿No aparece? <a href="{{ route('ubicaciones.create') }}" target="_blank" class="underline">Crear ubicación</a></p>
        </div>
        <div>
            <label class="{{ $lbl }}">Área / puesto (texto libre)</label>
            <input type="text" name="area" value="{{ $v('area') }}" class="{{ $in }}" placeholder="Ej: Recepción - Caja 2">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="{{ $lbl }}">Resguardo (usuario de SIGHO)</label>
            <select name="asignado_a" class="{{ $in }}">
                <option value="">— Nadie —</option>
                @foreach ($usuarios as $u)
                    <option value="{{ $u->id }}" @selected((string) $v('asignado_a') === (string) $u->id)>{{ $u->name }}{{ $u->departamento ? ' · '.$u->departamento : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $lbl }}">Encargado (si no tiene usuario)</label>
            <input type="text" name="encargado" value="{{ $v('encargado') }}" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">División</label>
            <input type="text" name="division" value="{{ $v('division') }}" class="{{ $in }}" placeholder="Ej: Front Office">
        </div>
    </div>

    <div>
        <label class="{{ $lbl }}">Ligado a otro activo <span class="font-normal text-slate-500">(un monitor a su CPU, un control a su TV)</span></label>
        <select name="parent_id" class="{{ $in }}">
            <option value="">— Independiente —</option>
            @foreach ($padres as $p)
                <option value="{{ $p->id }}" @selected((string) $v('parent_id') === (string) $p->id)>{{ $p->nomenclatura }}{{ $p->nombre ? ' · '.$p->nombre : '' }} ({{ $p->categoria }})</option>
            @endforeach
        </select>
    </div>

    <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Red</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="{{ $lbl }}">Dirección IP</label>
            <input type="text" name="ip_address" value="{{ $v('ip_address') }}" class="{{ $in }} font-mono" placeholder="10.7.64.50">
        </div>
        <div>
            <label class="{{ $lbl }}">MAC</label>
            <input type="text" name="mac_address" value="{{ $v('mac_address') }}" class="{{ $in }} font-mono" placeholder="AA:BB:CC:DD:EE:FF">
        </div>
    </div>

    <template x-if="categoria === 'Cómputo' || categoria === 'Punto de venta'">
        <div class="space-y-6">
            <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Hardware y software</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="{{ $lbl }}">Procesador</label>
                    <input type="text" name="procesador" value="{{ $v('procesador') }}" class="{{ $in }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">RAM (GB)</label>
                    <input type="text" name="ram" value="{{ $v('ram') }}" class="{{ $in }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Tipo RAM</label>
                    <input type="text" name="tipo_ram" value="{{ $v('tipo_ram') }}" class="{{ $in }}">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="{{ $lbl }}">Almacenamiento</label>
                    <input type="text" name="almacenamiento" value="{{ $v('almacenamiento') }}" class="{{ $in }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Sistema operativo</label>
                    <input type="text" name="sistema_operativo" value="{{ $v('sistema_operativo') }}" class="{{ $in }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Office</label>
                    <input type="text" name="paqueteria_office" value="{{ $v('paqueteria_office') }}" class="{{ $in }}">
                </div>
            </div>
        </div>
    </template>

    <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Compra y garantía</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="{{ $lbl }}">Proveedor</label>
            <select name="proveedor_id" class="{{ $in }}">
                <option value="">— Elegir del catálogo —</option>
                @foreach ($proveedores as $p)
                    <option value="{{ $p->id }}" @selected((string) $v('proveedor_id') === (string) $p->id)>{{ $p->nombre }}</option>
                @endforeach
            </select>
            <input type="text" name="proveedor" value="{{ $v('proveedor') }}" class="{{ $in }} mt-2" placeholder="…o escríbelo si no está en el catálogo">
        </div>
        <div>
            <label class="{{ $lbl }}">Factura</label>
            <input type="text" name="factura" value="{{ $v('factura') }}" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Costo (MXN)</label>
            <input type="number" step="0.01" min="0" name="costo" value="{{ $v('costo') }}" class="{{ $in }}">
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="{{ $lbl }}">Fecha de compra</label>
            <input type="date" name="fecha_adquisicion" value="{{ $fechaV('fecha_adquisicion') }}" class="{{ $in }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Garantía vigente hasta</label>
            <input type="date" name="garantia_hasta" value="{{ $fechaV('garantia_hasta') }}" class="{{ $in }}">
        </div>
    </div>

    <template x-if="estado === 'Baja'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div>
                <label class="{{ $lbl }}">Fecha de baja</label>
                <input type="date" name="fecha_baja" value="{{ $fechaV('fecha_baja') ?? now()->format('Y-m-d') }}" class="{{ $in }}">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $lbl }}">Motivo de baja</label>
                <textarea name="motivo_baja" rows="2" class="{{ $in }}" placeholder="Ej: daño en tarjeta madre, dictamen del proveedor">{{ $v('motivo_baja') }}</textarea>
            </div>
        </div>
    </template>

    <div>
        <label class="{{ $lbl }} mt-4">Notas</label>
        <textarea name="notas" rows="3" class="{{ $in }}">{{ $v('notas') }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-200">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
            <label class="block text-sm font-bold text-slate-700 mb-3">Foto del activo</label>
            @if ($asset->imagen_path)
                <div class="flex items-center gap-3 mb-4 p-3 bg-slate-50 rounded-md border border-slate-200">
                    <img src="{{ Storage::url($asset->imagen_path) }}" class="w-16 h-16 object-cover rounded shadow-sm" alt="">
                    <span class="text-xs text-slate-600 font-medium">Imagen actual</span>
                </div>
            @endif
            <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
        </div>
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
            <label class="block text-sm font-bold text-[#041b32] mb-3">Documento (PDF: factura, protocolo, dictamen)</label>
            @if ($asset->pdf_path)
                <div class="bg-blue-50/50 p-4 rounded-md mb-4 border border-blue-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-[#041b32] font-bold">Documento cargado</span>
                        <a href="{{ Storage::url($asset->pdf_path) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline font-medium">Ver archivo</a>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="delete_pdf" value="1" class="rounded text-red-600 w-4 h-4">
                        <span class="text-sm font-bold text-red-600">Eliminar documento actual</span>
                    </label>
                </div>
            @endif
            <input type="file" name="pdf_protocolo" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
        </div>
    </div>
</div>
