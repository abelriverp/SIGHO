@php
    $mon = $d->asset?->monitorear ? $d->asset->estado_monitoreo : null;
    $punto = match ($mon) { 'activo' => 'bg-green-500', 'caido' => 'bg-red-500', null => 'bg-slate-300', default => 'bg-amber-400' };
    $hijos = $porPadre[$d->id] ?? collect();
@endphp
<li class="relative">
    <div class="inline-flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-sm my-1">
        <span class="w-2.5 h-2.5 rounded-full {{ $punto }}" title="{{ $mon ? 'Monitoreo: '.$mon : 'Sin monitoreo' }}"></span>
        <a href="{{ route('network.show', $d->id) }}" class="font-bold text-[#041b32] hover:underline">{{ $d->nombre }}</a>
        <span class="text-xs text-slate-500">{{ $d->tipo }}</span>
        @if($d->ip_gestion ?: $d->asset?->ip_address)<span class="text-xs font-mono text-slate-500">{{ $d->ip_gestion ?: $d->asset?->ip_address }}</span>@endif
        @if($d->ubicacionCatalogo)<span class="text-xs bg-slate-100 rounded px-1.5">{{ $d->ubicacionCatalogo->codigo }}{{ $d->posicion_rack ? ' · '.$d->posicion_rack : '' }}</span>@endif
        <span class="text-xs text-slate-400">{{ $d->ocupados_count }}/{{ $d->ports_count }} puertos</span>
        @if($d->uplink_puerto)<span class="text-[10px] text-slate-400">uplink: {{ $d->uplink_puerto }}</span>@endif
    </div>
    @if($hijos->isNotEmpty() && $nivel < 8)
        <ul class="ml-6 pl-4 border-l-2 border-slate-200">
            @foreach($hijos as $h)
                @include('network._nodo', ['d' => $h, 'nivel' => $nivel + 1])
            @endforeach
        </ul>
    @endif
</li>
