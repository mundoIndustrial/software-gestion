<?php
require 'bootstrap/autoload.php';
$app = require_once('bootstrap/app.php');

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$results = \DB::select("
    SELECT pppt.* 
    FROM pedidos_procesos_prenda_tallas pppt
    WHERE pppt.proceso_prenda_detalle_id IN (
        SELECT id FROM pedidos_procesos_prenda_detalles 
        WHERE prenda_pedido_id IN (
            SELECT id FROM prendas_pedido 
            WHERE pedido_produccion_id = 12
        )
    )
    ORDER BY pppt.id DESC 
    LIMIT 20
");

echo "Total records: " . count($results) . "\n";
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
