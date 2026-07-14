{{--
    Ícono "?" con tooltip al pasar el cursor. Requiere que el contenedor
    padre tenga la clase `relative` para posicionarse en su esquina.

    Uso:
        <div class="relative ...">
            <x-info-tip>Texto explicativo aquí.</x-info-tip>
            ...resto de la tarjeta...
        </div>

    No usar dentro de contenedores con `overflow-hidden`: el popup se
    recortaría. Para esos casos, usar un subtítulo de texto fijo en su lugar.
--}}
@props(['align' => 'right'])

<div class="absolute top-2 {{ $align === 'right' ? 'right-2' : 'left-2' }} group z-10">
    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/80 border border-gray-300 text-gray-500 text-[11px] font-bold cursor-help hover:bg-white hover:text-gray-700 transition">?</span>
    <div class="hidden group-hover:block absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} top-full mt-2 w-64 rounded-lg bg-gray-900 text-white text-xs leading-relaxed p-3 shadow-lg z-50 normal-case text-left">
        {{ $slot }}
    </div>
</div>
