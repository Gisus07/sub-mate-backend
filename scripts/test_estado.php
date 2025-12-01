<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\services\SuscripcionService;
use App\services\AlertsService;
use App\core\Database;

try {
    $db = Database::getDB();
    $stmt = $db->prepare("SELECT id_ahjr FROM td_usuarios_ahjr WHERE email_ahjr = 'beta@submate.app'");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        die("Beta user not found. Run scripts/crear.php first.\n");
    }

    $userId = $user['id_ahjr'];

    $service = new SuscripcionService();
    $alerts = new AlertsService();

    echo "User ID: $userId\n";

    // 1. Get a subscription
    $list = $service->obtenerLista($userId);
    if (empty($list)) {
        die("No subscriptions found for beta user.\n");
    }
    $subId = $list[0]['id'];
    // Ensure we start with a known state if needed, but we'll just read current
    echo "Testing with Subscription ID: $subId (Initial State: {$list[0]['estado']})\n";

    // TEST 1: Deactivate
    echo "\n--- TEST 1: Deactivate ---\n";
    $updated = $service->cambiarEstado($subId, $userId, 'INACTIVO');
    echo "New State: " . $updated['estado'] . "\n";
    echo "Next Payment: " . ($updated['fecha_proximo_pago'] === null ? 'NULL' : $updated['fecha_proximo_pago']) . "\n";

    if ($updated['estado'] === 'INACTIVO' && $updated['fecha_proximo_pago'] === null) {
        echo "PASS: Deactivation logic correct.\n";
    } else {
        echo "FAIL: Deactivation logic incorrect.\n";
    }

    // Test Guardrail (Alerts)
    // Manually call alerts to test return value
    // We pass 'INACTIVO' as old state (since we just updated it) and 'INACTIVO' as new state
    // This simulates a redundant call
    $sent = $alerts->enviarCambioEstado($updated, 'INACTIVO', 'INACTIVO');
    if ($sent === false) {
        echo "PASS: Guardrail prevented email (returned false).\n";
    } else {
        echo "FAIL: Guardrail failed (returned true).\n";
    }

    // TEST 2: Activate
    echo "\n--- TEST 2: Activate ---\n";
    // Activating with payment day 20
    $updated = $service->cambiarEstado($subId, $userId, 'ACTIVO', 20);
    echo "New State: " . $updated['estado'] . "\n";
    echo "Last Payment: " . $updated['fecha_ultimo_pago'] . "\n";
    echo "Next Payment: " . $updated['fecha_proximo_pago'] . "\n";

    if ($updated['estado'] === 'ACTIVO' && $updated['fecha_ultimo_pago'] === date('Y-m-d')) {
        echo "PASS: Activation logic correct.\n";
    } else {
        echo "FAIL: Activation logic incorrect.\n";
    }

    // TEST 3: Service Guardrail
    echo "\n--- TEST 3: Service Guardrail ---\n";
    // Call activate again with same params
    $updated2 = $service->cambiarEstado($subId, $userId, 'ACTIVO', 20);
    echo "Service call successful (returned object).\n";
} catch (Exception $e) {
    file_put_contents('error_log.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "ERROR LOGGED.\n";
}
