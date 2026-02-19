<?php

/**
 * Script de prueba para simular evento OrdenUpdated
 * Usar para probar WebSocket desde supervisor-pedidos
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Events\OrdenUpdated;
use Illuminate\Support\Facades\Log;

// Simular datos de una orden
$ordenData = [
    'id' => 99999,
    'numero_pedido' => 'WS-TEST-' . time(),
    'cliente' => 'Cliente WebSocket Test',
    'estado' => 'Aprobado',
    'novedades' => 'Probando WebSocket desde supervisor-pedidos',
    'created_at' => now(),
    'updated_at' => now(),
];

// Crear objeto simulado (no es un Eloquent model pero funciona para el test)
$orden = (object) $ordenData;

Log::info('[TEST] Simulando evento OrdenUpdated', [
    'numero_pedido' => $orden->numero_pedido,
    'estado' => $orden->estado
]);

try {
    // Disparar evento
    event(new OrdenUpdated($orden, 'created', ['numero_pedido', 'estado']));
    
    Log::info('[TEST] Evento OrdenUpdated disparado correctamente', [
        'numero_pedido' => $orden->numero_pedido
    ]);
    
    echo "✅ Evento OrdenUpdated disparado correctamente\n";
    echo "📋 Pedido: {$orden->numero_pedido}\n";
    echo "🔄 Acción: created\n";
    echo "📡 Canales: supervisor-pedidos, ordenes\n";
    
} catch (Exception $e) {
    Log::error('[TEST] Error al disparar evento', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n📝 Revisa los logs de Laravel para más detalles\n";
echo "🌐 Abre /websocket-test-supervisor.html para probar la recepción\n";
