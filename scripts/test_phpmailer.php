<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    echo "PHPMailer instantiated successfully.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
