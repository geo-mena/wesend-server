<!DOCTYPE html>
<html>
<head>
    <title>Transferencia Exitosa</title>
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
            width: 500px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            background-color: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #10b981;
        }
        .subtitle {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .recipient {
            background-color: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            text-align: left;
        }
        .recipient strong {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }
        .files-section {
            margin: 16px 0;
            text-align: left;
        }
        .files-list {
            list-style: none;
            padding: 0;
            margin: 12px 0;
        }
        .file-item {
            padding: 12px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .file-icon {
            margin-right: 12px;
            color: #6b7280;
        }
        .info-box {
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            margin: 12px 0;
            font-size: 14px;
            color: #4b5563;
            text-align: left;
        }
        .info-box strong {
            color: #374151;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }
        .buttons-container {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
        }
        .button {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 200px;
        }
        .button:hover {
            transform: translateY(-1px);
        }
        .button-download {
            background-color: #10b981;
            color: white;
        }
        .button-download:hover {
            background-color: #059669;
        }
        .button-delete {
            background-color: #ef4444;
            color: white;
        }
        .button-delete:hover {
            background-color: #dc2626;
        }
        .button-icon {
            margin-right: 8px;
        }
        .expiry-info {
            background-color: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            color: #4b5563;
            font-size: 14px;
            margin: 12px 0;
            display: flex;
            align-items: center;
        }
        .expiry-icon {
            margin-right: 12px;
        }
        .section-title {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin: 32px 0 16px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="content">
        <div class="card">
            <div class="title">¡Enhorabuena!</div>

            <div class="subtitle">
                Se ha enviado con éxito tu archivo. Te avisaremos por mail cuando el destinatario lo haya descargado.
            </div>

            <div class="recipient">
                <strong>Enviado a:</strong>
                {{ $data['recipient_email'] }}
            </div>

            <div class="section-title">Contenido de la descarga</div>
            {{-- <div class="files-section">
                <ul class="files-list">
                    @foreach ($data['files'] as $file)
                        <li class="file-item">
                            <span class="file-icon">📄</span>
                            {{ $file->original_name }}
                        </li>
                    @endforeach
                </ul>
            </div> --}}

            @foreach ($data['files'] as $file)
                <div class="file-card">
                    <div class="file-name">
                        <span class="file-icon">📄</span>
                        {{ $file->original_name }}
                    </div>
                </div>
            @endforeach

            <div class="info-box">
                <strong>Tamaño de los archivos:</strong>
                {{ $data['total_size'] }}
            </div>

           <div class="expiry-info">
                <span class="expiry-icon">⏳</span>
                Este enlace es válido hasta el {{ $data['expiration_date'] }} (UTC +01:00)
            </div>

            <div class="buttons-container">
                <a href="{{ config('app.frontend_url') }}/send/{{ $data['download_link'] }}" 
                   class="button button-download">
                    <span class="button-icon">⬇️</span>
                    Descargar archivos
                </a>

                <a href="{{ config('app.frontend_url') }}/delete/{{ $data['delete_link'] }}" 
                   class="button button-delete">
                    <span class="button-icon">🗑️</span>
                    Eliminar la transferencia
                </a>
            </div>
        </div>
    </div>
</body>
</html>