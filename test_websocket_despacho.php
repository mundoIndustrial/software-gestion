<?php

// Script de prueba para verificar WebSockets en despacho
require_once 'vendor/autoload.php';

use App\Models\PedidoProduccion;
use App\Models\User;
use App\Events\PedidoActualizado;
use Illuminate\Support\Facades\Log;

// Inicializar Laravel (básico)
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Script de prueba para WebSockets en despacho\n";
echo "=============================================\n\n";

try {
    // Buscar un pedido de prueba
    $pedido = PedidoProduccion::find(1);
    
    if (!$pedido) {
        echo "❌ No se encontró el pedido con ID 1\n";
        exit(1);
    }
    
    echo "✅ Pedido encontrado:\n";
    echo "   ID: {$pedido->id}\n";
    echo "   Número: {$pedido->numero_pedido}\n";
    echo "   Cliente: {$pedido->cliente}\n";
    echo "   Estado actual: {$pedido->estado}\n\n";
    
    // Buscar un asesor
    $asesor = User::find($pedido->asesor_id);
    
    if (!$asesor) {
        echo "❌ No se encontró el asesor del pedido\n";
        exit(1);
    }
    
    echo "✅ Asesor encontrado:\n";
    echo "   ID: {$asesor->id}\n";
    echo "   Nombre: {$asesor->name}\n\n";
    
    // Simular cambios
    $changedFields = [
        'estado' => [
            'old' => $pedido->estado,
            'new' => 'No iniciado'
        ],
        'novedades' => [
            'old' => $pedido->novedades,
            'new' => 'Prueba de WebSocket ' . date('H:i:s')
        ]
    ];
    
    echo "🔄 Simulando cambios:\n";
    echo "   Estado: {$changedFields['estado']['old']} → {$changedFields['estado']['new']}\n";
    echo "   Novedades: '{$changedFields['novedades']['old']}' → '{$changedFields['novedades']['new']}'\n\n";
    
    // Emitir el evento
    echo "📡 Emitiendo evento PedidoActualizado...\n";
    
    $event = new PedidoActualizado($pedido, $asesor, $changedFields, 'updated');
    
    // Forzar el broadcast
    event($event);
    
    echo "✅ Evento emitido exitosamente\n";
    echo "📋 Canales de broadcast:\n";
    foreach ($event->broadcastOn() as $channel) {
        echo "   - " . get_class($channel) . ": " . $channel->name . "\n";
    }
    echo "\n";
    
    echo "📦 Datos del evento:\n";
    $data = $event->broadcastWith();
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            echo "   $key: [array]\n";
        } else {
            echo "   $key: $value\n";
        }
    }
    echo "\n";
    
    echo "🎯 Nombre del evento: " . $event->broadcastAs() . "\n\n";
    
    echo "✅ Prueba completada. Revisa la consola de JavaScript en:\n";
    echo "   https://sistemamundoindustrial.online/despacho/pendientes\n\n";
    
} catch (Exception $e) {
    echo "❌ Error en la prueba: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
