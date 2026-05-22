<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$ids=Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])->pluck('id');
$rows=Illuminate\Support\Facades\DB::table('prenda_recibo_completado')->whereIn('id_recibo',$ids)->whereRaw("LOWER(TRIM(COALESCE(area, ''))) = 'costura'")->pluck('id_recibo');
echo json_encode(['ids_count'=>$ids->count(),'completados'=>$rows->all()], JSON_PRETTY_PRINT);
