<?php
/**
 * Script de prueba rápida para verificar que MapeoImagenesService 
 * ahora encuentra procesos correctamente por tipo de proceso
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcessImagenes;

// Buscar el último pedido creado
$pedidoReciente = PedidoProduccion::latest('id')->first();

if (!$pedidoReciente) {
    echo "No hay pedidos creados\n";
    exit(1);
}

echo "✅ Último pedido: #{$pedidoReciente->numero_pedido} (ID: {$pedidoReciente->id})\n\n";

// Buscar prendas del pedido
$prendas = $pedidoReciente->prendas;
echo "📦 Prendas del pedido: " . $prendas->count() . "\n";

foreach ($prendas as $prenda) {
    echo "\n  Prenda: {$prenda->nombre} (ID: {$prenda->id})\n";
    
    // Buscar procesos de la prenda
    $procesos = $prenda->procesos;
    echo "    Procesos: " . $procesos->count() . "\n";
    
    foreach ($procesos as $proceso) {
        echo "    - Tipo: {$proceso->tipoProceso->nombre} (ID: {$proceso->id})\n";
        echo "      datos_adicionales tipo: " . gettype($proceso->datos_adicionales) . "\n";
        if (is_array($proceso->datos_adicionales)) {
            echo "      UID en datos_adicionales: " . ($proceso->datos_adicionales['uid'] ?? 'NO ENCONTRADO') . "\n";
        }
        
        // Buscar imágenes del proceso
        $imagenes = $proceso->imagenes;
        echo "      🖼️  Imágenes: " . $imagenes->count() . "\n";
        if ($imagenes->count() > 0) {
            foreach ($imagenes as $imagen) {
                echo "        - {$imagen->ruta_webp}\n";
            }
        } else {
            echo "        ❌ SIN IMÁGENES (¡Este es el problema!)\n";
        }
    }
}

echo "\n✅ Test completado\n";
