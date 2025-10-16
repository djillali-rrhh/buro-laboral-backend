@include('emails.layouts.header')

<h2>¡Hola {{ $nombre }}! 👋</h2>

<p>
    Es un placer darte la bienvenida a <strong>RRHH INGENIA</strong>. 
    Tu registro fue procesado exitosamente el día <strong>{{ $fecha }}</strong>.
</p>

<div class="info-box">
    <strong>📋 Información de tu cuenta:</strong><br>
    Usuario: {{ $nombre }}<br>
    Fecha de registro: {{ $fecha }}<br>
    Estatus: Activo ✅
</div>

<p>
    Estamos emocionados de tenerte con nosotros. A partir de ahora podrás acceder 
    a todas las funcionalidades de nuestra plataforma.
</p>

<center>
    <a href="{{ $url ?? '#' }}" class="button">
        Ir a mi cuenta
    </a>
</center>

<p>
    Si tienes alguna duda o necesitas ayuda, no dudes en contactarnos.
</p>

<p style="margin-top: 30px;">
    Saludos cordiales,<br>
    <strong>El equipo de RRHH INGENIA</strong>
</p>

@include('emails.layouts.footer')