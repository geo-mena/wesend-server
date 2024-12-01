<!DOCTYPE html>
<html>
<head>
    <title>Archivo Compartido</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>¡Alguien ha compartido archivos contigo!</h2>
        
        <p>{{ $transfer->sender_email }} te ha enviado algunos archivos.</p>
        
        @if($transfer->message)
            <p>Mensaje: {{ $transfer->message }}</p>
        @endif

        <h3>Archivos compartidos:</h3>
        <ul>
            @foreach($transfer->files as $file)
                <li>{{ $file->original_name }}</li>
            @endforeach
        </ul>

        <p>
            <a href="{{ route('download', ['token' => $transfer->download_token]) }}" 
               style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Descargar Archivos
            </a>
        </p>

        @if($transfer->password)
            <p>Contraseña requerida para la descarga: <strong>{{ $transfer->password }}</strong></p>
        @endif

        <p>Este enlace expirará el {{ \Carbon\Carbon::parse($transfer->expires_at)->format('d/m/Y H:i') }}</p>

        <p style="font-size: 12px; color: #666;">
            Este es un correo automático, por favor no responder.
        </p>
    </div>
</body>
</html>