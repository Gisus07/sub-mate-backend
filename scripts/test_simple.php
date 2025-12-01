<?php
require_once __DIR__ . '/../vendor/autoload.php';
$s = new \App\Services\SuscripcionService();
echo "Class loaded.\n";
if (method_exists($s, 'cambiarEstado')) {
    echo "Method exists.\n";
} else {
    echo "Method MISSING.\n";
}
