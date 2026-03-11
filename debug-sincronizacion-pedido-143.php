<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Pedido 143 - Verificación de Sincronización ===" . PHP_EOL;

// 1. Obtener datos originales del pedido desde ObtenerPedidoUseCase
try {
    $obtenerPedidoUseCase = app(\App\Application\Bodega\UseCases\ObtenerPedidoUseCase::class);
    $datosOriginales = $obtenerPedidoUseCase->ejecutar(143);
    
    echo PHP_EOL . "DATOS ORIGINALES desde ObtenerPedidoUseCase:" . PHP_EOL;
    
    if (isset($datosOriginales->prendas) && is_array($datosOriginales->prendas)) {
        foreach ($datosOriginales->prendas as $prenda) {
            echo "Prenda: " . ($prenda['nombre'] ?? 'N/A') . PHP_EOL;
            
            $variantes = $prenda['variantes'] ?? [];
            foreach ($variantes as $variante) {
                $coloresDetalle = $variante['colores_detalle'] ?? [];
                foreach ($coloresDetalle as $color) {
                    $talla = $color['talla'] ?? 'N/A';
                    $genero = $color['genero'] ?? 'N/A';
                    $cantidad = $color['cantidad'] ?? 0;
                    echo "  - Talla: {$talla} | Género: {$genero} | Cantidad: {$cantidad}" . PHP_EOL;
                }
            }
        }
    }
    
    if (isset($datosOriginales->epps) && is_array($datosOriginales->epps)) {
        echo PHP_EOL . "EPPs:" . PHP_EOL;
        foreach ($datosOriginales->epps as $epp) {
            echo "  - EPP: " . ($epp['nombre'] ?? 'N/A') . " | Cantidad: " . ($epp['cantidad'] ?? 0) . PHP_EOL;
        }
    }
    
} catch (\Exception $e) {
    echo "Error al obtener datos originales: " . $e->getMessage() . PHP_EOL;
}

// 2. Comparar con bodega_detalles_talla
echo PHP_EOL . "COMPARACIÓN con bodega_detalles_talla:" . PHP_EOL;
$detallesBodega = DB::table('bodega_detalles_talla')
    ->where('numero_pedido', '143')
    ->get();

foreach ($detallesBodega as $detalle) {
    echo "Bodega - Talla: {$detalle->talla} | Género: {$detalle->genero} | Estado: {$detalle->estado_bodega}" . PHP_EOL;
}

echo PHP_EOL . "=== CONCLUSIÓN ===" . PHP_EOL;
echo "Si hay más items en los datos originales que en bodega_detalles_talla," . PHP_EOL;
echo "entonces faltan sincronizar los datos." . PHP_EOL;
