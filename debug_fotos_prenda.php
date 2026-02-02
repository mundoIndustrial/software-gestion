<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$fotos = \DB::table('prenda_fotos_pedido')
    ->where('prenda_pedido_id', 6)
    ->get(['id', 'prenda_pedido_id', 'ruta_webp', 'orden']);

echo "Fotos de prenda 6:\n";
echo json_encode($fotos, JSON_PRETTY_PRINT) . "\n";

// También verificar procesos
$procesos = \DB::table('pedidos_procesos_prenda_detalle')
    ->where('prenda_pedido_id', 6)
    ->get(['id', 'tipo_proceso_id']);

echo "\nProcesos de prenda 6:\n";
echo json_encode($procesos, JSON_PRETTY_PRINT) . "\n";

// Imágenes de proceso
$imagenesProc = \DB::table('pedidos_procesos_imagenes')
    ->whereIn('proceso_prenda_detalle_id', $procesos->pluck('id'))
    ->get(['id', 'proceso_prenda_detalle_id', 'ruta_webp']);

echo "\nImágenes de procesos:\n";
echo json_encode($imagenesProc, JSON_PRETTY_PRINT) . "\n";
