<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Cambiar estado de PENDIENTE_SUPERVISOR a pendiente_cartera
$updated = DB::table('pedidos_produccion')
    ->where('estado', 'PENDIENTE_SUPERVISOR')
    ->update(['estado' => 'pendiente_cartera']);

echo "Pedidos actualizados: " . $updated . "\n";

// Verificar
$count = DB::table('pedidos_produccion')
    ->where('estado', 'pendiente_cartera')
    ->count();

echo "Pedidos con estado pendiente_cartera: " . $count . "\n";
