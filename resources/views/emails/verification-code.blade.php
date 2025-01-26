<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
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
        }
        .card {
            width: 600px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            background-color: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 16px;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            color: #10b981;
            letter-spacing: 4px;
            margin: 24px 0;
            cursor: pointer;
        }
        .note {
            color: #6b7280;
            font-size: 16px;
        }
    </style>
    <script>
        function copyCode() {
            var codeElement = document.querySelector('.code');
            navigator.clipboard.writeText(codeElement.textContent);
            alert('Code copied to clipboard!');
        }
    </script>
</head>
<body>
    <div class="content">
       <div class="card">
            <div class="title"> 
                Tu código de verificación
            </div>
            <p class="note">
                Este es el código de un solo uso que debes introducir para verificar tu dirección de correo electrónico.
            </p>
            <div class="code" onclick="copyCode()">
                {{ $code }}
            </div>
            <p class="note">
                Si no has hecho esta solicitud, ignora este correo electrónico.
            </p>
            <p class="note">El equipo Tofi</p>
       </div>
    </div>
</body>
</html>