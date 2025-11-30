<?php

namespace App\Core;

require __DIR__ . '/../vendor/autoload.php';

echo "Testing PHPMailer in App\\Core namespace...\n";

// Test 1: class_exists with string (no leading backslash in string)
if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "class_exists('PHPMailer...') returned TRUE\n";
} else {
    echo "class_exists('PHPMailer...') returned FALSE\n";
}

// Test 2: class_exists with FQCN string
if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "class_exists('\\PHPMailer...') returned TRUE\n";
} else {
    echo "class_exists('\\PHPMailer...') returned FALSE\n";
}

// Test 3: Instantiation with backslash
try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    echo "Instantiation with \\PHPMailer... SUCCESS\n";
} catch (\Throwable $e) {
    echo "Instantiation with \\PHPMailer... FAILED: " . $e->getMessage() . "\n";
}
