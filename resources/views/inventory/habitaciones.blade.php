@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto mt-8 px-4">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-[#041b32]">Inventario por Habitación</h2>
            <p class="text-slate-500 font-medium">Gestión técnica de Access Points, TV, Controles y Cajas de Seguridad.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="abrirModalCrear()" class="bg-[#041b32] text-white px-6 py-2 rounded-lg font-bold shadow hover:opacity-90 transition">
                + Registrar Habitación
            </button>
            <button onclick="window.print()" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg font-bold border border-slate-300 hover:bg-slate-200 transition">
                Imprimir Reporte
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-bounce">
            {{ session('success') }}
        </div>
    @endif

    @php 
        $pisosData = [1 => $piso1, 2 => $piso2, 3 => $piso3, 4 => $piso4]; 
    @endphp

    @foreach($pisosData as $numPiso => $habitaciones)
    <div class="mb-12">
        <h3 class="text-xl font-black text-slate-700 mb-6 flex items-center gap-3 border-b-2 border-slate-200 pb-2">
            <span class="bg-[#041b32] text-white px-3 py-1 rounded-lg text-sm">PISO {{ $numPiso }}</span>
            <span class="text-slate-400 text-sm font-medium">{{ $habitaciones->count() }} Habitaciones registradas</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($habitaciones as $hab)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-xl hover:border-blue-300 transition-all duration-300 group">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-3xl font-black text-[#041b32] group-hover:text-blue-600">#{{ $hab->numero_habitacion }}</span>
                    <button onclick='abrirModalEdicion(@json($hab))' class="bg-blue-50 text-blue-600 p-2 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        
                        <div class="overflow-hidden">
                            <p class="text-[9px] uppercase font-bold text-slate-400 leading-none">Access Point</p>
                            <p class="text-sm font-bold text-slate-700 truncate">{{ $hab->ap_modelo ?? '---' }}</p>
                            <p class="text-[9px] font-mono text-slate-400">MAC: {{ $hab->ap_mac ?? '---' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                       
                        <div class="overflow-hidden">
                            <p class="text-[9px] uppercase font-bold text-slate-400 leading-none">TV & Control</p>
                            <p class="text-sm font-bold text-slate-700 truncate">{{ $hab->tv_modelo ?? '---' }}</p>
                            <p class="text-[10px] text-slate-500">
                                <span class="font-bold">TV S/N:</span> {{ $hab->tv_serie ?? '---' }}<br>
                                <span class="font-bold">Ctrl S/N:</span> {{ $hab->control_serie ?? '---' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        
                        <div class="overflow-hidden">
                            <p class="text-[9px] uppercase font-bold text-slate-400 leading-none">Caja de Seguridad</p>
                            <p class="text-sm font-bold text-slate-700 truncate">{{ $hab->caja_fuerte_modelo ?? '---' }}</p>
                            <p class="text-[9px] font-mono text-slate-400">S/N: {{ $hab->caja_fuerte_serie ?? '---' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full py-10 text-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-200">
                <p class="text-slate-400 italic font-medium">No hay registros de habitaciones en este piso.</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

<div id="modalEdicion" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#041b32] p-6 text-white flex justify-between items-center">
            <div>
                <h3 class="text-xl font-black uppercase tracking-tight">Inventario Técnico Completo</h3>
                <p class="text-blue-300 text-xs font-bold">HABITACIÓN #<span id="modal_num_hab"></span></p>
            </div>
            <button onclick="cerrarModalEdicion()" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">✕</button>
        </div>

        <form id="formUpdateHab" method="POST" class="p-8 max-h-[80vh] overflow-y-auto">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="space-y-4">
                    <h4 class="text-blue-600 font-black text-xs uppercase border-b border-blue-100 pb-2 flex items-center gap-2">
                        <span></span> Infraestructura Red
                    </h4>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Modelo AP</label>
                        <input type="text" name="ap_modelo" id="ap_modelo" class="w-full border-slate-200 rounded-lg p-2 text-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Número de Serie AP</label>
                        <input type="text" name="ap_serie" id="ap_serie" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Dirección MAC</label>
                        <input type="text" name="ap_mac" id="ap_mac" class="w-full border-slate-200 rounded-lg p-2 font-mono text-sm" placeholder="00:00:00...">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">IP Asignada</label>
                        <input type="text" name="ap_ip" id="ap_ip" class="w-full border-slate-200 rounded-lg p-2 font-mono text-sm" placeholder="192.168...">
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-purple-600 font-black text-xs uppercase border-b border-purple-100 pb-2 flex items-center gap-2">
                        <span></span> Televisión
                    </h4>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Marca</label>
                        <input type="text" name="tv_marca" id="tv_marca" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Modelo</label>
                            <input type="text" name="tv_modelo" id="tv_modelo" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Pulgadas</label>
                            <input type="text" name="tv_pulgadas" id="tv_pulgadas" class="w-full border-slate-200 rounded-lg p-2 text-sm" placeholder="50">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Número de Serie TV</label>
                        <input type="text" name="tv_serie" id="tv_serie" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                    </div>
                    <div class="bg-purple-50 p-3 rounded-xl border border-purple-100">
                        <label class="text-[10px] font-bold text-purple-600 uppercase">Serie del Control Remoto</label>
                        <input type="text" name="control_serie" id="control_serie" class="w-full border-purple-200 rounded-lg p-2 text-sm font-bold">
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-yellow-600 font-black text-xs uppercase border-b border-yellow-100 pb-2 flex items-center gap-2">
                        <span></span> Otros Activos
                    </h4>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Modelo Caja Fuerte</label>
                        <input type="text" name="caja_fuerte_modelo" id="caja_fuerte_modelo" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Serie Caja Fuerte</label>
                        <input type="text" name="caja_fuerte_serie" id="caja_fuerte_serie" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Estado Batería Caja</label>
                        <select name="caja_fuerte_bateria" id="caja_fuerte_bateria" class="w-full border-slate-200 rounded-lg p-2 text-sm">
                            <option value="OK">OK (Cargada)</option>
                            <option value="Baja">Baja (Cambiar pronto)</option>
                            <option value="Requiere cambio">Requiere cambio ya</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Notas Técnicas</label>
                        <textarea name="notas_tecnicas" id="notas_tecnicas" rows="3" class="w-full border-slate-200 rounded-lg p-2 text-sm italic" placeholder="Ej: Enchufe detrás de cama flojo..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 border-t pt-6">
                <button type="button" onclick="cerrarModalEdicion()" class="px-6 py-2 rounded-xl font-bold text-slate-400 hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="bg-[#041b32] text-white px-10 py-3 rounded-xl font-black shadow-lg hover:scale-105 transition-all">
                    GUARDAR INVENTARIO COMPLETO
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalEdicion(hab) {
        document.getElementById('modalEdicion').classList.remove('hidden');
        document.getElementById('modal_num_hab').innerText = hab.numero_habitacion;
        
        // Llenado de RED
        document.getElementById('ap_modelo').value = hab.ap_modelo || '';
        document.getElementById('ap_serie').value = hab.ap_serie || '';
        document.getElementById('ap_mac').value = hab.ap_mac || '';
        document.getElementById('ap_ip').value = hab.ap_ip || '';

        // Llenado de TV
        document.getElementById('tv_marca').value = hab.tv_marca || '';
        document.getElementById('tv_modelo').value = hab.tv_modelo || '';
        document.getElementById('tv_pulgadas').value = hab.tv_pulgadas || '';
        document.getElementById('tv_serie').value = hab.tv_serie || '';
        document.getElementById('control_serie').value = hab.control_serie || '';

        // Llenado de CAJA Y NOTAS
        document.getElementById('caja_fuerte_modelo').value = hab.caja_fuerte_modelo || '';
        document.getElementById('caja_fuerte_serie').value = hab.caja_fuerte_serie || '';
        document.getElementById('caja_fuerte_bateria').value = hab.caja_fuerte_bateria || 'OK';
        document.getElementById('notas_tecnicas').value = hab.notas_tecnicas || '';
        
        document.getElementById('formUpdateHab').action = `/inventario/habitaciones/${hab.id}`;
    }

    function cerrarModalEdicion() {
        document.getElementById('modalEdicion').classList.add('hidden');
    }
</script>

<style>
    @media print {
        nav, button, .no-print, #modalCrear, #modalEdicion { display: none !important; }
        body { background: white !important; }
        .grid { display: block !important; }
        .bg-white { border: 1px solid #ddd !important; margin-bottom: 20px !important; break-inside: avoid; }
    }
</style>
@endsection