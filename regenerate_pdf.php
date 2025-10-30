<?php
// Script para regenerar el PDF de la planilla #15

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$form = \App\Models\ApplicationForm::find(15);

if (!$form) {
    die("❌ No se encontró la planilla #15\n");
}

echo "📋 Regenerando PDF para planilla #15\n";
echo "================================\n\n";

try {
    // Eliminar el PDF anterior si existe
    if ($form->pdf_path && \Storage::disk('local')->exists($form->pdf_path)) {
        \Storage::disk('local')->delete($form->pdf_path);
        echo "🗑️ PDF anterior eliminado\n";
    }
    
    // Generar nuevo PDF
    $pdfGenerator = app(\App\Services\PdfGeneratorService::class);
    $pdfPath = $pdfGenerator->generateConfirmationPdf($form);
    
    // Actualizar la ruta en la base de datos
    $form->update(['pdf_path' => $pdfPath]);
    
    $fullPath = storage_path("app/{$pdfPath}");
    $size = filesize($fullPath);
    $sizeKB = round($size / 1024, 2);
    
    echo "✅ PDF regenerado exitosamente\n";
    echo "📂 Ruta: {$pdfPath}\n";
    echo "📊 Tamaño: {$sizeKB} KB\n";
    echo "✅ Listo para descargar desde el dashboard\n";
    
} catch (\Exception $e) {
    echo "❌ Error al regenerar PDF:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
