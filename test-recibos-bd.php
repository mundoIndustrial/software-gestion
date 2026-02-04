<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== Verificando COSTURA-BODEGA en BD ===\n\n";
    
    // Verificar que el consecutivo está en la BD
    echo "1. Consecutivos COSTURA-BODEGA en BD:\n";
    
    $costuraBodega = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->where('activo', 1)
        ->get();
    
    if ($costuraBodega->count() > 0) {
        echo "   ✓ Encontrados: " . $costuraBodega->count() . " registros\n";
        foreach ($costuraBodega as $cb) {
            echo "     - Pedido ID {$cb->pedido_produccion_id}: Consecutivo {$cb->consecutivo_actual}\n";
        }
    } else {
        echo "   ✗ No hay registros COSTURA-BODEGA\n";
    }
    
    // Verificar que en obtenerConsecutivosPrenda para pedido 1, prenda 1 incluye COSTURA-BODEGA
    echo "\n2. Simulando obtenerConsecutivosPrenda para pedido 1, prenda 1:\n";
    
    $pedidoId = 1;
    $prendaId = 1;
    
    $consecutivos = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedidoId)
        ->where('activo', 1)
        ->where(function($query) use ($prendaId) {
            $query->where('prenda_id', $prendaId)
                  ->orWhereNull('prenda_id');
        })
        ->get();
    
    echo "   Registros encontrados: " . $consecutivos->count() . "\n";
    
    foreach ($consecutivos as $c) {
        echo "     - Tipo: {$c->tipo_recibo} | Consecutivo: {$c->consecutivo_actual}\n";
    }
    
    // Estruturar como lo hace el método
    $recibos = [
        'COSTURA' => null,
        'ESTAMPADO' => null,
        'BORDADO' => null,
        'DTF' => null,
        'SUBLIMADO' => null,
        'REFLECTIVO' => null,
        'COSTURA-BODEGA' => null
    ];
    
    foreach ($consecutivos as $consecutivo) {
        $tipo = $consecutivo->tipo_recibo;
        if (array_key_exists($tipo, $recibos)) {
            $recibos[$tipo] = $consecutivo->consecutivo_actual;
        }
    }
    
    echo "\n3. Array de recibos después de procesar:\n";
    foreach ($recibos as $tipo => $valor) {
        $status = $valor !== null ? "✓" : "✗";
        echo "   {$status} {$tipo}: " . ($valor !== null ? $valor : "null") . "\n";
    }
    
    if ($recibos['COSTURA-BODEGA'] !== null) {
        echo "\n✅ COSTURA-BODEGA SERÁ DEVUELTO EN EL ENDPOINT CON VALOR: " . $recibos['COSTURA-BODEGA'] . "\n";
    } else {
        echo "\n✗ COSTURA-BODEGA SERÁ null EN EL ENDPOINT\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
