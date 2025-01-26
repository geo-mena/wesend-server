<!DOCTYPE html>
<html>
<head>
    <style>
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <p>Enviado por: <strong>{{ $data['senderEmail'] }}</strong></p>
        
        <h3>Contenido de la descarga</h3>
        @foreach ($data['files'] as $file)
            <p>{{ $file->original_name }}</p>
            <p>Tamaño del archivo: {{ number_format($file->size / 1024, 2) }} KB</p>
        @endforeach

        <p>Este enlace es válido hasta el {{ $data['expirationDate'] }} (UTC +01:00)</p>

        <a href="{{ config('app.frontend_url') }}/send/{{ $data['downloadToken'] }}" class="button">
            Descargar el archivo
        </a>

        @if($data['message'])
            <hr>
            <p><strong>Mensaje:</strong></p>
            <p>{{ $data['message'] }}</p>
        @endif
    </div>
</body>
</html>