<?php
use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';
$app = require_once('bootstrap/app.php');

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->bootstrap();

$results = DB::table('pedidos_procesos_prenda_tallas')
    ->whereIn('proceso_prenda_detalle_id', 
        DB::table('pedidos_procesos_prenda_detalles')
            ->whereIn('prenda_pedido_id', 
                DB::table('prendas_pedido')
                    ->where('pedido_produccion_id', 12)
                    ->pluck('id')
            )
            ->pluck('id')
    )
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

echo "Records found: " . count($results) . "\n";
foreach($results as $r) {
    echo json_encode((array)$r, JSON_UNESCAPED_UNICODE) . "\n";
}
