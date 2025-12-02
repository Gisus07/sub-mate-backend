<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno si es necesario
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\services\HomeService;

echo "Verifying HomeService...\n";

try {
    $service = new HomeService();
    // Usamos un ID de usuario que sepamos que existe, por ejemplo 1.
    $uid = 1;

    $data = $service->obtenerDatosHome($uid);

    echo "Status: " . $data['status'] . "\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";

    if (isset($data['data'])) {
        echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";

        if (isset($data['data']['semaforo'])) {
            echo "Semaforo keys: " . implode(', ', array_keys($data['data']['semaforo'])) . "\n";
            echo "Gasto 7 dias: " . $data['data']['semaforo']['gasto_7_dias'] . "\n";
        }

        if (isset($data['data']['proximos_vencimientos'])) {
            echo "Proximos vencimientos count: " . count($data['data']['proximos_vencimientos']) . "\n";
        }

        if (isset($data['data']['gasto_semanal'])) {
            echo "Gasto semanal keys: " . implode(', ', array_keys($data['data']['gasto_semanal'])) . "\n";
        }
    } else {
        echo "ERROR: 'data' key missing.\n";
    }

    echo "\nFull Response:\n";
    print_r($data);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
