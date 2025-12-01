<?php
// Minimalist test
putenv('APP_ENV=local');

require __DIR__ . '/vendor/autoload.php';

// Load env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create app
$app = require __DIR__ . '/bootstrap/app.php';

try {
    // Get DB connection
    $db = $app['db'];
    
    // Check tabla_original_bodega
    $count = $db->table('tabla_original_bodega')->count();
    echo "tabla_original_bodega: $count registros\n";
    
    if ($count == 0) {
        // Check tabla_original
        $countOrig = $db->table('tabla_original')->count();
        echo "tabla_original: $countOrig registros\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
