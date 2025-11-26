<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE registros_por_orden (SOURCE) ===\n\n";

// Ver columnas
$cols = DB::getSchemaBuilder()->getColumnListing('registros_por_orden');
echo "Columnas:\n";
foreach($cols as $col) echo "  - $col\n";

echo "\n\nEjemplo de datos:\n";
$sample = DB::table('registros_por_orden')
    ->where('pedido', 43133)
    ->limit(3)
    ->get();

foreach($sample as $row) {
    echo "\nPedido: {$row->pedido}\n";
    echo "  Prenda: {$row->prenda}\n";
    echo "  Talla: {$row->talla}\n";
    echo "  Cantidad: {$row->cantidad}\n";
    echo "  Descripción: " . substr($row->descripcion, 0, 100) . "...\n";
}
?>
