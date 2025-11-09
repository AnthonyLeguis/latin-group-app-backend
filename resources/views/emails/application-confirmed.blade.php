<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla Confirmada</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px 20px;
            color: #333333;
            line-height: 1.6;
        }
        .email-body h2 {
            color: #166534;
            margin-top: 0;
            font-size: 20px;
        }
        .info-box {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 8px 0;
        }
        .info-box strong {
            color: #1f2937;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #16a34a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #15803d;
        }
        .attachments {
            background-color: #ecfdf5;
            border: 1px solid #bbf7d0;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .attachments h3 {
            margin-top: 0;
            color: #166534;
            font-size: 16px;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="icon">🎉</div>
            <h1>¡El cliente confirmó su planilla!</h1>
        </div>
        
        <div class="email-body">
            <h2>Hola, {{ $agentName }}</h2>
            
            <p>El cliente ha revisado y aceptado la planilla de aplicación enviada.</p>
            
            <div class="info-box">
                <p><strong>Cliente:</strong> {{ $clientName }}</p>
                <p><strong>ID de Planilla:</strong> #{{ $formId }}</p>
                <p><strong>Fecha de Confirmación:</strong> {{ $confirmedAt ?? 'Pendiente' }}</p>
            </div>

            <div class="attachments">
                <h3>📎 Documento Adjuntado</h3>
                <p>Se adjunta el PDF de autorización firmado por el cliente para tu archivo.</p>
            </div>

            <p>Puedes revisar la planilla confirmada ingresando al sistema y buscando el ID indicado:</p>
            <a href="{{ $applicationLink }}" class="btn">Ver planilla en el sistema</a>
        </div>
        
        <div class="email-footer">
            <p><strong>Latin Group Application System</strong></p>
            <p>Este es un correo automático, por favor no responder.</p>
            <p style="margin-top: 10px;">&copy; {{ date('Y') }} Latin Group. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
