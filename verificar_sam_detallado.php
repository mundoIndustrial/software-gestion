<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Balanceo;
use App\Models\OperacionBalanceo;

$balanceoId = $argv[1] ?? 4; // Por defecto ID 4

echo "=== VERIFICACIÓN DETALLADA DE SAM - Balanceo ID: {$balanceoId} ===\n\n";

$balanceo = Balanceo::with(['prenda', 'operaciones'])->find($balanceoId);

if (!$balanceo) {
    echo "Balanceo no encontrado.\n";
    exit;
}

echo "Prenda: {$balanceo->prenda->nombre}\n";
echo "SAM Total guardado en DB: {$balanceo->sam_total}\n";
echo "Total de operaciones: " . $balanceo->operaciones->count() . "\n\n";

echo "Valores SAM individuales:\n";
echo str_repeat("=", 60) . "\n";

$sumaManual = 0;
$contador = 0;

foreach ($balanceo->operaciones as $op) {
    $contador++;
    echo sprintf("%2d. SAM: %8.3f (guardado como: %s)\n", 
        $contador,
        $op->sam,
        var_export($op->sam, true)
    );
    $sumaManual += $op->sam;
}

echo str_repeat("=", 60) . "\n";
echo "\nRESULTADOS:\n";
echo "Suma manual (precisión completa): " . number_format($sumaManual, 10) . "\n";
echo "Suma con round(1 decimal):        " . number_format(round($sumaManual, 1), 1) . "\n";
echo "SAM Total en DB:                  " . number_format($balanceo->sam_total, 1) . "\n";
echo "Diferencia:                       " . number_format($sumaManual - $balanceo->sam_total, 10) . "\n";

echo "\n¿Qué debería ser según Excel? 784.2\n";
echo "Diferencia con Excel:             " . number_format(784.2 - $sumaManual, 3) . "\n";
