<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Limpiar procesos_prenda
DB::statement('DELETE FROM procesos_prenda');
echo "✅ Tabla procesos_prenda vaciada\n";

// Mostrar conteo
$count = DB::table('procesos_prenda')->count();
echo "Registros en procesos_prenda: " . $count . "\n";
