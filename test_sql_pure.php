<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Usando DB::connection para SQL puro
$result = DB::connection()->select("SELECT DISTINCT descripcion FROM prendas_pedido WHERE descripcion IS NOT NULL AND descripcion != '' ORDER BY descripcion");

echo "SQL Puro - Total valores: " . count($result) . "\n";

// Convertir a array
$values = array_map(function($row) {
    return $row->descripcion;
}, $result);

// Napoles
$napoles = array_filter($values, function($v) {
    return stripos($v, 'napole') !== false;
});

echo "Con 'napole': " . count($napoles) . "\n";
foreach ($napoles as $val) {
    echo "- " . substr($val, 0, 80) . "\n";
}

// Ahora probar LIKE
echo "\n=== Con LIKE '%napole%' ===\n";
$likeResult = DB::connection()->select("SELECT DISTINCT descripcion FROM prendas_pedido WHERE LOWER(descripcion) LIKE '%napole%' ORDER BY descripcion");

echo "SQL Puro LIKE - Total valores: " . count($likeResult) . "\n";
foreach ($likeResult as $row) {
    echo "- " . substr($row->descripcion, 0, 80) . "\n";
}
