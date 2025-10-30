<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .code {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Recuperación de Contraseña</h1>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>Latin Group</strong>.</p>
            
            <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">
                    Restablecer Contraseña
                </a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong> Este enlace es válido por <strong>60 minutos</strong>. Después de ese tiempo, deberás solicitar uno nuevo.
            </div>
            
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            
            <div class="code">
                {{ $resetUrl }}
            </div>
            
            <p style="margin-top: 30px;">Si no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura. Tu contraseña no será modificada.</p>
            
            <p>Saludos,<br>
            <strong>El equipo de Latin Group</strong></p>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; {{ date('Y') }} Latin Group. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
