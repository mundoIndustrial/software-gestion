<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICANDO ESTRUCTURA DE TABLA ===\n";

// Verificar estructura de la tabla
$columnas = \DB::select("DESCRIBE pedidos_procesos_prenda_detalles");
echo "Columnas de la tabla:\n";
foreach ($columnas as $col) {
    echo "- {$col->Field}: {$col->Type}\n";
}

echo "\n=== VERIFICANDO DATOS EXISTENTES ===\n";

// Verificar todos los datos existentes
$datos = \DB::table('pedidos_procesos_prenda_detalles')
    ->limit(5)
    ->get(['id', 'tipo_recibo', 'estado', 'numero_recibo', 'prenda_pedido_id']);

echo "Primeros 5 registros:\n";
foreach ($datos as $d) {
    echo "ID: {$d->id} - Tipo: '{$d->tipo_recibo}' - Estado: {$d->estado} - Recibo: '{$d->numero_recibo}' - Prenda: {$d->prenda_pedido_id}\n";
}

echo "\n=== VERIFICANDO TABLA CONSECUTIVOS ===\n";

// Verificar si hay datos en consecutivos_recibos_pedidos
$consecutivos = \DB::table('consecutivos_recibos_pedidos')
    ->select('tipo_recibo', \DB::raw('count(*) as total'))
    ->groupBy('tipo_recibo')
    ->get();

echo "Tipos en consecutivos_recibos_pedidos:\n";
foreach ($consecutivos as $c) {
    echo "- {$c->tipo_recibo}: {$c->total}\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
