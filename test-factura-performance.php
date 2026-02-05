<?php
/**
 * Script de prueba para medir el rendimiento de obtenerDatosFactura
 * Ejecutar desde la terminal: php artisan tinker
 * Luego: include('test-factura-performance.php')
 */

use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;

// ID del pedido a probar (cambiar según sea necesario)
$pedidoId = 4;

$repo = app(PedidoProduccionRepository::class);

echo "\n=== PRUEBA DE RENDIMIENTO obtenerDatosFactura ===\n";
echo "Pedido ID: $pedidoId\n";
echo "Inicio: " . date('Y-m-d H:i:s.u') . "\n";

$inicio = microtime(true);

try {
    $datos = $repo->obtenerDatosFactura($pedidoId);
    
    $duracion = round((microtime(true) - $inicio) * 1000, 2);
    
    echo "Fin: " . date('Y-m-d H:i:s.u') . "\n";
    echo "Duración TOTAL: {$duracion} ms\n";
    echo "\nResultado:\n";
    echo "- Prendas: " . count($datos['prendas'] ?? []) . "\n";
    echo "- EPPs: " . count($datos['epps'] ?? []) . "\n";
    echo "- Total items: " . ($datos['total_items'] ?? 0) . "\n";
    
    if (count($datos['prendas'] ?? []) > 0) {
        echo "\nPrenda 1:\n";
        $prenda = $datos['prendas'][0];
        echo "  Nombre: " . ($prenda['nombre'] ?? 'N/A') . "\n";
        echo "  Telas: " . ($prenda['tela'] ?? 'N/A') . "\n";
        echo "  Procesos: " . count($prenda['procesos'] ?? []) . "\n";
    }
    
    echo "\n✅ Success!\n";
} catch (\Exception $e) {
    $duracion = round((microtime(true) - $inicio) * 1000, 2);
    echo "❌ Error después de {$duracion}ms:\n";
    echo $e->getMessage() . "\n";
}
