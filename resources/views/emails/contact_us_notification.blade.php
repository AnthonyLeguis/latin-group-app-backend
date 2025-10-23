<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva petición desde contáctanos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 200px;
            background: white;
            height: auto;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #b91c1c;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .alert-box p {
            margin: 0;
            color: #991b1b;
            font-weight: 600;
            font-size: 16px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .info-table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 15px 0;
            vertical-align: top;
        }
        .info-table td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table td:last-child {
            color: #1f2937;
            font-size: 15px;
        }
        .service-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin: 4px 4px 4px 0;
        }
        .badge-yes {
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-no {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .footer {
            background: #f9fafb;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 13px;
        }
        .footer .timestamp {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <img src="https://i.imgur.com/45lBgFR.png" alt="Latin Group Insurance">
            <h1>Nueva Solicitud de Contacto</h1>
        </div>
        
        <div class="content">
            <div class="alert-box">
                <p>📩 Has recibido una nueva solicitud desde el formulario de contacto</p>
            </div>

            <table class="info-table">
                <tr>
                    <td>Nombre Completo</td>
                    <td><strong>{{ $contact->full_name }}</strong></td>
                </tr>
                <tr>
                    <td>Correo Electrónico</td>
                    <td><a href="mailto:{{ $contact->email }}" style="color: #b91c1c; text-decoration: none;">{{ $contact->email }}</a></td>
                </tr>
                <tr>
                    <td>Teléfono</td>
                    <td><a href="tel:{{ $contact->phone }}" style="color: #b91c1c; text-decoration: none;">{{ $contact->phone }}</a></td>
                </tr>
                <tr>
                    <td>Código Postal</td>
                    <td>{{ $contact->zip_code }}</td>
                </tr>
                <tr>
                    <td>Servicios de Interés</td>
                    <td>
                        @if($contact->service_medical)
                            <span class="service-badge">🏥 Servicio Médico</span>
                        @endif
                        @if($contact->service_dental)
                            <span class="service-badge">🦷 Servicio Dental</span>
                        @endif
                        @if($contact->service_accidents)
                            <span class="service-badge">🚑 Póliza de Accidentes</span>
                        @endif
                        @if($contact->service_life)
                            <span class="service-badge">💼 Seguro de Vida</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Consentimiento SMS</td>
                    <td>
                        @if($contact->accept_sms)
                            <span class="badge-yes">✓ Sí acepta</span>
                        @else
                            <span class="badge-no">✗ No acepta</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p><strong>Latin Group Insurance</strong></p>
            <p>Este correo fue generado automáticamente desde el formulario de contacto</p>
            <p class="timestamp">📅 Recibido el {{ $contact->created_at->format('d/m/Y') }} a las {{ $contact->created_at->format('H:i') }}</p>
        </div>
    </div>
</body>
</html>
