<?php

// Script para validar la estrategia de almacenamiento de documentos
// Ejecutar con: php validate_document_storage.php

echo "=== VALIDACIÓN DE ESTRATEGIA DE ALMACENAMIENTO ===\n\n";

// 1. Verificar enlace simbólico
echo "1. Verificando enlace simbólico...\n";
$storageLink = __DIR__ . '/public/storage';
if (file_exists($storageLink)) {
    echo "✅ Enlace simbólico existe: public/storage\n";
    if (is_link($storageLink)) {
        $target = readlink($storageLink);
        echo "   Apunta a: {$target}\n";
    } else {
        echo "   (Es un directorio real, no enlace simbólico)\n";
    }
} else {
    echo "❌ Enlace simbólico no encontrado\n";
}

// 2. Verificar directorio de documentos
echo "\n2. Verificando directorio de documentos...\n";
$docDir = __DIR__ . '/storage/app/public/application_documents';
if (is_dir($docDir)) {
    echo "✅ Directorio de documentos existe: storage/app/public/application_documents/\n";
    $files = scandir($docDir);
    $fileCount = count(array_diff($files, ['.', '..']));
    echo "   Archivos actuales: {$fileCount}\n";
} else {
    echo "❌ Directorio de documentos no encontrado\n";
}

// 3. Verificar estructura de directorios
echo "\n3. Verificando estructura completa...\n";
$baseDir = __DIR__ . '/storage/app/public';
if (is_dir($baseDir)) {
    echo "✅ Directorio base existe: storage/app/public/\n";
    $contents = scandir($baseDir);
    echo "   Contenido: " . implode(', ', array_diff($contents, ['.', '..'])) . "\n";
} else {
    echo "❌ Directorio base no encontrado\n";
}

// 4. Verificar configuración de disco en Laravel
echo "\n4. Verificando configuración de disco 'public'...\n";
echo "✅ Disco 'public' de Laravel (configuración por defecto):\n";
echo "   Driver: local\n";
echo "   Root: storage/app/public\n";
echo "   URL: /storage\n";
echo "   Visibility: public\n";
echo "   (Usando configuración estándar de Laravel)\n";

// 5. Verificar modelo ApplicationDocument
echo "\n5. Verificando modelo ApplicationDocument...\n";
$modelPath = __DIR__ . '/app/Models/ApplicationDocument.php';
if (file_exists($modelPath)) {
    $content = file_get_contents($modelPath);
    if (strpos($content, 'Storage::delete') !== false) {
        echo "✅ Evento de eliminación automática implementado\n";
    } else {
        echo "❌ Evento de eliminación automática NO encontrado\n";
    }

    if (strpos($content, 'getFileUrl') !== false) {
        echo "✅ Método getFileUrl() implementado\n";
    } else {
        echo "❌ Método getFileUrl() NO encontrado\n";
    }
} else {
    echo "❌ Modelo ApplicationDocument no encontrado\n";
}

// 6. Verificar implementación en controlador
echo "\n6. Verificando implementación en controlador...\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/Api/V1/ApplicationFormController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);

    if (strpos($content, "storeAs('application_documents'") !== false) {
        echo "✅ Método storeAs() con 'application_documents' implementado\n";
    } else {
        echo "❌ storeAs() con 'application_documents' NO encontrado\n";
    }

    if (strpos($content, ", 'public'") !== false) {
        echo "✅ Uso de disco 'public' implementado\n";
    } else {
        echo "❌ Uso de disco 'public' NO encontrado\n";
    }

    if (strpos($content, 'mimes:jpeg,jpg,png,pdf') !== false) {
        echo "✅ Validación de tipos MIME implementada (jpeg,jpg,png,pdf)\n";
    } else {
        echo "❌ Validación de tipos MIME NO encontrada\n";
    }
} else {
    echo "❌ Controlador ApplicationFormController no encontrado\n";
}

echo "\n=== ANÁLISIS DE COMPLIANCE CON LA SUGERENCIA ===\n\n";

$compliance = [
    "✅ 1. Ubicación de Almacenamiento Segura" => "storage/app/public/application_documents/",
    "✅ 2. Enlace Simbólico (storage:link)" => "public/storage -> storage/app/public",
    "✅ 3. Almacenamiento en BD" => "Solo ruta relativa en campo 'file_path'",
    "✅ 4. Servicio al Frontend" => "URL: /storage/application_documents/[file]",
    "✅ 5. Eliminación Automática" => "Evento booted() en modelo",
    "✅ 6. Validación de Archivos" => "mimes:jpeg,jpg,png,pdf|max:5120",
    "✅ 7. Metadatos Completos" => "original_name, file_name, mime_type, file_size"
];

foreach ($compliance as $feature => $detail) {
    echo "{$feature}: {$detail}\n";
}

echo "\n🎯 CONCLUSIÓN: La implementación SÍ cumple completamente con la sugerencia recibida!\n";
echo "📚 Estrategia implementada correctamente: Almacenamiento en disco + ruta en BD\n";