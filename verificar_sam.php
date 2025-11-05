<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Balanceo;
use App\Models\OperacionBalanceo;

echo "=== VERIFICACIÓN DE VALORES SAM ===\n\n";

// Obtener el primer balanceo
$balanceo = Balanceo::with('operaciones')->first();

if (!$balanceo) {
    echo "No hay balanceos en la base de datos.\n";
    exit;
}

echo "Balanceo ID: {$balanceo->id}\n";
echo "SAM Total guardado: {$balanceo->sam_total}\n\n";

echo "Operaciones individuales:\n";
echo str_repeat("-", 50) . "\n";

$sumaManual = 0;
foreach ($balanceo->operaciones as $op) {
    echo sprintf("%-5s | %-30s | %8.3f\n", $op->letra, substr($op->operacion, 0, 30), $op->sam);
    $sumaManual += $op->sam;
}

echo str_repeat("-", 50) . "\n";
echo "Suma manual (PHP):           " . number_format($sumaManual, 3) . "\n";
echo "Suma con round(1 decimal):   " . number_format(round($sumaManual, 1), 1) . "\n";
echo "SAM Total en DB:             " . number_format($balanceo->sam_total, 1) . "\n";
echo "Diferencia:                  " . number_format($sumaManual - $balanceo->sam_total, 3) . "\n";
