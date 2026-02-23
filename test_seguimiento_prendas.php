<?php
// Script de prueba para verificar los cambios en seguimiento de prendas
require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\ConsecutivosRecibosPedidos;

echo "=== PRUEBA DE TIPOS DE RECIBO DE PROCESOS ===\n\n";

// Buscar un pedido con prendas
$pedido = PedidoProduccion::with(['prendas'])->first();
if (!$pedido) {
    echo "No se encontraron pedidos con prendas.\n";
    exit(1);
}

echo "Pedido encontrado: {$pedido->numero_pedido} (ID: {$pedido->id})\n";
echo "Total de prendas: " . $pedido->prendas->count() . "\n\n";

foreach ($pedido->prendas as $index => $prenda) {
    echo "--- PRENDA " . ($index + 1) . " ---\n";
    echo "ID: {$prenda->id}\n";
    echo "Nombre: {$prenda->nombre_prenda}\n";
    echo "Cantidad: {$prenda->cantidad_total}\n";
    
    // Buscar todos los consecutivos activos para esta prenda
    $consecutivos = ConsecutivosRecibosPedidos::where('prenda_id', $prenda->id)
        ->where('pedido_produccion_id', $pedido->id)
        ->where('activo', 1)
        ->get();
    
    echo "Total de consecutivos activos: " . $consecutivos->count() . "\n";
    
    // Filtrar solo los tipos que son procesos
    $tiposProcesoValidos = ['ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO'];
    $tiposReciboProcesos = [];
    
    foreach ($consecutivos as $consecutivo) {
        echo "  - {$consecutivo->tipo_recibo} #{$consecutivo->consecutivo_actual} (Estado: {$consecutivo->estado})\n";
        
        if (in_array($consecutivo->tipo_recibo, $tiposProcesoValidos)) {
            $tiposReciboProcesos[] = [
                'nombre' => $consecutivo->tipo_recibo,
                'estado' => $consecutivo->estado ?? 'PENDIENTE'
            ];
        }
    }
    
    echo "\nTipos de recibo que son PROCESOS:\n";
    if (empty($tiposReciboProcesos)) {
        echo "  (Sin procesos)\n";
    } else {
        foreach ($tiposReciboProcesos as $proceso) {
            echo "  - {$proceso['nombre']} ({$proceso['estado']})\n";
        }
        
        // Mostrar cómo se vería en la tabla
        $procesosInfo = implode(', ', array_map(function($p) {
            return "{$p['nombre']} ({$p['estado']})";
        }, $tiposReciboProcesos));
        echo "\nEn la tabla se mostraría: {$procesosInfo}\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== FIN DE PRUEBA ===\n";
