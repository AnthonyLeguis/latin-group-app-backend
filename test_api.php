<?php

// Script para probar las APIs del sistema LatinGroup App
// Ejecutar con: php test_api.php

function makeRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "=== PRUEBAS DEL SISTEMA LATIN GROUP APP ===\n\n";

$timestamp = time();

// 1. Probar registro público (debe fallar)
echo "1. Probando registro público (debe fallar)...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'type' => 'client'
]);

if ($result['code'] == 401 || $result['code'] == 500) {
    echo "✅ Registro público correctamente bloqueado\n";
} else {
    echo "❌ Registro público debería estar bloqueado\n";
}
echo "Código: {$result['code']}\n";
echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// 2. Probar login con admin
echo "2. Probando login con admin...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/login', [
    'email' => 'admin@example.com',
    'password' => 'password123'
]);

$adminToken = null;
if ($result['code'] == 200) {
    $adminToken = $result['response']['token'];
    echo "✅ Login exitoso\n";
    echo "Token: " . substr($adminToken, 0, 20) . "...\n";
} else {
    echo "❌ Login falló\n";
}
echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// 3. Probar login con agent
echo "3. Probando login con agent...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/login', [
    'email' => 'agent@example.com',
    'password' => 'password123'
]);

$agentToken = null;
if ($result['code'] == 200) {
    $agentToken = $result['response']['token'];
    echo "✅ Login exitoso\n";
} else {
    echo "❌ Login falló\n";
}
echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// 4. Probar login con client
echo "4. Probando login con client...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/login', [
    'email' => 'client@example.com',
    'password' => 'password123'
]);

$clientToken = null;
if ($result['code'] == 200) {
    $clientToken = $result['response']['token'];
    echo "✅ Login exitoso\n";
} else {
    echo "❌ Login falló\n";
}
echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// 5. Probar registro de usuario con admin (debe funcionar)
echo "5. Probando registro de client con admin...\n";
if (!$adminToken) {
    echo "❌ No hay token de admin disponible\n\n";
} else {
    $result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
        'name' => 'New Client',
        'email' => "newclient{$timestamp}@example.com",
        'password' => 'password123',
        'type' => 'client'
    ], ["Authorization: Bearer {$adminToken}"]);

    if ($result['code'] == 201) {
        echo "✅ Registro exitoso\n";
    } else {
        echo "❌ Registro falló\n";
    }
    echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";
}

// 6. Probar registro de admin con admin (debe funcionar)
echo "6. Probando registro de admin con admin...\n";
if (!$adminToken) {
    echo "❌ No hay token de admin disponible\n\n";
} else {
    $result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
        'name' => 'New Admin',
        'email' => "newadmin{$timestamp}@example.com",
        'password' => 'password123',
        'type' => 'admin'
    ], ["Authorization: Bearer {$adminToken}"]);

    if ($result['code'] == 201) {
        echo "✅ Registro exitoso\n";
    } else {
        echo "❌ Registro falló\n";
    }
    echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";
}

// 7. Probar registro de admin con agent (debe fallar)
echo "7. Probando registro de admin con agent (debe fallar)...\n";
if (!$agentToken) {
    echo "❌ No hay token de agent disponible\n\n";
} else {
    $result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
        'name' => 'Another Admin',
        'email' => "anotheradmin{$timestamp}@example.com",
        'password' => 'password123',
        'type' => 'admin'
    ], ["Authorization: Bearer {$agentToken}"]);

    if ($result['code'] == 403) {
        echo "✅ Correctamente rechazado\n";
    } else {
        echo "❌ Debería haber sido rechazado\n";
    }
    echo "Respuesta: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";
}

// 8. Probar restricción de email único
echo "8. Probando restricción de email único...\n";
if (!$adminToken) {
    echo "❌ No hay token de admin disponible\n\n";
} else {
    // Primero crear un usuario
    $testEmail = "unique{$timestamp}@example.com";
    $result1 = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
        'name' => 'Unique Test User',
        'email' => $testEmail,
        'password' => 'password123',
        'type' => 'client'
    ], ["Authorization: Bearer {$adminToken}"]);

    if ($result1['code'] == 201) {
        echo "✅ Primer usuario creado exitosamente\n";

        // Intentar crear otro usuario con el mismo email
        $result2 = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
            'name' => 'Duplicate Test User',
            'email' => $testEmail, // Mismo email
            'password' => 'password123',
            'type' => 'client'
        ], ["Authorization: Bearer {$adminToken}"]);

        if ($result2['code'] == 400 || strpos($result2['response']['error'] ?? '', 'Duplicate entry') !== false) {
            echo "✅ Email duplicado correctamente rechazado\n";
        } else {
            echo "❌ Debería haber rechazado el email duplicado\n";
        }
        echo "Respuesta duplicado: " . json_encode($result2['response'], JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "❌ No se pudo crear el primer usuario para prueba\n\n";
    }
}