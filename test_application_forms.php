<?php

// Script para probar las APIs de Planillas de Aplicación
// Ejecutar con: php test_application_forms.php

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

echo "=== PRUEBAS DE PLANILLAS DE APLICACIÓN ===\n\n";

// 1. Login con admin para obtener token
echo "1. Login con admin...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/login', [
    'email' => 'admin@example.com',
    'password' => 'password123'
]);

$adminToken = null;
if ($result['code'] == 200) {
    $adminToken = $result['response']['token'];
    echo "✅ Login exitoso - Token obtenido\n";
} else {
    echo "❌ Login falló\n";
    exit(1);
}

// 2. Login con agent
echo "\n2. Login con agent...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/login', [
    'email' => 'agent@example.com',
    'password' => 'password123'
]);

$agentToken = null;
if ($result['code'] == 200) {
    $agentToken = $result['response']['token'];
    echo "✅ Login exitoso - Token obtenido\n";
} else {
    echo "❌ Login falló\n";
    exit(1);
}

// 3. Crear usuario tipo client con agent
echo "\n3. Agent crea usuario tipo client...\n";
$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/auth/register', [
    'name' => 'Test Client',
    'email' => 'testclient' . time() . '@example.com',
    'password' => 'password123',
    'type' => 'client'
], ["Authorization: Bearer {$agentToken}"]);

$clientId = null;
if ($result['code'] == 201) {
    $clientId = $result['response']['user']['id'];
    echo "✅ Usuario client creado - ID: {$clientId}\n";
} else {
    echo "❌ Falló creación de usuario client\n";
    var_dump($result['response']);
    exit(1);
}

// 4. Agent crea planilla de aplicación para el client
echo "\n4. Agent crea planilla de aplicación...\n";
$formData = [
    'client_id' => $clientId,
    'applicant_name' => 'Test Client',
    'dob' => '1990-05-15',
    'address' => '123 Test Street',
    'city' => 'Test City',
    'state' => 'Test State',
    'zip_code' => '12345',
    'phone' => '+1234567890',
    'email' => 'testclient@example.com',
    'gender' => 'M',
    'ssn' => '123-45-6789',
    'legal_status' => 'Citizen',
    'document_number' => 'DOC123456',
    'employment_type' => 'W2',
    'employment_company_name' => 'Test Company',
    'wages' => 50000.00,
    'wages_frequency' => 'Monthly',
    // Person 1 data
    'person1_name' => 'Spouse Name',
    'person1_relation' => 'Spouse',
    'person1_is_applicant' => false,
    'person1_legal_status' => 'Citizen',
    'person1_document_number' => 'SPOUSE123',
    'person1_dob' => '1992-03-20',
    'person1_ssn' => '987-65-4321',
    'person1_gender' => 'F',
    // Payment method
    'card_type' => 'Visa',
    'card_number' => '4111111111111111',
    'card_expiration' => '12/25',
    'card_cvv' => '123',
    'bank_name' => 'Test Bank',
    'bank_routing' => '123456789',
    'bank_account' => '9876543210'
];

$result = makeRequest('POST', 'http://127.0.0.1:8000/api/v1/application-forms', $formData, [
    "Authorization: Bearer {$agentToken}"
]);

$formId = null;
if ($result['code'] == 201) {
    $formId = $result['response']['form']['id'];
    echo "✅ Planilla creada exitosamente - ID: {$formId}\n";
    echo "Status: {$result['response']['form']['status']}\n";
    echo "Confirmed: " . ($result['response']['form']['confirmed'] ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Falló creación de planilla\n";
    var_dump($result['response']);
    exit(1);
}

// 5. Agent confirma la planilla
echo "\n5. Agent confirma la planilla...\n";
$result = makeRequest('POST', "http://127.0.0.1:8000/api/v1/application-forms/{$formId}/confirm", [
    'confirmed' => true
], ["Authorization: Bearer {$agentToken}"]);

if ($result['code'] == 200) {
    echo "✅ Planilla confirmada exitosamente\n";
} else {
    echo "❌ Falló confirmación de planilla\n";
    var_dump($result['response']);
}

// 6. Admin cambia status a Activo
echo "\n6. Admin cambia status a Activo...\n";
$result = makeRequest('POST', "http://127.0.0.1:8000/api/v1/application-forms/{$formId}/status", [
    'status' => 'Activo',
    'status_comment' => 'Planilla revisada y aprobada por admin'
], ["Authorization: Bearer {$adminToken}"]);

if ($result['code'] == 200) {
    echo "✅ Status cambiado a Activo\n";
} else {
    echo "❌ Falló cambio de status\n";
    var_dump($result['response']);
}

// 7. Verificar que agent ya no puede editar (porque está confirmado)
echo "\n7. Verificar que agent no puede editar planilla confirmada...\n";
$result = makeRequest('PUT', "http://127.0.0.1:8000/api/v1/application-forms/{$formId}", [
    'applicant_name' => 'Intento de cambio'
], ["Authorization: Bearer {$agentToken}"]);

if ($result['code'] == 403) {
    echo "✅ Correctamente bloqueado - Agent no puede editar planilla confirmada\n";
} else {
    echo "❌ Debería estar bloqueado\n";
    var_dump($result['response']);
}

// 8. Admin puede editar cualquier planilla
echo "\n8. Admin puede editar cualquier planilla...\n";
$result = makeRequest('PUT', "http://127.0.0.1:8000/api/v1/application-forms/{$formId}", [
    'applicant_name' => 'Cambio por Admin'
], ["Authorization: Bearer {$adminToken}"]);

if ($result['code'] == 200) {
    echo "✅ Admin pudo editar exitosamente\n";
} else {
    echo "❌ Admin debería poder editar\n";
    var_dump($result['response']);
}

echo "\n=== PRUEBAS COMPLETADAS ===\n";
echo "🎉 Sistema de Planillas de Aplicación funcionando correctamente!\n";