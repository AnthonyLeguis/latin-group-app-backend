<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);

$stmt = $pdo->query('SELECT id, email, wages, subsidy, final_cost, pending_changes FROM application_forms WHERE has_pending_changes = 1 LIMIT 1');
$form = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Form ID: " . $form['id'] . "\n";
echo "Current Values:\n";
echo "  email: " . $form['email'] . "\n";
echo "  wages: " . $form['wages'] . "\n";
echo "  subsidy: " . $form['subsidy'] . "\n";
echo "  final_cost: " . $form['final_cost'] . "\n";
echo "\nPending Changes:\n";

$changes = json_decode($form['pending_changes'], true);
foreach ($changes as $key => $value) {
    $currentValue = $form[$key] ?? 'NULL';
    $isDifferent = ($value != $currentValue) ? '[DIFFERENT]' : '[SAME]';
    echo "  $key: '$value' (current: '$currentValue') $isDifferent\n";
}
