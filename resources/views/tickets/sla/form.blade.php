@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($politica) ? 'Editar Política de SLA' : 'Nueva Política de SLA' }}</h3>
        </div>

        <form action="{{ isset($politica) ? route('tickets.sla.update', $politica->id) : route('tickets.sla.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($politica)) @method('PUT') @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <ul class="list-disc ml-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Departamento</label>
                <select name="departamento_id" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="">Todos los departamentos (default de la propiedad)</option>
                    @foreach($departamentos as $depto)
                        <option value="{{ $depto->id }}" {{ (isset($politica) && $politica->departamento_id == $depto->id) ? 'selected' : '' }}>
                            {{ $depto->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1 italic">Deja "Todos los departamentos" para fijar un default general de la propiedad; un departamento específico sobre-escribe ese default solo para él.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Prioridad</label>
                <select name="prioridad" required class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    @foreach(\App\Services\SlaService::PRIORIDADES as $prioridad)
                        <option value="{{ $prioridad }}" {{ (isset($politica) ? $politica->prioridad : old('prioridad')) === $prioridad ? 'selected' : '' }}>
                            {{ $prioridad }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Horas para resolver</label>
                <input type="number" name="horas_resolucion" min="1" max="2160" required
                    value="{{ $politica->horas_resolucion ?? old('horas_resolucion') }}"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                <p class="text-[10px] text-slate-400 mt-1 italic">Desde que se crea el ticket hasta que debería quedar Resuelto.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Avisar "por vencer" con anticipación de (horas)</label>
                <input type="number" name="horas_alerta_temprana" min="0" max="2160"
                    value="{{ $politica->horas_alerta_temprana ?? old('horas_alerta_temprana') }}"
                    placeholder="Vacío = 20% de las horas de resolución"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                <p class="text-[10px] text-slate-400 mt-1 italic">Opcional. Si lo dejas vacío, se calcula automáticamente como el 20% de las horas de resolución.</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="activo" name="activo" value="1"
                    {{ (!isset($politica) || $politica->activo) ? 'checked' : '' }}
                    class="w-4 h-4">
                <label for="activo" class="text-sm font-bold text-slate-700">Activa</label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('tickets.sla.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
