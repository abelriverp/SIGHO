{{--
    Vista markdown propia para ResumenTicketsPendientesNotification.

    NO se arma con ->line() encadenados como el resto de notificaciones del
    proyecto: se probó así primero y las tablas NO se renderizaban (salían
    como texto plano con pipes literales, ver captura del usuario). La
    causa real no se pudo confirmar sin poder correr la app aquí (ver nota
    de PHP en el sandbox), así que se cambió a la forma que Laravel
    documenta explícitamente para tablas en correos: el componente "mail
    table" (ver resources/views/vendor/mail/html/table.blade.php) — ese
    componente llama Illuminate\Mail\Markdown::parse() SOBRE SU PROPIO
    contenido, así que no depende de que el documento completo se parsee
    correctamente de punta a punta.
--}}
@component('mail::message')
# Resumen de tickets pendientes

Hola {{ $notifiable->name }},

Este es un correo generado automáticamente por el sistema de tickets. Es un resumen periódico (no un aviso por cada ticket) para que tengas panorama completo de lo que sigue pendiente en tu propiedad.

**Propiedad:** {{ $hotelNombre }} ({{ $hotelCode }})

@component('mail::table')
| Indicador | Valor |
| --- | ---: |
| Total de tickets pendientes | **{{ $total }}** |
| Sin ninguna nota hace {{ $umbralDias }}+ días | **{{ $sinSeguimiento }}** ({{ $porcentaje }}%) |
@endcomponent

{{ $barra }} **{{ $porcentaje }}%** de los pendientes llevan {{ $umbralDias }}+ días sin seguimiento.

@component('mail::table')
| # | Departamento | Prioridad | Días abierto | Sin seguimiento | Reportó |
| ---: | --- | :---: | ---: | ---: | --- |
@foreach ($filas as $fila)
| {{ $fila['numero'] }} | {{ $fila['departamento'] }} | {{ $fila['prioridad'] }} | {{ $fila['dias_abierto'] }} | {{ $fila['seguimiento'] }} | {{ $fila['reporto'] }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => $actionUrl])
Ver todos los tickets
@endcomponent

Si ya se está atendiendo alguno de estos, pide al departamento responsable que deje una nota en la bitácora para que no vuelva a salir como "sin seguimiento" en el próximo resumen.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
