<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ARREGLANDO IMÁGENES FALTANTES ===\n\n";

// Obtener todos los pedidos de la cotización 150 que no tienen imágenes
$pedidos = \App\Models\PedidoProduccion::where('cotizacion_id', 150)
    ->orderBy('id', 'desc')
    ->get();

echo "Pedidos encontrados de cotización 150: " . $pedidos->count() . "\n\n";

$service = app(\App\Application\Services\CopiarImagenesCotizacionAPedidoService::class);

foreach ($pedidos as $pedido) {
    echo "Procesando pedido #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
    
    // Verificar si ya tiene imágenes
    $prendas = \App\Models\PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();
    $totalImagenes = 0;
    
    foreach ($prendas as $prenda) {
        $totalImagenes += \App\Models\PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->count();
        $totalImagenes += \App\Models\PrendaFotoTelaPedido::where('prenda_pedido_id', $prenda->id)->count();
        $totalImagenes += \App\Models\PrendaFotoLogoPedido::where('prenda_pedido_id', $prenda->id)->count();
    }
    
    if ($totalImagenes > 0) {
        echo "  ✅ Ya tiene {$totalImagenes} imágenes, omitiendo...\n";
        continue;
    }
    
    echo "  📸 No tiene imágenes, copiando...\n";
    
    try {
        $service->copiarImagenesCotizacionAPedido(150, $pedido->id);
        echo "  ✅ Imágenes copiadas exitosamente\n";
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
