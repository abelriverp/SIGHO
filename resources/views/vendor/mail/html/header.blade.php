@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- El logo se incrusta como adjunto (CID) en lugar de cargarse desde una URL pública,
     para que se vea igual en Gmail/Outlook aunque APP_URL no sea accesible desde fuera
     de la red del hotel y sin depender de que el cliente de correo cargue imágenes remotas. --}}
<img src="{{ isset($message) ? $message->embed(public_path('img/logo.jpg')) : asset('img/logo.jpg') }}" class="logo" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
