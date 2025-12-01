<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\SuscripcionService;
use App\Models\UsuarioModel;
use App\Core\Database;

// Helper to print colored output
function printColor($text, $color = '37')
{
    echo "\033[{$color}m{$text}\033[0m\n";
    flush();
}

try {
    $service = new SuscripcionService();
    $userModel = new UsuarioModel();
    $db = Database::getDB();

    // 1. Create a dummy user
    $email = 'test_edit_' . time() . '@example.com';
    $userData = [
        'nombre' => 'Test',
        'apellido' => 'User',
        'email' => $email,
        'clave' => password_hash('password123', PASSWORD_DEFAULT),
        'estado' => 'activo',
        'rol' => 'user'
    ];
    $userId = $userModel->crear($userData);
    printColor("User created with ID: $userId", '32');

    // 2. Create a subscription
    $subData = [
        'nombre_servicio' => 'Netflix',
        'costo' => 15.00,
        'frecuencia' => 'mensual',
        'metodo_pago' => 'Visa',
        'dia_cobro' => 15
    ];
    $subResult = $service->crear($subData, $userId);
    $subId = $subResult['id'];
    printColor("Subscription created with ID: $subId", '32');

    // Activate it to have dates
    $service->cambiarEstado($subId, $userId, 'ACTIVO');
    $initialState = $service->obtenerDetalle($subId, $userId);
    printColor("Initial Next Payment: " . $initialState['fecha_proximo_pago'], '36');

    // TEST 1: Attempt to change name (Should Fail)
    printColor("\nTEST 1: Attempting to change name...", '33');
    try {
        $service->modificar($subId, ['nombre_servicio' => 'Spotify'], $userId);
        printColor("FAIL: Name change should have been blocked.", '31');
    } catch (Exception $e) {
        if ($e->getCode() === 400) {
            printColor("PASS: Name change blocked with 400.", '32');
        } else {
            printColor("FAIL: Unexpected error: " . $e->getMessage(), '31');
        }
    }

    // TEST 2: Update without frequency change (Dates should stay same)
    printColor("\nTEST 2: Update cost (no frequency change)...", '33');
    $service->modificar($subId, ['costo' => 20.00], $userId);
    $state2 = $service->obtenerDetalle($subId, $userId);

    if ($state2['fecha_proximo_pago'] === $initialState['fecha_proximo_pago']) {
        printColor("PASS: Date remained unchanged.", '32');
    } else {
        printColor("FAIL: Date changed unexpectedly! Was {$initialState['fecha_proximo_pago']}, now {$state2['fecha_proximo_pago']}", '31');
    }

    // TEST 3: Update WITH frequency change (Dates should change)
    printColor("\nTEST 3: Change frequency to Annual...", '33');
    $service->modificar($subId, ['frecuencia' => 'Anual'], $userId);
    $state3 = $service->obtenerDetalle($subId, $userId);

    if ($state3['fecha_proximo_pago'] !== $initialState['fecha_proximo_pago']) {
        printColor("PASS: Date updated. New date: " . $state3['fecha_proximo_pago'], '32');
    } else {
        printColor("FAIL: Date did NOT update!", '31');
    }

    // Cleanup
    $service->borrar($subId, $userId);
    // Delete user manually since no delete method in model for user
    $db->prepare("DELETE FROM td_usuarios_ahjr WHERE id_ahjr = ?")->execute([$userId]);
    printColor("\nCleanup complete.", '32');
} catch (Exception $e) {
    printColor("FATAL ERROR: " . $e->getMessage(), '31');
    echo $e->getTraceAsString();
}
