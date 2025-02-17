<!DOCTYPE html>
<html>
<head>
    <title>Detalles de la Descarga</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #d5e9fb;
        }
        .content {
            background-color: #f2f6f9;
            padding: 32px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            text-align: center;
            margin: 20px;
        }
        .card {
            width: 600px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            background-color: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            text-align: left;
        }
        .sender-info {
            background-color: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .message-title {
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .sender-email {
            color: #374151;
            font-weight: 500;
        }
        .section-title {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin: 32px 0 16px;
            text-align: left;
        }
        .file-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .file-name {
            display: flex;
            align-items: center;
            color: #374151;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .file-icon {
            margin-right: 12px;
            color: #6b7280;
        }
        .file-size {
            color: #6b7280;
            font-size: 14px;
        }
        .expiry-info {
            background-color: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            color: #4b5563;
            font-size: 14px;
            margin: 24px 0;
            display: flex;
            align-items: center;
        }
        .expiry-icon {
            margin-right: 12px;
        }
        .button-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
            padding: 24px 0;
        }
        .download-button {
            background-color: #10b981;
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: all 0.2s ease;
            min-width: 200px;
        }
        .download-button:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }
        .button-icon {
            margin-right: 8px;
        }
        .message-section {
            margin: 24px 0;
        }
        .message-content {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            color: #4b5563;
            line-height: 1.5;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="card">
            <!-- Sección del remitente -->
            <div class="sender-info">
                <div class="message-title">Enviado por:</div>
                <div class="sender-email">{{ $data['senderEmail'] }}</div>
            </div>

            <!-- Sección del mensaje -->
            @if($data['message'])
                <div class="message-section">
                    <div class="message-content">
                        {{ $data['message'] }}
                    </div>
                </div>
            @endif

            <!-- Sección de archivos -->
            <div class="section-title">Contenido de la descarga</div>
            @foreach ($data['files'] as $file)
                <div class="file-card">
                    <div class="file-name">
                        <span class="file-icon">📄</span>
                        {{ $file->original_name }}
                    </div>
                    <div class="file-size">
                        Tamaño del archivo: {{ number_format($file->size / 1024, 2) }} KB
                    </div>
                </div>
            @endforeach

            <!-- Sección de expiración -->
            <div class="expiry-info">
                <span class="expiry-icon">⏳</span>
                Este enlace es válido hasta el {{ $data['expirationDate'] }} (UTC +01:00)
            </div>

            <!-- Botón de descarga -->
            <div class="button-wrapper">
                <a href="{{ config('app.frontend_url') }}/send/{{ $data['downloadToken'] }}" 
                   class="download-button">
                    <span class="button-icon">⬇️</span>
                    Descargar el archivo
                </a>
            </div>
        </div>
    </div>
</body>
</html>