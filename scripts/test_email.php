<?php

require __DIR__ . '/../vendor/autoload.php';

use App\core\Env;
use App\core\Mailer;

Env::loadEnv(__DIR__ . '/..');

$to = $argv[1] ?? null;
if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido o no provisto']);
    exit(1);
}

$html = Mailer::generarTemplateHTML_AHJR('Prueba SMTP - SubMate', 'Prueba desde CLI');
$ok = Mailer::sendEmail_AHJR($to, 'Prueba SMTP - SubMate', $html);

echo json_encode([
    'success' => $ok,
    'error' => Mailer::getLastError(),
]);
