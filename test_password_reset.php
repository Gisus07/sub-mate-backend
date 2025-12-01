<?php
require 'vendor/autoload.php';

use App\Services\AuthService;
use App\Models\UsuarioOTPModel;
use App\Models\UsuarioModel;
use App\Core\Database;

// Mock environment
if (!isset($_ENV['JWT_SECRET'])) {
    $_ENV['JWT_SECRET'] = 'test_secret';
}

$authService = new AuthService();
$otpModel = new UsuarioOTPModel();
$usuarioModel = new UsuarioModel();

$email = 'test_reset_' . time() . '@example.com';
$oldPassword = 'password123';
$newPassword = 'newpassword456';

echo "1. Creating Test User...\n";
try {
    // Create user directly via model to skip OTP registration flow for this test
    $usuarioModel->crear([
        'nombre' => 'Test',
        'apellido' => 'Reset',
        'email' => $email,
        'clave' => password_hash($oldPassword, PASSWORD_BCRYPT),
        'estado' => 'activo',
        'rol' => 'user'
    ]);
    echo "User created.\n";
} catch (Throwable $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    exit;
}

echo "2. Requesting Password Reset...\n";
try {
    $authService->solicitarResetPassword($email);
    echo "Reset requested (Email sent).\n";
} catch (Throwable $e) {
    echo "Reset request error: " . $e->getMessage() . "\n";
}

echo "3. Checking Reset Record in DB...\n";
$reset = $otpModel->obtenerResetPorEmail($email);

if ($reset) {
    echo "SUCCESS: Reset record found for $email\n";

    // Inject known OTP
    echo "   Injecting known OTP '654321'...\n";
    $knownOtp = '654321';
    $knownHash = password_hash($knownOtp, PASSWORD_BCRYPT);

    $db = Database::getDB();
    $stmt = $db->prepare("UPDATE td_reset_clave_ahjr SET otp_hash_ahjr = :hash WHERE email_ahjr = :email");
    $stmt->execute(['hash' => $knownHash, 'email' => $email]);

    echo "4. Verifying Reset with '654321' and New Password...\n";
    try {
        $result = $authService->verificarResetPassword($email, $knownOtp, $newPassword);
        echo "Verification Result: " . print_r($result, true) . "\n";

        // Verify new password works (by checking hash change or login)
        $usuario = $usuarioModel->buscarPorEmail($email);
        if (password_verify($newPassword, $usuario['clave_ahjr'])) {
            echo "SUCCESS: Password updated correctly.\n";
        } else {
            echo "FAILURE: Password NOT updated.\n";
        }
    } catch (Throwable $e) {
        echo "Verification Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "FAILURE: No reset record found.\n";
}

// Clean up
$usuario = $usuarioModel->buscarPorEmail($email);
if ($usuario) {
    $usuarioModel->eliminar($usuario['id_ahjr']);
    echo "Cleaned up user.\n";
}
