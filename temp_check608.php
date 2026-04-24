<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido=App\Models\PedidoProduccion::find(608);
if(!$pedido){echo "pedido_not_found\n"; exit;}
echo "pedido:{$pedido->id}|estado={$pedido->estado}|numero={$pedido->numero_pedido}\n";
$prendas=App\Models\PrendaPedido::where('pedido_produccion_id',608)->whereNull('deleted_at')->get(['id','nombre_prenda','de_bodega']);
foreach($prendas as $p){echo "prenda:{$p->id}|{$p->nombre_prenda}|de_bodega=".var_export($p->de_bodega,true)."\n";}
