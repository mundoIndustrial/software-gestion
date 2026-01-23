<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

// Modificar el ENUM para incluir pendiente_cartera
DB::statement("ALTER TABLE pedidos_produccion MODIFY estado ENUM('Pendiente', 'Entregado', 'En Ejecución', 'No iniciado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'pendiente_cartera')");

echo "ENUM modificado para incluir 'pendiente_cartera'\n";

// Ahora actualizar los pedidos con estado PENDIENTE_SUPERVISOR a pendiente_cartera
$updated = DB::table('pedidos_produccion')
    ->where('estado', 'PENDIENTE_SUPERVISOR')
    ->update(['estado' => 'pendiente_cartera']);

echo "Pedidos actualizados: " . $updated . "\n";

// Verificar
$count = DB::table('pedidos_produccion')
    ->where('estado', 'pendiente_cartera')
    ->count();

echo "Pedidos con estado pendiente_cartera: " . $count . "\n";
