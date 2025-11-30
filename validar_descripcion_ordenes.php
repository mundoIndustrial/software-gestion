<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Models/PedidoProduccion.php';
require __DIR__ . '/app/Models/PrendaPedido.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as DB;

$capsule = new DB();
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== VALIDACIÓN DE DESCRIPCIÓN CON TALLAS ===\n\n";

use App\Models\PedidoProduccion;

// Obtener un pedido con prendas migradas
$ordenes = PedidoProduccion::with('prendas')
    ->where('cotizacion_id', null)
    ->limit(3)
    ->get();

foreach ($ordenes as $orden) {
    echo "📦 Pedido: {$orden->numero_pedido}\n";
    echo "─────────────────────────────────────────\n";
    echo $orden->descripcion_prendas;
    echo "\n\n";
    
    // Mostrar datos crudos de prendas
    echo "📋 Prendas asociadas:\n";
    foreach ($orden->prendas as $i => $prenda) {
        echo "  Prenda " . ($i + 1) . ": {$prenda->nombre_prenda}\n";
        echo "    - cantidad_talla: " . ($prenda->cantidad_talla ?? 'NULL') . "\n";
        echo "    - descripcion_armada: " . substr($prenda->descripcion_armada ?? '', 0, 100) . "...\n";
    }
    echo "\n\n";
}

echo "✅ Validación completada\n";
