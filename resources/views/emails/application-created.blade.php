<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Planilla Creada</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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
            color: #dc2626;
            margin-top: 0;
            font-size: 20px;
        }
        .info-box {
            background-color: #f9fafb;
            border-left: 4px solid #dc2626;
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
            background-color: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #991b1b;
        }
        .attachments {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .attachments h3 {
            margin-top: 0;
            color: #dc2626;
            font-size: 16px;
        }
        .attachments ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .attachments li {
            margin: 5px 0;
            color: #374151;
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
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="icon">✅</div>
            <h1>Nueva Planilla de Aplicación Creada</h1>
        </div>
        
        <div class="email-body">
            <h2>Hola, {{ $agentName }}</h2>
            
            <p>Se ha creado una nueva planilla de aplicación y ha sido asignada a tu cuenta.</p>
            
            <div class="info-box">
                <p><strong>Cliente:</strong> {{ $clientName }}</p>
                <p><strong>Aplicante:</strong> {{ $applicantName }}</p>
                <p><strong>ID de Planilla:</strong> #{{ $formId }}</p>
                <p><strong>Fecha de Creación:</strong> {{ $createdAt }}</p>
            </div>

            <div class="attachments">
                <h3>📎 Documento Adjuntado</h3>
                <p>Este correo incluye el PDF completo de la planilla para tu revisión.</p>
                <p style="margin-top: 10px; color: #6b7280; font-size: 14px;">
                    El PDF de autorización firmado se enviará automáticamente cuando el cliente confirme la planilla desde su enlace.
                </p>
            </div>

            <div class="divider"></div>

            <p><strong>Link de Confirmación para el Cliente:</strong></p>
            <p>Comparte el siguiente enlace con el cliente para que revise y acepte la planilla:</p>
            
            <a href="{{ $confirmationLink }}" class="btn">Ver Link de Confirmación</a>
            
            <p style="margin-top: 15px; font-size: 12px; color: #6b7280;">
                O copia y pega este enlace en tu navegador:<br>
                <code style="background-color: #f3f4f6; padding: 8px; display: inline-block; border-radius: 4px; margin-top: 5px;">{{ $confirmationLink }}</code>
            </p>

            <div class="divider"></div>

            <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">
                <strong>Nota:</strong> El link de confirmación expira en 3 días. Recibirás una copia de la autorización firmada en cuanto el cliente complete la confirmación.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>Latin Group Application System</strong></p>
            <p>Este es un correo automático, por favor no responder.</p>
            <p style="margin-top: 10px;">&copy; {{ date('Y') }} Latin Group. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
