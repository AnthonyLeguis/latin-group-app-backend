<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Regenerar PDF para la planilla 19
$form = App\Models\ApplicationForm::find(19);

if (!$form) {
    echo "❌ Planilla 19 no encontrada\n";
    exit(1);
}

echo "📋 Planilla encontrada: {$form->id}\n";
echo "👤 Cliente: {$form->client->name}\n";

try {
    $pdfService = app(App\Services\PdfGeneratorService::class);
    $path = $pdfService->generateConfirmationPdf($form);
    
    echo "✅ PDF generado en: {$path}\n";
    
    $form->update(['pdf_path' => $path]);
    
    echo "✅ Campo pdf_path actualizado en la base de datos\n";
    echo "📁 Ruta completa: " . storage_path("app/{$path}") . "\n";
    
    if (file_exists(storage_path("app/{$path}"))) {
        $size = filesize(storage_path("app/{$path}"));
        echo "✅ Archivo existe, tamaño: " . round($size / 1024, 2) . " KB\n";
    } else {
        echo "❌ El archivo no existe en el sistema de archivos\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Proceso completado exitosamente\n";
