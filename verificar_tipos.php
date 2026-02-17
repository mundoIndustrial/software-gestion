<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICANDO TODOS LOS TIPOS DE PROCESOS ===\n";

// Verificar todos los tipos de procesos existentes
$tipos = \DB::table('pedidos_procesos_prenda_detalles')
    ->select('tipo_recibo', \DB::raw('count(*) as total'))
    ->groupBy('tipo_recibo')
    ->orderBy('total', 'desc')
    ->get();

echo "Todos los tipos de procesos encontrados:\n";
foreach ($tipos as $tipo) {
    echo "- {$tipo->tipo_recibo}: {$tipo->total} procesos\n";
}

echo "\n=== VERIFICANDO ESTADOS POR TIPO ===\n";

// Verificar estados para cada tipo
foreach ($tipos as $tipo) {
    echo "\nTipo: {$tipo->tipo_recibo}\n";
    $estados = \DB::table('pedidos_procesos_prenda_detalles')
        ->where('tipo_recibo', $tipo->tipo_recibo)
        ->select('estado', \DB::raw('count(*) as total'))
        ->groupBy('estado')
        ->get();
    
    foreach ($estados as $estado) {
        echo "  - {$estado->estado}: {$estado->total}\n";
    }
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
