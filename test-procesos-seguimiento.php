<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->bootstrap();

// Check procesos_prenda table
$procesos = \DB::table('procesos_prenda')->whereNull('deleted_at')->limit(5)->get();
echo "=== PROCESOS_PRENDA ===\n";
echo "Procesos encontrados: " . count($procesos) . "\n";

if ($procesos->count() > 0) {
    echo json_encode($procesos->first(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "No hay procesos en la tabla procesos_prenda\n";
}

// Check pedidos_produccion
$pedidos = \DB::table('pedidos_produccion')->whereNull('deleted_at')->limit(1)->get();
echo "\n=== PEDIDOS_PRODUCCION ===\n";
echo "Pedidos encontrados: " . count($pedidos) . "\n";
if ($pedidos->count() > 0) {
    echo json_encode($pedidos->first(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Check prendas_pedido
$prendas = \DB::table('prendas_pedido')->whereNull('deleted_at')->limit(1)->get();
echo "\n=== PRENDAS_PEDIDO ===\n";
echo "Prendas encontradas: " . count($prendas) . "\n";
if ($prendas->count() > 0) {
    echo json_encode($prendas->first(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
