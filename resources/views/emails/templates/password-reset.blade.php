@extends('emails.layouts.base')

@section('content')
    <div class="header">
        <h1>Recuperación de Contraseña</h1>
    </div>
    
    <div class="content">
        <p>Hola {{ $name }},</p>
        
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
        
        <p style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a>
        </p>
        
        <p>Este enlace expirará en {{ $expirationMinutes }} minutos.</p>
        
        <p>Si no solicitaste un restablecimiento de contraseña, puedes ignorar este correo de forma segura.</p>
        
        <p style="font-size: 12px; color: #666;">
            Si tienes problemas con el botón, copia y pega esta URL en tu navegador:<br>
            <code>{{ $resetUrl }}</code>
        </p>
    </div>
@endsection