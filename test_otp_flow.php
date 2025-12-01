<?php
require 'vendor/autoload.php';

use App\Services\AuthService;
use App\Models\UsuarioOTPModel;
use App\Models\UsuarioModel;
use App\core\Database;

// Mock environment if needed
if (!isset($_ENV['JWT_SECRET'])) {
    $_ENV['JWT_SECRET'] = 'test_secret';
}

$authService = new AuthService();
$otpModel = new UsuarioOTPModel();
$usuarioModel = new UsuarioModel();

$email = 'test_otp_' . time() . '@example.com';
$password = 'password123';

echo "1. Testing Registration (OTP Generation)...\n";
try {
    $authService->registrarUsuario([
        'nombre' => 'Test',
        'apellido' => 'User',
        'email' => $email,
        'clave' => $password
    ]);
    echo "Registration call completed (Email sent).\n";
} catch (Throwable $e) {
    echo "Registration call ended: " . $e->getMessage() . "\n";
}

echo "2. Checking Pending Registration in DB...\n";
$pendiente = $otpModel->obtenerPendientePorEmail($email);

if ($pendiente) {
    echo "SUCCESS: Pending record found for $email\n";

    // Manually update OTP to a known value to test verification
    echo "   Injecting known OTP '123456' for testing...\n";
    $knownOtp = '123456';
    $knownHash = password_hash($knownOtp, PASSWORD_BCRYPT);

    $db = Database::getDB();
    $stmt = $db->prepare("UPDATE td_registro_pendiente_ahjr SET otp_hash_ahjr = :hash WHERE email_ahjr = :email");
    $stmt->execute(['hash' => $knownHash, 'email' => $email]);

    echo "3. Testing Verification with '123456'...\n";
    try {
        $result = $authService->verificarYActivar($email, $knownOtp);
        echo "Verification Result: " . print_r($result, true) . "\n";

        $usuario = $usuarioModel->buscarPorEmail($email);
        if ($usuario) {
            echo "SUCCESS: User created in main table.\n";
            echo "User ID: " . $usuario['id_ahjr'] . "\n";
            echo "User Role: " . $usuario['rol_ahjr'] . "\n";

            // Clean up
            $usuarioModel->eliminar($usuario['id_ahjr']);
            echo "Cleaned up user.\n";
        } else {
            echo "FAILURE: User NOT found in main table.\n";
        }
    } catch (Throwable $e) {
        echo "Verification Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "FAILURE: No pending record found.\n";
}
