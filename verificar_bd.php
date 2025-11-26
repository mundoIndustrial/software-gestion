<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE BASE DE DATOS ===\n";
echo "Pedidos: " . DB::table('pedidos_produccion')->count() . "\n";
echo "Prendas: " . DB::table('prendas_pedido')->count() . "\n";
echo "Procesos: " . DB::table('procesos_prenda')->count() . "\n";
echo "Cotizaciones: " . DB::table('cotizaciones')->count() . "\n";
echo "Prendas Cotizaciones: " . DB::table('prendas_cotizaciones')->count() . "\n";

if (DB::table('pedidos_produccion')->count() > 0) {
    echo "\n=== ÚLTIMOS PEDIDOS ===\n";
    DB::table('pedidos_produccion')
        ->latest()
        ->limit(5)
        ->get()
        ->each(function($p) {
            echo "ID: {$p->id} | Pedido: {$p->numero_pedido} | Cliente: {$p->cliente}\n";
        });
}

if (DB::table('prendas_pedido')->count() > 0) {
    echo "\n=== ÚLTIMAS PRENDAS ===\n";
    DB::table('prendas_pedido')
        ->latest()
        ->limit(3)
        ->get()
        ->each(function($p) {
            echo "ID: {$p->id} | Nombre: {$p->nombre_prenda} | Descripción (primeros 100 chars): " . substr($p->descripcion, 0, 100) . "\n";
        });
}
