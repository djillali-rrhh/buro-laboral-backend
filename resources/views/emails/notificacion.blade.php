@include('emails.layouts.header')

<h2>📢 {{ $titulo ?? 'Nueva Notificación' }}</h2>

<p>Hola <strong>{{ $nombre }}</strong>,</p>

<p>{{ $mensaje }}</p>

@if(isset($detalles) && count($detalles) > 0)
<div class="info-box">
    <strong>Detalles:</strong><br>
    @foreach($detalles as $key => $valor)
        <strong>{{ $key }}:</strong> {{ $valor }}<br>
    @endforeach
</div>
@endif

@if(isset($accion_url))
<center>
    <a href="{{ $accion_url }}" class="button">
        {{ $accion_texto ?? 'Ver más' }}
    </a>
</center>
@endif

<p style="margin-top: 30px;">
    Saludos,<br>
    <strong>RRHH INGENIA</strong>
</p>

@include('emails.layouts.footer')