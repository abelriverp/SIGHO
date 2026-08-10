@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">Nuevo Punto QR</h3>
        </div>

        <form action="{{ route('puntos-qr.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

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
                <label class="block text-sm font-bold text-slate-700 mb-1">Número de habitación / nombre de unidad</label>
                <input type="text" name="identificador" value="{{ old('identificador') }}" required
                    placeholder="Ej: 204 o Depto 4B"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                <p class="text-[10px] text-slate-400 mt-1 italic">
                    El sistema genera el enlace y el código QR automáticamente al guardar.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('puntos-qr.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Generar QR</button>
            </div>
        </form>
    </div>
</div>
@endsection
