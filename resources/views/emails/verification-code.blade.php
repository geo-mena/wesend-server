<!DOCTYPE html>
<html>
<head>
    <style>
        .container {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
            letter-spacing: 4px;
            margin: 20px 0;
        }
        .note {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Código de verificación</h2>
        <p>Tu código de verificación es:</p>
        <div class="code">{{ $code }}</div>
        <p class="note">Este código expirará en 15 minutos.</p>
    </div>
</body>
</html>