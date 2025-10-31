<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Backend Laravel está funcionando',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
