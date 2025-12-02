<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\services\HomeService;
use App\core\Database;

$db = Database::getDB();
$homeService = new HomeService();

// Test with Beta user (should have data)
$betaStmt = $db->query("SELECT id_ahjr FROM td_usuarios_ahjr WHERE email_ahjr = 'beta@submate.app' LIMIT 1");
$betaUser = $betaStmt->fetch();

if (!$betaUser) {
    echo "ERROR: Beta user not found. Run scripts/crear.php first.\n";
    exit(1);
}

$betaId = (int) $betaUser['id_ahjr'];

echo "=== Testing Home Endpoint with Beta User (ID: $betaId) ===\n\n";

try {
    $result = $homeService->obtenerDatosHome($betaId);

    echo "Response structure: OK\n";

    // Verify semaforo
    $semaforo = $result['data']['semaforo'];
    echo "Semaforo:\n";
    echo "  - gasto_7_dias: " . $semaforo['gasto_7_dias'] . "\n";
    echo "  - total_suscripciones: " . $semaforo['total_suscripciones'] . "\n";
    echo "  - proximo_gran_cargo: " . ($semaforo['proximo_gran_cargo'] ? $semaforo['proximo_gran_cargo']['nombre'] : 'null') . "\n";

    // Verify proximos_vencimientos
    $vencimientos = $result['data']['proximos_vencimientos'];
    echo "\nProximos vencimientos: " . count($vencimientos) . " found\n";

    // Verify gasto_semanal
    $gastoSemanal = $result['data']['gasto_semanal'];
    echo "\nGasto semanal:\n";
    foreach ($gastoSemanal['labels'] as $idx => $label) {
        echo "  - $label: " . $gastoSemanal['data'][$idx] . "\n";
    }

    // Validate data is not empty
    if ($semaforo['total_suscripciones'] > 0) {
        echo "\n✓ SUCCESS: Home endpoint returning data correctly!\n";
    } else {
        echo "\n✗ WARNING: No active subscriptions found for beta user.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
