<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Filtrar solo los que contienen "napole"
$napoles = $uniqueValues->filter(function($v) {
    return stripos($v, 'napole') !== false;
});

echo "Con 'napole': " . count($napoles) . "\n";
foreach ($napoles as $idx => $val) {
    echo "[$idx] " . substr($val, 0, 80) . "\n";
}

// Convertir a array
$uniqueValuesArray = $uniqueValues->toArray();

// Mostrar JSON que se enviaría
echo "\nTotal en JSON response: " . count($uniqueValuesArray) . "\n";
echo "\nPrimeros 20 valores en JSON:\n";
foreach (array_slice($uniqueValuesArray, 0, 20) as $idx => $val) {
    echo "[$idx] " . substr($val, 0, 80) . "\n";
}
