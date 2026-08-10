@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">{{ isset($categoria) ? 'Editar Categoría' : 'Nueva Categoría' }}</h3>
        </div>

        <form action="{{ isset($categoria) ? route('tickets.categorias.update', $categoria) : route('tickets.categorias.store') }}"
              method="POST" class="p-6 space-y-6"
              x-data="camposEditor({{ isset($categoria) ? $categoria->campos->map(fn($c) => ['name' => $c->name, 'label' => $c->label, 'tipo_campo' => $c->tipo_campo, 'requerido' => $c->requerido])->toJson() : '[]' }}, {{ old('requiere_subcategoria', $categoria->requiere_subcategoria ?? true) ? 'true' : 'false' }})">
            @csrf
            @if(isset($categoria)) @method('PUT') @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <ul class="list-disc ml-5 text-sm">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre de la categoría</label>
                <input type="text" name="label" value="{{ old('label', $categoria->label ?? '') }}" required
                    placeholder="Ej: Alta de usuario, Cambio de contraseña..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Departamento</label>
                <select name="departamento_id" required class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                    <option value="" disabled {{ old('departamento_id', $categoria->departamento_id ?? '') ? '' : 'selected' }}>Selecciona un departamento...</option>
                    @foreach($departamentos as $depto)
                        <option value="{{ $depto->id }}" {{ (string) old('departamento_id', $categoria->departamento_id ?? '') === (string) $depto->id ? 'selected' : '' }}>
                            {{ $depto->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="requiereSubcategoria" name="requiere_subcategoria" value="1"
                    x-model="requiereSubcategoria"
                    {{ old('requiere_subcategoria', $categoria->requiere_subcategoria ?? true) ? 'checked' : '' }}
                    class="w-4 h-4">
                <label for="requiereSubcategoria" class="text-sm text-slate-700">
                    Esta categoría pide elegir un <strong>sistema</strong> (ej. Facto, Simphony, Opera...)
                </label>
            </div>
            <p class="text-[11px] text-slate-400 -mt-4">
                Márcalo si esta categoría aplica a varios sistemas distintos (cada uno con sus propios campos, gestionados aparte).
                Déjalo sin marcar para categorías simples de un solo paso (ej. "Otra falla / Otro"), cuyos campos se definen aquí abajo.
            </p>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Orden</label>
                    <input type="number" name="orden" min="0" value="{{ old('orden', $categoria->orden ?? 0) }}"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                </div>
                <div class="flex items-end gap-2 pb-2">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', $categoria->activo ?? true) ? 'checked' : '' }} class="w-4 h-4">
                    <label class="text-sm text-slate-700">Activa (visible al crear tickets)</label>
                </div>
            </div>

            {{-- Campos directos: solo si NO requiere elegir sistema --}}
            <div x-show="!requiereSubcategoria" x-cloak class="border-t border-slate-100 pt-4">
                <h4 class="text-sm font-bold text-slate-700 mb-2">Campos que pide esta categoría</h4>
                <p class="text-[11px] text-slate-400 mb-3">Se piden directo, sin elegir sistema (ej. un solo campo "Especifica el problema").</p>

                <template x-for="(campo, i) in campos" :key="i">
                    <div class="grid grid-cols-12 gap-2 items-center border-b border-slate-100 py-2">
                        <input type="text" x-model="campo.name" :name="'campos['+i+'][name]'" placeholder="clave_interna"
                            class="col-span-3 bg-white border border-slate-300 rounded-md py-1.5 px-2 text-sm">
                        <input type="text" x-model="campo.label" :name="'campos['+i+'][label]'" placeholder="Etiqueta visible"
                            class="col-span-4 bg-white border border-slate-300 rounded-md py-1.5 px-2 text-sm">
                        <select x-model="campo.tipo_campo" :name="'campos['+i+'][tipo_campo]'" class="col-span-2 bg-white border border-slate-300 rounded-md py-1.5 px-2 text-sm">
                            <option value="text">Texto corto</option>
                            <option value="textarea">Texto largo</option>
                            <option value="email">Correo</option>
                        </select>
                        <label class="col-span-2 flex items-center gap-1 text-[11px] text-slate-600">
                            <input type="checkbox" x-model="campo.requerido" :name="'campos['+i+'][requerido]'" value="1"> Obligatorio
                        </label>
                        <button type="button" @click="campos.splice(i, 1)" class="col-span-1 text-red-500 hover:text-red-700 text-sm font-bold">✕</button>
                    </div>
                </template>

                <button type="button" @click="campos.push({name:'', label:'', tipo_campo:'text', requerido:true})"
                    class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-bold">+ Agregar campo</button>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('tickets.categorias.index') }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function camposEditor(camposIniciales, requiereSubcategoriaInicial) {
        return {
            requiereSubcategoria: requiereSubcategoriaInicial,
            campos: camposIniciales.length ? camposIniciales : [],
        };
    }
</script>
@endsection
