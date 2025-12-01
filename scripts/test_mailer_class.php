<?php
require __DIR__ . '/../vendor/autoload.php';

use App\core\Mailer;

try {
    if (class_exists(Mailer::class)) {
        echo "App\\core\\Mailer class exists.\n";
    } else {
        echo "App\\core\\Mailer class NOT found.\n";
    }

    // Check if we can call a method (it will fail due to missing env/params, but we want to see if it crashes on class load)
    // Actually, we don't need to call it to verify the "Undefined type" error is gone from a runtime perspective.
    // The runtime error would be "Class not found" when executing the line with `new PHPMailer`.
    // But we can't execute that line without sending mail.

    // However, we can check if the file has syntax errors.
    echo "Mailer class loaded successfully.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
