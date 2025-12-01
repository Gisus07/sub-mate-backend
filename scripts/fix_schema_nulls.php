<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\core\Database;

try {
    $db = Database::getDB();
    echo "Altering table td_suscripciones_ahjr to allow NULL in dia_cobro_ahjr...\n";

    $sql = "ALTER TABLE td_suscripciones_ahjr MODIFY COLUMN dia_cobro_ahjr TINYINT UNSIGNED NULL COMMENT 'Día del mes (1-31)'";
    $db->exec($sql);

    echo "Table altered successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
