@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($hotel) ? 'Editar Propiedad' : 'Registrar Nueva Propiedad' }}</h3>
        </div>

        <form action="{{ isset($hotel) ? route('admin.hoteles.update', $hotel->id) : route('admin.hoteles.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @if(isset($hotel)) @method('PUT') @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Código de Propiedad</label>
                @if(isset($hotel))
                    <input type="text" value="{{ $hotel->hotel_code }}" disabled
                        class="w-full bg-slate-100 border border-slate-300 rounded-md py-2 px-3 text-slate-500 font-mono">
                    <p class="text-[10px] text-slate-400 mt-1 italic">El código no se puede cambiar una vez creado: ya está grabado en usuarios y registros de esta propiedad.</p>
                @else
                    <input type="text" name="hotel_code" value="{{ old('hotel_code') }}" required maxlength="10" placeholder="Ej: {{ config('app.hotel_code') }}"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 font-mono uppercase">
                    <p class="text-[10px] text-slate-400 mt-1 italic">Solo letras/números, sin espacios. No podrás cambiarlo después.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre de la Propiedad</label>
                <input type="text" name="nombre" value="{{ $hotel->nombre ?? old('nombre') }}" required placeholder="Ej: {{ config('app.hotel_nombre') }}"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Tipo</label>
                <select name="tipo" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    @foreach(\App\Models\Hotel::TIPOS as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ (isset($hotel) ? $hotel->tipo : old('tipo')) == $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.hoteles.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection