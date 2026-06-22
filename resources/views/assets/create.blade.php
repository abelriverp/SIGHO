@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-10 mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Registrar Nuevo Equipo</h2>
        <a href="{{ route('assets.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium">
            Volver 
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Información del Activo</h3>
        </div>
        
        <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6 bg-slate-50/30">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del Equipo</label>
                    <input type="text" name="nombre" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors" placeholder="Ej: Laptop Dell Latitude">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nomenclatura (ID)</label>
                    <input type="text" name="nomenclatura" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Encargado</label>
                    <input type="text" name="encargado" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Dirección IP</label>
                    <input type="text" name="ip_address" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors font-mono" placeholder="192.168.x.x">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Área</label>
                    {{-- Cambiado a input de texto libre --}}
                    <input type="text" name="area" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors" placeholder="Escribe el área">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Estatus Inicial</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors">
                        <option value="Activo">Activo</option>
                        <option value="En Reparación">En Reparación</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Notas / Observaciones</label>
                <textarea name="notas" rows="3" class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:bg-white focus:border-[#041b32] focus:ring-1 focus:ring-[#041b32] transition-colors"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-200">
                
                {{-- CAMPO DE IMAGEN --}}
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700 mb-3">Foto del Equipo (Opcional)</label>
                    <input type="file" name="imagen" accept="image/*" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
                </div>

                {{-- CAMPO DE PDF --}}
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                    <label class="block text-sm font-bold text-[#041b32] mb-3">Protocolo / PDF (Opcional)</label>
                    <input type="file" name="pdf_protocolo" accept=".pdf" class="block w-full text-sm text-slate-500 file:bg-[#041b32] file:text-white file:rounded-md file:border-0 file:px-4 file:py-2 hover:file:opacity-90 cursor-pointer transition">
                </div>
            </div>

            <div class="flex justify-end pt-6 gap-3 border-t border-slate-200 mt-6">
                <a href="{{ route('assets.index') }}" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition shadow-sm">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2.5 px-8 rounded-lg shadow-md hover:shadow-lg hover:opacity-95 transition">
                    Guardar Equipo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection