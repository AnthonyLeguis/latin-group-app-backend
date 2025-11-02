<?php

namespace App\Services;

use App\Models\ApplicationForm;
use Illuminate\Support\Facades\Storage;
use TCPDF;
use TCPDF_FONTS;

class PdfGeneratorService
{
    /**
     * Generar PDF de confirmación para una planilla
     */
    public function generateConfirmationPdf(ApplicationForm $form): string
    {
        // Crear directorio si no existe
        $clientDir = "pdfs/{$form->id}";
        if (!Storage::disk('local')->exists($clientDir)) {
            Storage::disk('local')->makeDirectory($clientDir);
        }

        // Nombre del archivo
        $filename = "{$form->id}.pdf";
        $filepath = "{$clientDir}/{$filename}";

        // Inicializar TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
    // Configuración del PDF
    $pdf->SetCreator('Latin Group Insurance');
        $pdf->SetAuthor('Latin Group Insurance');
        $pdf->SetTitle('Confirmación de Planilla');
        $pdf->SetSubject('Confirmación de Planilla');
        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $this->applyArialFont($pdf, 12);

        // PÁGINA 1: Documento de Confirmación
        $pdf->AddPage();

        // Header con logo y fecha
        $this->addHeaderWithLogo($pdf);
        $pdf->setCellHeightRatio(1.4); // Interlineado moderado para la hoja 1
        $this->addConfirmationPage($pdf, $form);

        // PÁGINA 2: Carta de Declaración de Ingresos
        $pdf->AddPage();
        $this->addHeaderWithLogo($pdf); // Mismo encabezado que página 1
        $pdf->setCellHeightRatio(1.25); // Interlineado estándar en la hoja 2
        $this->addIncomeDeclarationPage($pdf, $form);

        // Guardar PDF
        $fullPath = storage_path("app/{$filepath}");
        $pdf->Output($fullPath, 'F');

        // Retornar la ruta relativa al disco 'local' (storage/app/)
        return $filepath;
    }

    /**
     * Generar PDF completo con todos los datos de la planilla
     */
    public function generateApplicationPdf(ApplicationForm $form): string
    {
        // Crear directorio si no existe
        $clientDir = "pdfs/{$form->id}";
        if (!Storage::disk('local')->exists($clientDir)) {
            Storage::disk('local')->makeDirectory($clientDir);
        }

        // Nombre del archivo
        $filename = "application_{$form->id}.pdf";
        $filepath = "{$clientDir}/{$filename}";

        // Inicializar TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configuración del PDF
        $pdf->SetCreator('Latin Group Insurance');
        $pdf->SetAuthor('Latin Group Insurance');
        $pdf->SetTitle('Planilla de Aplicación');
        $pdf->SetSubject('Datos de Aplicación de Cliente');
        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $this->applyArialFont($pdf, 12);

        // PÁGINA 1: Datos principales
        $pdf->AddPage();
        $this->addHeaderWithLogo($pdf);
        $pdf->setCellHeightRatio(1.3);
        $this->addApplicationDataPage($pdf, $form);

        // Guardar PDF
        $fullPath = storage_path("app/{$filepath}");
        $pdf->Output($fullPath, 'F');

        // Retornar la ruta relativa al disco 'local' (storage/app/)
        return $filepath;
    }

    /**
     * Aplicar fuente Arial (o fallback a Helvetica si no está disponible)
     */
    private function applyArialFont(TCPDF $pdf, int $size = 12, string $style = ''): void
    {
        static $fontCache = [];

        $styleKey = $style !== '' ? $style : 'regular';

        if (!array_key_exists($styleKey, $fontCache)) {
            $fontFile = match ($style) {
                'B' => 'arialbd.ttf',
                'I' => 'ariali.ttf',
                'BI', 'IB' => 'arialbi.ttf',
                default => 'arial.ttf',
            };

            $paths = [
                resource_path('fonts/' . $fontFile),
                public_path('fonts/' . $fontFile),
                storage_path('fonts/' . $fontFile),
                base_path('fonts/' . $fontFile),
            ];

            if (PHP_OS_FAMILY === 'Windows') {
                $windowsDir = getenv('WINDIR');
                if ($windowsDir) {
                    $paths[] = $windowsDir . DIRECTORY_SEPARATOR . 'Fonts' . DIRECTORY_SEPARATOR . $fontFile;
                }
            }

            $fontPath = null;
            foreach ($paths as $path) {
                if ($path && file_exists($path)) {
                    $fontPath = $path;
                    break;
                }
            }

            if ($fontPath) {
                $fontName = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
                $fontCache[$styleKey] = $fontName ?: false;
            } else {
                $fontCache[$styleKey] = false;
            }
        }

        if ($fontCache[$styleKey]) {
            $pdf->SetFont($fontCache[$styleKey], '', $size);
        } else {
            // Fallback a Helvetica si Arial no está disponible
            $pdf->SetFont('helvetica', $style, $size);
        }
    }

    /**
     * Agregar encabezado con logo (más grande, sin texto adicional)
     */
    private function addHeaderWithLogo(TCPDF $pdf): void
    {
        // Logo más ancho horizontalmente (50mm ancho x 30mm alto)
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 10, 60, 30);
        }

        // Fecha de registro (lado derecho, centrada verticalmente)
        $pdf->SetXY(140, 18);
        $this->applyArialFont($pdf, 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, 'Fecha de registro', 0, 1, 'R');
        $pdf->SetXY(140, 23);
        $this->applyArialFont($pdf, 12, 'B');
        $pdf->Cell(0, 5, now()->format('d/m/Y'), 0, 1, 'R');

        // Línea divisoria roja
        $pdf->SetY(45);
        $pdf->SetDrawColor(220, 20, 60);
        $pdf->SetLineWidth(2);
        $pdf->Line(15, 45, 195, 45);
        $pdf->SetY(50);
    }

    /**
     * Agregar contenido de la página 1: Confirmación
     */
    private function addConfirmationPage(TCPDF $pdf, ApplicationForm $form): void
    {
    $this->applyArialFont($pdf, 12);

    // Saludo
    $this->applyArialFont($pdf, 12, 'B');
        $pdf->SetY($pdf->GetY() + 3);

        // Datos del cliente
    $this->applyArialFont($pdf, 12);
        $this->addClientInfoSection($pdf, $form);

        // Texto de confirmación (alineación izquierda para evitar espacios grandes)
        $pdf->SetY($pdf->GetY() + 5);
        $this->applyArialFont($pdf, 12);
        
        // Obtener nombre del agente de la relación (siempre actualizado)
        $agentName = $form->agent ? $form->agent->name : $form->agent_name;
        
        $texto = "Le enviamos este mensaje en nombre de Latin Group Insurance para confirmar su autorización al Asesor {$agentName} a trabajar con el NPN 19903181 / 19606203. De {$form->client->name} para acceder y utilizar la información confidencial que usted ha proporcionado para el período 2026. Cabe destacar que la explicación de su plan ya ha sido completada y verificada tanto por usted como por su asesor.";
        $pdf->MultiCell(0, 5, $texto, 0, 'L');

        // Plan (sin justificación)
        if ($form->insurance_plan) {
            $pdf->SetY($pdf->GetY() + 3);
            $pdf->MultiCell(0, 5, "Su plan para el año 2026 es: {$form->insurance_plan}.", 0, 'L');
        }

        // Razones de uso
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->MultiCell(0, 5, "La información recibida por usted será exclusivamente usada por una o más de las siguientes razones:", 0, 'L');

        // Lista de razones
        $reasons = [
            'Consultar una aplicación existente en el Mercado de Seguros.',
            'Completar una solicitud para determinar su elegibilidad e inscripción en un Plan de Salud Calificado del Mercado.',
            'Brindar asistencia continua en la administración de su cuenta y proceso de inscripción, según sea necesario.',
            'Responder a consultas del Mercado relacionadas con su aplicación.'
        ];

        foreach ($reasons as $reason) {
            $pdf->SetY($pdf->GetY() + 2);
            $pdf->MultiCell(0, 4, "• " . $reason, 0, 'L');
        }

        // Cláusula de confidencialidad (alineación izquierda)
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->MultiCell(0, 5, "Entiendo que los agentes mencionados no utilizarán ni compartirán mi información personal para fines distintos a los especificados anteriormente. Asimismo, se comprometen a mantener la confidencialidad y seguridad de mi información en todo momento.", 0, 'L');

        // Footer centrado en la parte inferior de la página
        $this->addPageFooter($pdf, $form->client->name);
    }

    /**
     * Agregar sección de datos del cliente
     */
    private function addClientInfoSection(TCPDF $pdf, ApplicationForm $form): void
    {
        $y = $pdf->GetY();
        
        $clientData = [
            'Nombre y apellido:' => $form->client->name,
            'Fecha de Nacimiento:' => $form->dob?->format('d/m/Y'),
            'Correo Electrónico:' => $form->email,
            'Número de Teléfono:' => $form->phone,
            'Salario Mensual:' => $form->wages ?? 'N/A',
            'Plan 2026:' => $form->insurance_plan ?? 'N/A',
            'Estado:' => $form->state ?? 'N/A'
        ];

        $pdf->SetFillColor(240, 240, 240);
        $this->applyArialFont($pdf, 12);

        foreach ($clientData as $label => $value) {
            $pdf->SetX(20);
            $this->applyArialFont($pdf, 12, 'B');
            $pdf->Cell(50, 4, $label, 0, 0, 'L', true);
            
            $this->applyArialFont($pdf, 12);
            $pdf->MultiCell(0, 4, (string)$value, 0, 'L', true);
        }

        $pdf->SetFillColor(255, 255, 255);
    }

    /**
     * Agregar pie de página centrado en la parte inferior
     */
    private function addPageFooter(TCPDF $pdf, string $clientName): void
    {
        // Posicionar el footer a 35mm del borde inferior (más abajo)
        $pdf->SetY(-45);
        
        // Firma digital del cliente
        $this->applyArialFont($pdf, 12, 'I');
        $pdf->Cell(0, 5, 'Este documento ha sido autorizado por: ' . $clientName, 0, 1, 'C');
        
        $pdf->Ln(7); 
        
        $this->applyArialFont($pdf, 12, 'B');
        $pdf->Cell(0, 5, 'Atentamente,', 0, 1, 'C');
        
        $this->applyArialFont($pdf, 12, 'B');
        $pdf->Cell(0, 5, 'Latin Group Insurance', 0, 1, 'C');
        
        $this->applyArialFont($pdf, 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'IP del usuario: ' . $this->getClientIp(), 0, 1, 'C');
    }    /**
     * Agregar contenido de la página 2: Declaración de Ingresos
     */
    private function addIncomeDeclarationPage(TCPDF $pdf, ApplicationForm $form): void
    {
    $this->applyArialFont($pdf, 14, 'B');
        $pdf->Cell(0, 10, 'Carta de declaración de ingresos', 0, 1, 'C');
        $pdf->SetY($pdf->GetY() + 5);

    $this->applyArialFont($pdf, 12);
        
        // Párrafo inicial
        $now = now();
        $monthText = $this->getMonthName($now->month);
        $dayText = "del mes 10 al 30 del año " . $now->year;
        
        $pdf->MultiCell(0, 5, 
            "En {$dayText}\nNombre: {$form->client->name}\nDirección: {$form->state}",
            0, 'L'
        );

        $pdf->SetY($pdf->GetY() + 5);
        $this->applyArialFont($pdf, 12, 'B');
        $pdf->Cell(0, 5, 'HEALTH INSURANCE MARKETPLACE DEPARTMENT OF HEALTH AND HUMAN SERVICES', 0, 1, 'C');

        // Calcular ingresos mensuales y anuales basados en la frecuencia
        $monthlySalary = $this->calculateMonthlySalary($form->wages, $form->wages_frequency);
        $annualSalary = $this->calculateAnnualSalary($form->wages, $form->wages_frequency);

        $pdf->SetY($pdf->GetY() + 5);
        $this->applyArialFont($pdf, 12);
        $pdf->MultiCell(0, 5,
            "A quien pueda interesar:\n\n" .
            "Yo, {$form->client->name}, fecha de nacimiento: {$form->dob?->format('d/m/Y')}\n" .
            "Con numero de social security: 111\n\n" .
            "Hago constar por medio de la presente que trabajo por cuenta propia y me comprometo y es mi voluntad, declarar alrededor de " . number_format($monthlySalary, 2) . " al mes y " . number_format($annualSalary, 2) . " ingresos anuales para el 2026.",
            0, 'L'
        );

    }

    /**
     * Agregar página con todos los datos de la aplicación
     */
    private function addApplicationDataPage(TCPDF $pdf, ApplicationForm $form): void
    {
        $this->applyArialFont($pdf, 14, 'B');
        $pdf->Cell(0, 10, 'Planilla de Aplicación - Datos Completos', 0, 1, 'C');
        $pdf->SetY($pdf->GetY() + 3);

        // Información del Cliente y Agente
        $this->addSectionTitle($pdf, 'Información del Cliente');
        $this->addFieldRow($pdf, 'Nombre del Cliente', $form->client->name ?? 'N/A');
        $this->addFieldRow($pdf, 'Agente Asignado', $form->agent_name ?? 'N/A');
        $this->addFieldRow($pdf, 'Estado', $form->status ?? 'N/A');
        $this->addFieldRow($pdf, 'Confirmado', $form->confirmed ? 'Sí' : 'No');

        $pdf->Ln(3);

        // Datos del Aplicante
        $this->addSectionTitle($pdf, 'Datos del Aplicante');
        $this->addFieldRow($pdf, 'Nombre Completo', $form->applicant_name ?? 'N/A');
        $this->addFieldRow($pdf, 'Fecha de Nacimiento', $form->dob?->format('d/m/Y') ?? 'N/A');
        $this->addFieldRow($pdf, 'Género', $form->gender === 'M' ? 'Masculino' : ($form->gender === 'F' ? 'Femenino' : 'N/A'));
        $this->addFieldRow($pdf, 'Estatus Legal', $form->legal_status ?? 'N/A');
        $this->addFieldRow($pdf, 'Número de Documento', $form->document_number ?? 'N/A');
        $this->addFieldRow($pdf, 'SSN', $form->ssn ?? 'N/A');
        $this->addFieldRow($pdf, 'Dirección', $form->address ?? 'N/A');
        $this->addFieldRow($pdf, 'Apt/Unidad', $form->unit_apt ?? 'N/A');
        $this->addFieldRow($pdf, 'Ciudad', $form->city ?? 'N/A');
        $this->addFieldRow($pdf, 'Estado', $form->state ?? 'N/A');
        $this->addFieldRow($pdf, 'Código Postal', $form->zip_code ?? 'N/A');
        $this->addFieldRow($pdf, 'Teléfono', $form->phone ?? 'N/A');
        $this->addFieldRow($pdf, 'Teléfono 2', $form->phone2 ?? 'N/A');
        $this->addFieldRow($pdf, 'Email', $form->email ?? 'N/A');

        // Nueva página si es necesario
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
            $this->addHeaderWithLogo($pdf);
        } else {
            $pdf->Ln(3);
        }

        // Información de Empleo
        $this->addSectionTitle($pdf, 'Información de Empleo');
        $this->addFieldRow($pdf, 'Tipo de Empleo', $form->employment_type ?? 'N/A');
        $this->addFieldRow($pdf, 'Nombre de la Empresa', $form->employment_company_name ?? 'N/A');
        $this->addFieldRow($pdf, 'Teléfono del Trabajo', $form->work_phone ?? 'N/A');
        $this->addFieldRow($pdf, 'Salario', $form->wages ? '$' . number_format($form->wages, 2) : 'N/A');
        $this->addFieldRow($pdf, 'Frecuencia de Pago', $form->wages_frequency ?? 'N/A');

        $pdf->Ln(3);

        // Información de Seguro
        $this->addSectionTitle($pdf, 'Información de Seguro');
        $this->addFieldRow($pdf, 'Compañía de Seguro', $form->insurance_company ?? 'N/A');
        $this->addFieldRow($pdf, 'Plan de Seguro', $form->insurance_plan ?? 'N/A');
        $this->addFieldRow($pdf, 'Subsidio', $form->subsidy ? '$' . number_format($form->subsidy, 2) : 'N/A');
        $this->addFieldRow($pdf, 'Costo Final', $form->final_cost ? '$' . number_format($form->final_cost, 2) : 'N/A');

        $pdf->Ln(3);

        // Información de Póliza
        $this->addSectionTitle($pdf, 'Información de Póliza Dental');
        $this->addFieldRow($pdf, 'Número de Póliza', $form->poliza_number ?? 'N/A');
        $this->addFieldRow($pdf, 'Categoría', $form->poliza_category ?? 'N/A');
        $this->addFieldRow($pdf, 'Clave', $form->poliza_key ?? 'N/A');
        $this->addFieldRow($pdf, 'Monto Prima Dental', $form->poliza_amount ? '$' . number_format($form->poliza_amount, 2) : 'N/A');
        $this->addFieldRow($pdf, 'Día de Pago', $form->poliza_payment_day ?? 'N/A');
        $this->addFieldRow($pdf, 'Beneficiario', $form->poliza_beneficiary ?? 'N/A');

        // Personas adicionales (solo si existen)
        for ($i = 1; $i <= 6; $i++) {
            $personName = $form->{"person{$i}_name"};
            if ($personName) {
                // Nueva página si es necesario
                if ($pdf->GetY() > 220) {
                    $pdf->AddPage();
                    $this->addHeaderWithLogo($pdf);
                } else {
                    $pdf->Ln(3);
                }

                $this->addSectionTitle($pdf, "Persona Adicional #{$i}");
                $this->addFieldRow($pdf, 'Nombre', $personName);
                $this->addFieldRow($pdf, 'Relación', $form->{"person{$i}_relation"} ?? 'N/A');
                $this->addFieldRow($pdf, 'Es Aplicante', $form->{"person{$i}_is_applicant"} ? 'Sí' : 'No');
                $this->addFieldRow($pdf, 'Estatus Legal', $form->{"person{$i}_legal_status"} ?? 'N/A');
                $this->addFieldRow($pdf, 'Número de Documento', $form->{"person{$i}_document_number"} ?? 'N/A');
                $this->addFieldRow($pdf, 'Fecha de Nacimiento', $form->{"person{$i}_dob"} ? \Carbon\Carbon::parse($form->{"person{$i}_dob"})->format('d/m/Y') : 'N/A');
                $this->addFieldRow($pdf, 'Empresa', $form->{"person{$i}_company_name"} ?? 'N/A');
                $this->addFieldRow($pdf, 'SSN', $form->{"person{$i}_ssn"} ?? 'N/A');
                $this->addFieldRow($pdf, 'Género', $form->{"person{$i}_gender"} === 'M' ? 'Masculino' : ($form->{"person{$i}_gender"} === 'F' ? 'Femenino' : 'N/A'));
                $this->addFieldRow($pdf, 'Salario', $form->{"person{$i}_wages"} ? '$' . number_format($form->{"person{$i}_wages"}, 2) : 'N/A');
                $this->addFieldRow($pdf, 'Frecuencia', $form->{"person{$i}_frequency"} ?? 'N/A');
            }
        }

        // Verificar si hay suficiente espacio para método de pago (necesita aprox 50mm)
        // Si no hay espacio, agregar nueva página
        if ($pdf->GetY() > 220) {
            $pdf->AddPage();
            $this->addHeaderWithLogo($pdf);
        } else {
            $pdf->Ln(3);
        }

        // Método de Pago
        $this->addSectionTitle($pdf, 'Método de Pago');
        $this->addFieldRow($pdf, 'Tipo de Tarjeta', $form->card_type ?? 'N/A');
        $this->addFieldRow($pdf, 'Número de Tarjeta', $form->card_number ? '****' . substr($form->card_number, -4) : 'N/A');
        $this->addFieldRow($pdf, 'Fecha de Expiración', $form->card_expiration ?? 'N/A');
        $this->addFieldRow($pdf, 'Banco', $form->bank_name ?? 'N/A');
        $this->addFieldRow($pdf, 'Routing Number', $form->bank_routing ?? 'N/A');
        $this->addFieldRow($pdf, 'Número de Cuenta', $form->bank_account ? '****' . substr($form->bank_account, -4) : 'N/A');

        $pdf->Ln(5);

        // Información adicional
        $this->addSectionTitle($pdf, 'Información Adicional');
        $this->addFieldRow($pdf, 'Fecha de Creación', $form->created_at->format('d/m/Y H:i'));
        $this->addFieldRow($pdf, 'Última Actualización', $form->updated_at->format('d/m/Y H:i'));
    }

    /**
     * Agregar título de sección
     */
    private function addSectionTitle(TCPDF $pdf, string $title): void
    {
        $this->applyArialFont($pdf, 12, 'B');
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(0, 7, $title, 0, 1, 'L', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Ln(2);
    }

    /**
     * Agregar fila de campo con label y valor
     */
    private function addFieldRow(TCPDF $pdf, string $label, string $value): void
    {
        $this->applyArialFont($pdf, 10, 'B');
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell(60, 5, $label . ':', 0, 0, 'L');
        
        $this->applyArialFont($pdf, 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 5, $value, 0, 'L');
    }

    /**
     * Calcular salario mensual basado en la frecuencia de pago
     */
    private function calculateMonthlySalary(?float $wages, ?string $frequency): float
    {
        if (!$wages || !$frequency) {
            return 0;
        }

        return match(strtolower(trim($frequency))) {
            'semanal' => $wages * 4,
            'quincenal' => $wages * 2,
            'mensual' => $wages,
            'anual' => $wages / 12,
            default => $wages
        };
    }

    /**
     * Calcular salario anual basado en la frecuencia de pago
     */
    private function calculateAnnualSalary(?float $wages, ?string $frequency): float
    {
        if (!$wages || !$frequency) {
            return 0;
        }

        return match(strtolower(trim($frequency))) {
            'semanal' => $wages * 52,
            'quincenal' => $wages * 26,
            'mensual' => $wages * 12,
            'anual' => $wages,
            default => $wages * 12
        };
    }

    /**
     * Obtener nombre del mes
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        return $months[$month] ?? '';
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): string
    {
        return request()->ip() ?? '0.0.0.0';
    }
}
