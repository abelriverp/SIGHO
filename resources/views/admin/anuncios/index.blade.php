@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto mt-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#041b32]">📢 Anuncios</h2>
        <p class="text-sm text-slate-500">Avisa por correo de una función nueva o un cambio, solo a quienes tienen acceso al módulo afectado. Queda visible para ellos en "Novedades".</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <ul class="list-disc ml-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario para redactar uno nuevo --}}
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-sm font-bold text-slate-700 mb-4">Redactar anuncio</h3>
        <form action="{{ route('admin.anuncios.store') }}" method="POST">
            @csrf

            <label class="block text-xs font-bold text-slate-600 mb-1">¿A quién le llega?</label>
            <select name="modulo_id" class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 mb-4">
                <option value="">Todos los usuarios (anuncio general)</option>
                @foreach($modulos as $modulo)
                    <option value="{{ $modulo->id }}" {{ old('modulo_id') == $modulo->id ? 'selected' : '' }}>
                        Solo quienes tienen acceso a: {{ $modulo->nombre }}
                    </option>
                @endforeach
            </select>
            <p class="text-[10px] text-slate-400 -mt-3 mb-4 italic">
                "Gestión de Tickets" es libre para todos, así que un anuncio de ese módulo también le llega a todos los usuarios. Los administradores siempre reciben cualquier anuncio, aunque no tengan el módulo asignado explícitamente.
            </p>

            <label class="block text-xs font-bold text-slate-600 mb-1">Título</label>
            <input type="text" name="titulo" required maxlength="255" value="{{ old('titulo') }}"
                placeholder="Ej: Ahora puedes filtrar el inventario por categoría"
                class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 mb-4">

            <label class="block text-xs font-bold text-slate-600 mb-1">Mensaje / mini-tutorial</label>
            <textarea name="mensaje" required rows="6" maxlength="5000"
                placeholder="Explica qué cambió y, si aplica, los pasos para usarlo. Cada línea se manda por separado en el correo, así que puedes numerarlas (1. ... 2. ... 3. ...) para que se lea como una guía rápida."
                class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 mb-4">{{ old('mensaje') }}</textarea>

            <button type="submit"
                onclick="return confirm('¿Enviar este anuncio por correo a los usuarios correspondientes?');"
                class="bg-[#041b32] text-white font-bold py-2 px-6 rounded-lg shadow hover:opacity-90">
                Enviar anuncio
            </button>
        </form>
    </div>

    {{-- Historial --}}
    <h3 class="text-sm font-bold text-slate-700 mb-3">Historial de anuncios enviados</h3>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 border-b">
                <tr>
                    <th class="py-3 px-6">Título</th>
                    <th class="py-3 px-6">Dirigido a</th>
                    <th class="py-3 px-6">Destinatarios</th>
                    <th class="py-3 px-6">Enviado por</th>
                    <th class="py-3 px-6">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($anuncios as $anuncio)
                <tr class="border-b border-slate-50 hover:bg-slate-50 align-top">
                    <td class="py-4 px-6 font-medium text-slate-800">
                        {{ $anuncio->titulo }}
                        <p class="text-xs text-slate-400 font-normal mt-1 whitespace-pre-line max-w-md">{{ \Illuminate\Support\Str::limit($anuncio->mensaje, 140) }}</p>
                    </td>
                    <td class="py-4 px-6 text-slate-600">{{ $anuncio->modulo->nombre ?? 'Todos los usuarios' }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $anuncio->total_destinatarios }}</td>
                    <td class="py-4 px-6 text-slate-600">{{ $anuncio->autor->name ?? '—' }}</td>
                    <td class="py-4 px-6 text-slate-400 text-xs">{{ $anuncio->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-400 text-sm">Todavía no se ha enviado ningún anuncio.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
