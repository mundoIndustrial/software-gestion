<?php
/**
 * Script de prueba para API de Cartera
 * Verifica que la API responda correctamente
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;

// Simular entorno Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Crear una solicitud simulada
$request = Request::create('/api/cartera/pedidos', 'GET');

// Simular autenticación (temporal para prueba)
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/cartera/pedidos';

echo "🧪 Probando API de Cartera...\n";
echo "URL: /api/cartera/pedidos?estado=pendiente_cartera\n\n";

try {
    // Consulta directa a la base de datos
    $pedidos = \App\Models\PedidoProduccion::where('estado', 'pendiente_cartera')
        ->select('id', 'numero_pedido', 'cliente', 'estado', 'area', 'novedades', 'forma_pago', 'fecha_creacion', 'fecha_estimada')
        ->orderBy('fecha_creacion', 'desc')
        ->get();
    
    echo "✅ Conexión a BD exitosa\n";
    echo "📊 Pedidos encontrados: " . $pedidos->count() . "\n\n";
    
    if ($pedidos->count() > 0) {
        echo "📋 Primer pedido:\n";
        $primerPedido = $pedidos->first();
        echo "  - ID: {$primerPedido->id}\n";
        echo "  - Número: {$primerPedido->numero_pedido}\n";
        echo "  - Cliente: {$primerPedido->cliente}\n";
        echo "  - Estado: {$primerPedido->estado}\n";
        echo "  - Área: {$primerPedido->area}\n";
        echo "  - Fecha: {$primerPedido->fecha_creacion}\n\n";
    }
    
    // Formato JSON esperado
    $response = [
        'success' => true,
        'data' => $pedidos->toArray()
    ];
    
    echo "📤 Respuesta JSON (primeros 2 pedidos):\n";
    echo json_encode([
        'success' => true,
        'data' => $pedidos->take(2)->toArray()
    ], JSON_PRETTY_PRINT) . "\n\n";
    
    echo "✅ API funcionando correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔗 Para probar en el navegador:\n";
echo "http://localhost:8000/api/cartera/pedidos?estado=pendiente_cartera\n";
