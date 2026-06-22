@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-10 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Editar Equipo</h2>
        <a href="{{ route('assets.perifericos') }}" class="text-gray-500 hover:text-[#041b32] font-medium">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="bg-[#041b32] px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-semibold text-lg">Modificar: {{ $asset->nomenclatura }}</h3>
            <span class="bg-blue-800 text-blue-100 text-xs py-1 px-2 rounded font-bold shadow-sm">Act.: {{ $asset->updated_at?->format('d/m/Y') ?? 'Recién importado' }}</span>
        </div>

        <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6 bg-slate-50/30">
            @csrf
            @method('PUT')
            
            <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2">Datos Principales</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nombre</label>
                    <input type="text" name="nombre" value="{{ $asset->nombre }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nomenclatura</label>
                    <input type="text" name="nomenclatura" value="{{ $asset->nomenclatura }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Encargado</label>
                    <input type="text" name="encargado" value="{{ $asset->encargado }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">IP</label>
                    <input type="text" name="ip_address" value="{{ $asset->ip_address }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Área</label>
                    <input type="text" name="area" value="{{ $asset->area }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors" placeholder="Escribe el área">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Estado Actual</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                        <option value="Activo" {{ $asset->status == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="En Reparación" {{ $asset->status == 'En Reparación' ? 'selected' : '' }}>En Reparación</option>
                        <option value="Baja" {{ $asset->status == 'Baja' ? 'selected' : '' }}>Baja</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">División</label>
                    <input type="text" name="division" value="{{ $asset->division }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors" placeholder="Ej: Contraloría">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Tipo de Equipo</label>
                    <select name="tipo_equipo" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                        <option value="CPU" {{ $asset->tipo_equipo == 'CPU' ? 'selected' : '' }}>CPU (Escritorio)</option>
                        <option value="LAP" {{ $asset->tipo_equipo == 'LAP' ? 'selected' : '' }}>Laptop</option>
                        <option value="AIO" {{ $asset->tipo_equipo == 'AIO' ? 'selected' : '' }}>All-In-One</option>
                        <option value="POS" {{ $asset->tipo_equipo == 'POS' ? 'selected' : '' }}>Punto de Venta</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Marca</label>
                    <input type="text" name="marca" value="{{ $asset->marca }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Modelo</label>
                    <input type="text" name="modelo" value="{{ $asset->modelo }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Número de Serie</label>
                    <input type="text" name="numero_serie" value="{{ $asset->numero_serie }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors font-mono">
                </div>
            </div>

            <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Hardware y Software</h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Procesador</label>
                    <input type="text" name="procesador" value="{{ $asset->procesador }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">RAM (GB)</label>
                    <input type="number" name="ram" value="{{ $asset->ram }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Tipo RAM</label>
                    <input type="text" name="tipo_ram" value="{{ $asset->tipo_ram }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Almacenamiento</label>
                    <input type="text" name="almacenamiento" value="{{ $asset->almacenamiento }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Sistema Operativo</label>
                    <input type="text" name="sistema_operativo" value="{{ $asset->sistema_operativo }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Office</label>
                    <input type="text" name="paqueteria_office" value="{{ $asset->paqueteria_office }}" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
            </div>

            <h4 class="text-lg font-bold text-[#041b32] border-b-2 border-slate-200 pb-2 mt-8">Periféricos</h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-slate-200/50 p-4 rounded-lg border border-slate-200 shadow-inner">
                <div class="font-bold text-slate-700">🖥️ Monitor</div>
                <input type="text" name="monitor_marca" value="{{ $asset->monitor_marca }}" placeholder="Marca" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="monitor_modelo" value="{{ $asset->monitor_modelo }}" placeholder="Modelo" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="monitor_ns" value="{{ $asset->monitor_ns }}" placeholder="N/S" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 font-mono focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-slate-200/50 p-4 rounded-lg border border-slate-200 shadow-inner">
                <div class="font-bold text-slate-700">🖱️ Mouse</div>
                <input type="text" name="mouse_marca" value="{{ $asset->mouse_marca }}" placeholder="Marca" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="mouse_modelo" value="{{ $asset->mouse_modelo }}" placeholder="Modelo" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="mouse_ns" value="{{ $asset->mouse_ns }}" placeholder="N/S" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 font-mono focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center bg-slate-200/50 p-4 rounded-lg border border-slate-200 shadow-inner">
                <div class="font-bold text-slate-700">⌨️ Teclado</div>
                <input type="text" name="teclado_marca" value="{{ $asset->teclado_marca }}" placeholder="Marca" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="teclado_modelo" value="{{ $asset->teclado_modelo }}" placeholder="Modelo" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
                <input type="text" name="teclado_ns" value="{{ $asset->teclado_ns }}" placeholder="N/S" class="w-full bg-white border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 font-mono focus:outline-none focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32]">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1 mt-4">Notas</label>
                <textarea name="notas" rows="3" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">{{ $asset->notas }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-200">
                
                {{-- CAMPO FOTO --}}
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-3">Foto del Equipo</label>
                    @if($asset->imagen_path)
                        <div class="flex items-center gap-3 mb-4 p-3 bg-slate-50 rounded-md border border-slate-200">
                            <img src="{{ Storage::url($asset->imagen_path) }}" class="w-16 h-16 object-cover rounded shadow-sm">
                            <span class="text-xs text-slate-600 font-medium">Imagen Actual</span>
                        </div>
                    @endif
                    <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
                </div>

                {{-- CAMPO PDF --}}
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-[#041b32] mb-3">Protocolo (PDF)</label>
                    @if($asset->pdf_path)
                        <div class="bg-blue-50/50 p-4 rounded-md mb-4 border border-blue-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-[#041b32] font-bold flex items-center gap-2">📄 Documento cargado</span>
                                <a href="{{ Storage::url($asset->pdf_path) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline font-medium">Ver Archivo</a>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-red-50 p-2 rounded-md transition border border-transparent hover:border-red-100">
                                <input type="checkbox" name="delete_pdf" value="1" class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                                <span class="text-sm font-bold text-red-600">Eliminar documento actual</span>
                            </label>
                        </div>
                    @endif
                    <input type="file" name="pdf_protocolo" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
                </div>
            </div>

            <div class="flex justify-end pt-6 gap-3 border-t border-slate-200 mt-6">
                <a href="{{ route('assets.index') }}" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition shadow-sm">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md hover:shadow-lg hover:opacity-95 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection