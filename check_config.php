<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = glob('config/*.php');
foreach($files as $file) {
    try {
        $result = require $file;
        if (!is_array($result)) {
            echo 'LOI: ' . $file . ' - ' . gettype($result) . PHP_EOL;
        } else {
            echo 'OK: ' . $file . PHP_EOL;
        }
    } catch (Exception $e) {
        echo 'EXCEPTION: ' . $file . ' - ' . $e->getMessage() . PHP_EOL;
    }
}