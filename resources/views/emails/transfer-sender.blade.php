<!DOCTYPE html>
<html>
<head>
    <style>
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>¡Enhorabuena!</h2>
        <p>Se ha enviado con éxito tu archivo. Te avisaremos por mail cuando el destinatario lo haya descargado.</p>

        <p><strong>Enviado a:</strong> {{ $data['recipient_email'] }}</p>

        <h3>Contenido de la descarga:</h3>
        <ul>
            @foreach ($data['files'] as $file)
                <li>{{ $file->original_name }}</li>
            @endforeach
        </ul>

        <p><strong>Tamaño de los archivos:</strong> {{ $data['total_size'] }}</p>
        <p><strong>Este enlace es válido hasta el:</strong> {{ $data['expiration_date'] }}</p>

        <a href="{{ config('app.frontend_url') }}/send/{{ $data['download_link'] }}" class="button">
            Descargar archivo enviado
        </a>

        <a href="{{ config('app.frontend_url') }}/send/{{ $data['delete_link'] }}" class="button" style="background: #dc3545;">
            Eliminar la transferencia
        </a>
    </div>
</body>
</html>