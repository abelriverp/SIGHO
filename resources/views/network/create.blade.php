@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Registrar Equipo de Red</h2>
        <a href="{{ route('network.index') }}" class="text-gray-500 hover:text-[#041b32] font-medium">
            
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Información del Dispositivo</h3>
        </div>
        
        <form action="{{ route('network.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre / Identificador</label>
                    <input type="text" name="nombre" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" placeholder="Ej: Switch Rack Principal">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Equipo</label>
                    <select name="tipo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="Switch">Switch</option>
                        <option value="Patch Panel">Patch Panel</option>
                        <option value="Router">Router / Firewall</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad de Puertos</label>
                    <select name="total_ports" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200">
                        <option value="8">8 Puertos</option>
                        <option value="16">16 Puertos</option>
                        <option value="24" selected>24 Puertos</option>
                        <option value="48">48 Puertos</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Los puertos se generarán automáticamente.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Ubicación Física</label>
                    <input type="text" name="ubicacion" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-900 focus:ring focus:ring-blue-200" placeholder="Ej: Site Sistemas, Recepción...">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90 transition">
                    Crear y Generar Puertos
                </button>
            </div>
        </form>
    </div>
</div>
@endsection