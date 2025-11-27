<?php
require 'vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Consultar todas las descripciones con "napole"
$descriptions = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->distinct()
    ->pluck('descripcion')
    ->sort()
    ->values()
    ->toArray();

echo "=== TOTAL VALORES ÚNICOS CON 'NAPOLE': " . count($descriptions) . " ===\n\n";

foreach ($descriptions as $idx => $desc) {
    echo ($idx + 1) . ". " . substr($desc, 0, 80) . (strlen($desc) > 80 ? "..." : "") . "\n";
}

echo "\n=== VALORES COMPLETOS ===\n\n";
foreach ($descriptions as $idx => $desc) {
    echo "[$idx]\n";
    echo $desc;
    echo "\n---\n";
}

// Ahora consultar qué devuelve el método getFilterValues
echo "\n=== QUÉ DEVUELVE EL BACKEND EN getFilterValues ===\n\n";

$backendValues = DB::table('prendas_pedido')
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->distinct()
    ->pluck('descripcion')
    ->filter(function($value) {
        return $value !== null && $value !== '';
    })
    ->values()
    ->toArray();

$napolesBackend = array_filter($backendValues, function($v) {
    return stripos($v, 'napole') !== false;
});

echo "Total valores en backend: " . count($backendValues) . "\n";
echo "Total con 'napole' en backend: " . count($napolesBackend) . "\n\n";

foreach ($napolesBackend as $idx => $desc) {
    echo "[$idx] " . substr($desc, 0, 80) . (strlen($desc) > 80 ? "..." : "") . "\n";
}

echo "\n=== COMPARACIÓN ===\n";
echo "Únicos en BD con LIKE: " . count($descriptions) . "\n";
echo "Únicos en backend filter: " . count($napolesBackend) . "\n";
echo "Diferencia: " . (count($descriptions) - count($napolesBackend)) . "\n";

// Encontrar cuáles faltan
echo "\n=== VALORES FALTANTES EN BACKEND ===\n";
$descriptionStrings = array_map('strval', $descriptions);
$napolesBackendStrings = array_map('strval', $napolesBackend);

foreach ($descriptionStrings as $desc) {
    if (!in_array($desc, $napolesBackendStrings)) {
        echo "FALTANTE: " . $desc . "\n";
    }
}
