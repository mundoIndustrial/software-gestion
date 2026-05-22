<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function out($title, $rows) {
    echo "\n=== $title ===\n";
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

$rows1 = Illuminate\Support\Facades\DB::select("SELECT id,pedido_produccion_id,prenda_id,prenda_bodega_id,tipo_recibo,activo,area,estado,consecutivo_actual,created_at FROM consecutivos_recibos_pedidos WHERE UPPER(TRIM(tipo_recibo))='CORTE-PARA-BODEGA' AND activo=1 ORDER BY id DESC LIMIT 50");
out('consecutivos CORTE-PARA-BODEGA activos', $rows1);

$rows2 = Illuminate\Support\Facades\DB::select("SELECT id,numero_pedido,prenda_pedido_id,prenda_bodega_id,numero_recibo,numero_recibo_parcial,proceso,encargado,fecha_de_asignacion_encargado,created_at FROM procesos_prenda WHERE LOWER(TRIM(proceso))='costura' AND LOWER(TRIM(encargado))='modulo 5' ORDER BY id DESC LIMIT 50");
out('procesos_prenda costura encargado MODULO 5', $rows2);

$rows3 = Illuminate\Support\Facades\DB::select("SELECT c.id,c.pedido_produccion_id,c.prenda_bodega_id,c.consecutivo_actual,c.area,c.estado,c.activo,c.created_at,p.id AS proceso_id,p.proceso,p.encargado,p.numero_recibo,p.numero_recibo_parcial,p.fecha_de_asignacion_encargado,p.created_at AS proceso_created_at FROM consecutivos_recibos_pedidos c LEFT JOIN procesos_prenda p ON p.prenda_bodega_id=c.prenda_bodega_id AND LOWER(TRIM(p.proceso))='costura' WHERE UPPER(TRIM(c.tipo_recibo))='CORTE-PARA-BODEGA' AND c.activo=1 ORDER BY c.id DESC, p.id DESC LIMIT 120");
out('join recibos bodega con proceso costura por prenda_bodega_id', $rows3);
