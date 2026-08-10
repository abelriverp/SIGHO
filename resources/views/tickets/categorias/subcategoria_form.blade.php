@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-[#041b32] px-6 py-4">
            <h3 class="text-white font-bold text-lg">
                {{ isset($subcategoria) ? 'Editar Sistema' : 'Nuevo Sistema' }}
                <span class="text-blue-200 font-normal text-sm">— {{ $categoria->label }}</span>
            </h3>
        </div>

        <form action="{{ isset($subcategoria) ? route('tickets.categorias.subcategorias.update', $subcategoria) : route('tickets.categorias.subcategorias.store') }}"
              method="POST" class="p-6 space-y-6"
              x-data="camposEditor({{ isset($subcategoria) ? $subcategoria->campos->map(fn($c) => ['name' => $c->name, 'label' => $c->label, 'tipo_campo' => $c->tipo_campo, 'requerido' => $c->requerido])->toJson() : '[]' }})">
            @csrf
            @if(isset($subcategoria))
                @method('PUT')
            @else
                <input type="hidden" name="ticket_categoria_id" value="{{ $categoria->id }}">
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <ul class="list-disc ml-5 text-sm">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del sistema</label>
                <input type="text" name="label" value="{{ old('label', $subcategoria->label ?? '') }}" required
                    placeholder="Ej: Facto, Simphony, Opera, GestHotel..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Nota / recordatorio <span class="text-slate-400 font-normal">(opcional)</span></label>
                <textarea name="nota" rows="2" placeholder='Ej: "Un ticket por usuario: no incluyas la información de más de un colaborador."'
                    class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 focus:outline-none focus:border-[#041b32]">{{ old('nota', $subcategoria->nota ?? '') }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Se muestra tal cual arriba de los campos, en el formulario de crear ticket.</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Orden</label>
                    <input type="number" name="orden" min="0" value="{{ old('orden', $subcategoria->orden ?? 0) }}"
                        class="w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3">
                </div>
                <div class="flex items-end gap-2 pb-2">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', $subcategoria->activo ?? true) ? 'checked' : '' }} class="w-4 h-4">
                    <label class="text-sm text-slate-700">Activo (visible al crear tickets)</label>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h4 class="text-sm font-bold text-slate-700 mb-2">Campos que pide este sistema</h4>

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
                <p class="text-[11px] text-slate-400 mt-2">
                    "Clave interna" es el nombre técnico del campo (sin espacios, ej. <code>id_usuario</code>) — no lo ve el usuario, pero debe ser único dentro de este sistema.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('tickets.categorias.show', $categoria) }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded shadow hover:bg-gray-400">Cancelar</a>
                <button type="submit" class="bg-[#041b32] text-white font-bold py-2 px-8 rounded shadow hover:opacity-90">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function camposEditor(camposIniciales) {
        return { campos: camposIniciales.length ? camposIniciales : [] };
    }
</script>
@endsection
