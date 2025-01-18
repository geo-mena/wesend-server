<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Send - Sistema de Envío de Archivos</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                margin: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #f0f2f5 0%, #e2e8f0 100%);
            }
            .card {
                background: white;
                padding: 2.5rem;
                border-radius: 20px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                text-align: center;
                transition: all 0.3s ease;
                width: 400px;
            }
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            }
            .logo-container {
                margin-bottom: 1.5rem;
            }
            .logo {
                font-size: 3rem;
                color: #4a90e2;
                margin-bottom: 1rem;
            }
            h1 {
                color: #2d3748;
                margin: 0;
                font-weight: 600;
                font-size: 1.8rem;
                letter-spacing: 0.5px;
            }
            .divider {
                height: 3px;
                background: linear-gradient(90deg, #4a90e2 0%, #63b3ed 100%);
                margin: 1.5rem 0;
                border-radius: 2px;
            }
            .status {
                background: #e6f3ff;
                padding: 0.8rem;
                border-radius: 10px;
                margin: 1rem 0;
            }
            .status-text {
                color: #4a90e2;
                font-weight: 500;
            }
            .info-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-top: 1.5rem;
            }
            .info-box {
                background: #f8fafc;
                padding: 1rem;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }
            .info-label {
                color: #718096;
                font-size: 0.85rem;
                margin-bottom: 0.3rem;
            }
            .info-value {
                color: #2d3748;
                font-weight: 600;
                font-size: 1.1rem;
            }
            .footer {
                margin-top: 1.5rem;
                color: #718096;
                font-size: 0.9rem;
            }
            .button-container {
                margin-top: 2rem;
            }
            .btn {
                display: inline-block;
                padding: 0.8rem 1.5rem;
                margin: 0.5rem;
                font-size: 1rem;
                font-weight: 600;
                color: white;
                background: #4a90e2;
                border: none;
                border-radius: 5px;
                text-decoration: none;
                cursor: pointer;
                transition: background 0.3s ease;
            }
            .btn:hover {
                background: #357abd;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo-container">
                <i class="fas fa-link logo"></i>
            </div>
            <h1>Sistema de Envío de Archivos</h1>
            <div class="divider"></div>
            <div class="status">
                <span class="status-text">
                    <i class="fas fa-circle" style="color: #4CAF50;"></i>
                    Sistema Activo
                </span>
            </div>
            <div class="info-container">
                <div class="info-box">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                        Conectado
                    </div>
                </div>
                <div class="info-box">
                    <div class="info-label">Última Actualización</div>
                    <div class="info-value">
                        <i class="fas fa-clock"></i>
                        Ahora
                    </div>
                </div>
            </div>

            <!-- Botones adicionales -->
            <div class="button-container">
                <a href="https://send.tofi.pro" class="btn">Enviar Archivos</a>
                {{-- <a href="/storage" class="btn">Repositorio Archivos</a> --}}
            </div>
            <div class="footer">
                <i class="fas fa-shield-alt"></i>
                Conexión segura establecida
            </div>
        </div>
    </body>
</html>
