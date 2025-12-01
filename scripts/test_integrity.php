<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Services\SuscripcionOperacionesService;
use App\core\Database;

try {
    $db = Database::getDB();
    $stmt = $db->prepare("SELECT id_ahjr FROM td_usuarios_ahjr WHERE email_ahjr = 'beta@submate.app'");
    $stmt->execute();
    $user = $stmt->fetch();
    $userId = $user['id_ahjr'];

    $service = new SuscripcionOperacionesService();

    // Get a sub
    $stmtSub = $db->prepare("SELECT id_suscripcion_ahjr FROM td_suscripciones_ahjr WHERE id_usuario_suscripcion_ahjr = :uid LIMIT 1");
    $stmtSub->execute(['uid' => $userId]);
    $sub = $stmtSub->fetch();
    $subId = $sub['id_suscripcion_ahjr'];

    echo "Testing Integrity with Sub ID: $subId\n";

    // 1. Deactivate (Sanitization Check)
    echo "--- Deactivating ---\n";
    $res = $service->gestionarEstado($subId, 'inactiva', $userId);

    // Check DB directly for NULLs
    $stmtCheck = $db->prepare("SELECT dia_cobro_ahjr, mes_cobro_ahjr FROM td_suscripciones_ahjr WHERE id_suscripcion_ahjr = :id");
    $stmtCheck->execute(['id' => $subId]);
    $check = $stmtCheck->fetch();

    if ($check['dia_cobro_ahjr'] === null && $check['mes_cobro_ahjr'] === null) {
        echo "PASS: Sanitization successful (NULLs set).\n";
    } else {
        echo "FAIL: Sanitization failed. Day: {$check['dia_cobro_ahjr']}, Month: {$check['mes_cobro_ahjr']}\n";
    }

    // 2. Reactivate (Date Update Check)
    echo "\n--- Reactivating ---\n";
    $res = $service->gestionarEstado($subId, 'activa', $userId);

    $todayDay = (int)date('d');
    if ((int)$res['dia_cobro_ahjr'] === $todayDay) {
        echo "PASS: Cycle updated to today ($todayDay).\n";
    } else {
        echo "FAIL: Cycle NOT updated. Expected $todayDay, got {$res['dia_cobro_ahjr']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
