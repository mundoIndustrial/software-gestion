<?php

// Test script para verificar que el audit trail funciona correctamente
// Usar con: php artisan tinker --execute="include 'test-audit-trail.php'"

use App\Models\PedidoAuditoria;
use App\Services\PedidoAuditoriaService;

// Test 1: Verificar que un pedido fijo existe
$pedidoId = 1; // Usar un ID que sabes que existe

echo "=== TEST 1: Registrar cambio genérico ===\n";
try {
    $result = PedidoAuditoriaService::registrarCambio(
        pedidoId: $pedidoId,
        tipoCambio: 'PRUEBA_CONEXION',
        observaciones: 'Test de audit trail - conexión OK'
    );
    echo "✓ Cambio genérico registrado: ID = {$result->id}\n";
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

echo "\n=== TEST 2: Registrar cambio de imagen en prenda ===\n";
try {
    $result = PedidoAuditoriaService::registrarImagenPrendaAgregada(
        pedidoId: $pedidoId,
        prendalPedidoId: 1,
        imagenId: null,
        rutaImagen: '/storage/prueba/imagen.jpg'
    );
    echo "✓ Cambio de imagen prenda registrado: ID = {$result->id}\n";
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

echo "\n=== TEST 3: Registrar cambio de imagen en proceso ===\n";
try {
    $result = PedidoAuditoriaService::registrarImagenProcesoAgregada(
        pedidoId: $pedidoId,
        procesoPrendaDetalleId: 1,
        imagenId: null,
        rutaImagen: '/storage/prueba/proceso.jpg'
    );
    echo "✓ Cambio de imagen proceso registrado: ID = {$result->id}\n";
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

echo "\n=== TEST 4: Verificar cambios registrados ===\n";
try {
    $cambios = PedidoAuditoria::where('pedidos_produccion_id', $pedidoId)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    echo "✓ Se encontraron " . count($cambios) . " cambios recientes:\n";
    foreach ($cambios as $cambio) {
        echo "   - {$cambio->tipo_cambio} ({$cambio->created_at})\n";
    }
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

echo "\n✓ TEST COMPLETADO\n";
