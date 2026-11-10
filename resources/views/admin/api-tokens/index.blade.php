@extends('layouts.app')

@section('content')
@php $in = 'w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-sm'; $base = url('/api/v1'); @endphp
<div class="max-w-6xl mx-auto mb-10">
    <h2 class="text-2xl font-bold text-slate-800">Tokens de API</h2>
    <p class="text-gray-500 text-sm mt-1 mb-6">Para que otros sistemas (PMS, monitoreo, Power BI, scripts) consulten SIGHO o levanten tickets. Cada token actúa con los permisos y la propiedad del usuario al que se asigna.</p>

    @if(session('token_nuevo'))
        <div class="bg-green-50 border border-green-300 rounded-lg p-4 mb-6" x-data="{ copiado: false }">
            <p class="font-bold text-green-900 mb-2">Copia el token ahora. Por seguridad no se vuelve a mostrar.</p>
            <div class="flex gap-2">
                <input readonly value="{{ session('token_nuevo') }}" id="token-nuevo" class="flex-1 font-mono text-sm bg-white border border-green-300 rounded px-3 py-2">
                <button type="button" @click="navigator.clipboard?.writeText(document.getElementById('token-nuevo').value); copiado = true" class="bg-green-700 text-white text-sm font-bold px-4 rounded" x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</button>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-3 text-sm mb-4">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('admin.api-tokens.store') }}" class="bg-white rounded-lg shadow border border-gray-200 p-5 space-y-4 h-fit">
            @csrf
            <p class="font-bold text-slate-700">Nuevo token</p>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">Nombre / para qué es *</label><input name="nombre" required maxlength="100" value="{{ old('nombre') }}" class="{{ $in }}" placeholder="Ej: Power BI dirección"></div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">Actúa como el usuario *</label>
                <select name="user_id" required class="{{ $in }}">@foreach($usuarios as $u)<option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} ({{ $u->role }})</option>@endforeach</select>
                <p class="text-[10px] text-slate-400 mt-1">Recomendado: una cuenta de servicio con rol Gerente para solo lectura.</p>
            </div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">Vence</label><input type="date" name="expira_at" value="{{ old('expira_at') }}" class="{{ $in }}"><p class="text-[10px] text-slate-400 mt-1">Vacío = no vence.</p></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="puede_escribir" value="1" class="rounded" @checked(old('puede_escribir'))> Puede crear tickets</label>
            <button class="w-full bg-[#041b32] text-white font-bold py-2 rounded">Generar token</button>
        </form>

        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden h-fit">
            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead><tr class="hotel-blue text-white uppercase text-xs"><th class="py-3 px-4">Nombre</th><th class="py-3 px-4">Token</th><th class="py-3 px-4">Usuario</th><th class="py-3 px-4">Permiso</th><th class="py-3 px-4">Último uso</th><th class="py-3 px-4"></th></tr></thead>
                <tbody class="text-sm text-gray-600">
                    @forelse($tokens as $tk)
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 px-4 font-bold text-slate-700">{{ $tk->nombre }}@if($tk->expira_at)<div class="text-xs font-normal {{ $tk->expira_at->isPast() ? 'text-red-600' : 'text-slate-400' }}">{{ $tk->expira_at->isPast() ? 'Venció' : 'Vence' }} {{ $tk->expira_at->format('d/m/Y') }}</div>@endif</td>
                            <td class="py-2.5 px-4 font-mono text-xs">{{ $tk->prefijo }}…</td>
                            <td class="py-2.5 px-4 text-xs">{{ $tk->user?->name ?? '—' }}<div class="text-slate-400">{{ $tk->hotel_code }}</div></td>
                            <td class="py-2.5 px-4 text-xs">{{ $tk->puede_escribir ? 'Lectura y tickets' : 'Solo lectura' }}</td>
                            <td class="py-2.5 px-4 text-xs">{{ $tk->ultimo_uso_at?->diffForHumans() ?? 'Nunca' }}</td>
                            <td class="py-2.5 px-4 text-right"><form method="POST" action="{{ route('admin.api-tokens.destroy', $tk) }}" onsubmit="return confirm('¿Revocar este token? Los sistemas que lo usen dejarán de funcionar.')">@csrf @method('DELETE')<button class="text-red-600 text-xs font-bold hover:underline">Revocar</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-400">Sin tokens.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-5 mt-6 text-sm text-slate-700">
        <p class="font-bold mb-2">Cómo se usa</p>
        <p class="mb-2">Envía el token en el encabezado <code class="bg-slate-100 px-1">Authorization: Bearer sigho_…</code>. Límite: 60 peticiones por minuto. Respuestas en JSON.</p>
        <div class="overflow-x-auto"><table class="w-full text-xs font-mono mb-3">
            @foreach([
                ['GET', '/activos', 'Activos paginados (?categoria= ?status= ?q= ?por_pagina=)'],
                ['GET', '/activos/{nomenclatura}', 'Un activo con ubicación, garantía y estado de monitoreo'],
                ['GET', '/tickets', 'Tickets paginados (?status= ?desde=AAAA-MM-DD ?por_pagina=)'],
                ['POST', '/tickets', 'Crear ticket (token con permiso de escritura)'],
                ['GET', '/monitoreo', 'Estado de los equipos monitoreados'],
                ['GET', '/kpis', 'Indicadores (?desde= ?hasta=, por defecto el mes en curso)'],
            ] as [$m, $r, $d])
                <tr class="border-b border-slate-50"><td class="py-1 pr-3 font-bold {{ $m === 'POST' ? 'text-amber-700' : 'text-green-700' }}">{{ $m }}</td><td class="py-1 pr-3">{{ $base }}{{ $r }}</td><td class="py-1 font-sans text-slate-500">{{ $d }}</td></tr>
            @endforeach
        </table></div>
        <p class="font-bold text-xs mb-1">Ejemplo (PowerShell): levantar un ticket desde un script</p>
        <pre class="bg-slate-900 text-slate-100 text-xs rounded p-3 overflow-x-auto">Invoke-RestMethod -Method Post -Uri "{{ $base }}/tickets" `
  -Headers @{ Authorization = "Bearer sigho_TU_TOKEN" } `
  -Body @{ departamento_destino = "Sistemas"; prioridad = "Alta"; descripcion = "Respaldo nocturno falló"; activo = "SRV-01" }</pre>
    </div>
</div>
@endsection
