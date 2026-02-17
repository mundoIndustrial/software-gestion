<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICANDO PROCESOS ===\n";

// Verificar todos los procesos de logo
$procesos = \DB::table('pedidos_procesos_prenda_detalles')
    ->whereIn('tipo_recibo', ['BORDADO', 'ESTAMPADO', 'SUBLIMADO', 'DTF'])
    ->get(['tipo_recibo', 'estado', 'numero_recibo', 'id', 'prenda_pedido_id']);

echo "Total de procesos de logo: " . $procesos->count() . "\n";

if ($procesos->count() > 0) {
    echo "\nPrimeros 5 procesos:\n";
    foreach ($procesos->take(5) as $p) {
        echo "- {$p->tipo_recibo} - {$p->estado} - Recibo: {$p->numero_recibo} - ID: {$p->id}\n";
    }
}

// Verificar procesos pendientes
$pendientes = \DB::table('pedidos_procesos_prenda_detalles')
    ->whereIn('tipo_recibo', ['BORDADO', 'ESTAMPADO', 'SUBLIMADO', 'DTF'])
    ->whereIn('estado', ['PENDIENTE', 'EN_REVISION'])
    ->get(['tipo_recibo', 'estado', 'numero_recibo', 'id']);

echo "\nProcesos pendientes: " . $pendientes->count() . "\n";

if ($pendientes->count() > 0) {
    echo "\nProcesos pendientes:\n";
    foreach ($pendientes as $p) {
        echo "- {$p->tipo_recibo} - {$p->estado} - Recibo: {$p->numero_recibo} - ID: {$p->id}\n";
    }
} else {
    echo "\nNo hay procesos pendientes. Verificando todos los estados:\n";
    $todosEstados = \DB::table('pedidos_procesos_prenda_detalles')
        ->whereIn('tipo_recibo', ['BORDADO', 'ESTAMPADO', 'SUBLIMADO', 'DTF'])
        ->select('estado', \DB::raw('count(*) as total'))
        ->groupBy('estado')
        ->get();
    
    foreach ($todosEstados as $estado) {
        echo "- {$estado->estado}: {$estado->total}\n";
    }
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
