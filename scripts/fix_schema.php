<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Core\Database;

try {
    $db = Database::getDB();
    echo "Altering table td_suscripciones_ahjr...\n";
    $db->exec("ALTER TABLE td_suscripciones_ahjr MODIFY COLUMN fecha_proximo_pago_ahjr DATE NULL");
    echo "Table altered successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
