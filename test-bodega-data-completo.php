<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BodegaDetallesTalla;

echo "=== BUSCANDO TODOS LOS REGISTROS CON numero_pedido=6 ===\n\n";

$detalles = BodegaDetallesTalla::where('numero_pedido', 6)->get();
echo "Total registros encontrados: " . $detalles->count() . "\n\n";

$detalles->each(function($item, $index) {
    echo "--- Registro #" . ($index + 1) . " ---\n";
    echo "  id: {$item->id}\n";
    echo "  numero_pedido: {$item->numero_pedido}\n";
    echo "  talla: '{$item->talla}'\n";
    echo "  prenda_nombre: '{$item->prenda_nombre}'\n";
    echo "  cantidad: {$item->cantidad}\n";
    echo "  estado_bodega: '{$item->estado_bodega}'\n";
    echo "  area: '{$item->area}'\n";
    echo "  fecha_pedido: {$item->fecha_pedido}\n";
    echo "  fecha_entrega: {$item->fecha_entrega}\n";
    echo "  observaciones_bodega: " . strlen($item->observaciones_bodega) . " caracteres\n";
    echo "\n";
});

echo "=== BÚSQUEDA ESPECÍFICA POR talla=cb095257c44ec1ab92c5a9a8b52f2dc8 ===\n";
$especifico = BodegaDetallesTalla::where('numero_pedido', 6)->where('talla', 'cb095257c44ec1ab92c5a9a8b52f2dc8')->first();
if ($especifico) {
    echo "Encontrado!\n";
    echo "estado_bodega: '{$especifico->estado_bodega}'\n";
    echo "area: '{$especifico->area}'\n";
} else {
    echo "No encontrado\n";
}
