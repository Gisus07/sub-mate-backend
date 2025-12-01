<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\core\Database;

$db = Database::getDB();
$stmt = $db->query("SELECT id_suscripcion_ahjr, estado_ahjr, fecha_proximo_pago_ahjr FROM td_suscripciones_ahjr WHERE id_usuario_suscripcion_ahjr = 2");
while ($row = $stmt->fetch()) {
    print_r($row);
}
