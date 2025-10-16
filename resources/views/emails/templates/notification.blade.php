@extends('emails.layouts.base')

@section('content')
    <div class="header">
        <h1>{{ $title }}</h1>
    </div>
    
    <div class="content">
        <p>Hola {{ $recipientName }},</p>
        
        <p>{{ $message }}</p>
        
        @if(isset($actionUrl) && isset($actionText))
            <p style="text-align: center;">
                <a href="{{ $actionUrl }}" class="button">{{ $actionText }}</a>
            </p>
        @endif
        
        @if(isset($additionalInfo))
            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
                {!! $additionalInfo !!}
            </div>
        @endif
    </div>
@endsection