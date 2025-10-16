@extends('emails.layouts.base')

@section('content')
    <div class="header">
        <h1>¡Bienvenido {{ $name }}!</h1>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $name }}</strong>,</p>
        
        <p>Nos complace darte la bienvenida a nuestra plataforma. Estamos emocionados de tenerte con nosotros.</p>
        
        @if(isset($verificationUrl))
            <p>Para comenzar, por favor verifica tu cuenta haciendo clic en el siguiente botón:</p>
            <p style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verificar mi cuenta</a>
            </p>
        @endif
        
        <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
        
        <p>Saludos cordiales,<br>El equipo de {{ config('app.name') }}</p>
    </div>
@endsection

@section('footer')
    <p>Si no creaste esta cuenta, puedes ignorar este correo.</p>
@endsection