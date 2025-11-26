<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener valores únicos de estado_proceso
$valores = DB::table('procesos_prenda')
    ->distinct()
    ->pluck('estado_proceso')
    ->toArray();

echo "Valores únicos en estado_proceso:\n";
print_r($valores);
?>
