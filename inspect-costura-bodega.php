<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Prueba: COSTURA-BODEGA Generado Automáticamente ===\n\n";

    // 1. Ver estado actual de un pedido
    echo "1. Revisando estado de los pedidos...\n";
    
    $pedidos = PedidoProduccion::select('id', 'numero_pedido', 'estado')
        ->limit(5)
        ->get();
    
    foreach ($pedidos as $p) {
        $hasCosturaBodega = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $p->id)
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->exists();
        
        $status = $hasCosturaBodega ? '✓' : '✗';
        echo "   {$status} ID: {$p->id} | Número: {$p->numero_pedido} | Estado: {$p->estado}\n";
    }
    
    // 2. Verificar consecutivo actual de COSTURA-BODEGA
    echo "\n2. Consecutivo COSTURA-BODEGA actual:\n";
    
    $consecutivo = DB::table('consecutivos_recibos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->first();
    
    if ($consecutivo) {
        echo "   ✓ Consecutivo actual: {$consecutivo->consecutivo_actual}\n";
        echo "   - Año: {$consecutivo->año}\n";
        echo "   - Activo: {$consecutivo->activo}\n";
    } else {
        echo "   ✗ No existe consecutivo COSTURA-BODEGA\n";
    }
    
    // 3. Contar registros COSTURA-BODEGA
    echo "\n3. Estadísticas de COSTURA-BODEGA:\n";
    
    $countCosturaBodega = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->count();
    
    $countPedidos = DB::table('pedidos_produccion')->count();
    
    echo "   - COSTURA-BODEGA registros: {$countCosturaBodega}\n";
    echo "   - Total de pedidos: {$countPedidos}\n";
    echo "   - Cobertura: " . number_format(($countCosturaBodega / $countPedidos * 100), 2) . "%\n";
    
    // 4. Mostrar últimos COSTURA-BODEGA creados
    echo "\n4. Últimos COSTURA-BODEGA creados:\n";
    
    $ultimos = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($ultimos as $cb) {
        echo "   - Pedido ID: {$cb->pedido_produccion_id} | Consecutivo: {$cb->consecutivo_actual} | Creado: {$cb->created_at}\n";
    }
    
    echo "\n✓ Inspección completada\n";

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
