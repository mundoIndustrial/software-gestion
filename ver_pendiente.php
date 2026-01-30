<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "Verificación del pedido PENDIENTE_SUPERVISOR:\n";
echo "============================================\n";

$pedido = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
    ->with(['asesora', 'prendas', 'cotizacion'])
    ->first();

if ($pedido) {
    echo "Pedido encontrado:\n";
    echo "- ID: {$pedido->id}\n";
    echo "- Número: #{$pedido->numero_pedido}\n";
    echo "- Cliente: {$pedido->cliente}\n";
    echo "- Estado: '{$pedido->estado}'\n";
    echo "- Asesora: " . ($pedido->asesora?->name ?? 'N/A') . "\n";
    echo "- Fecha: {$pedido->created_at}\n";
    
    echo "\n¿Debería mostrar el botón Aprobar?\n";
    $debeMostrarBoton = $pedido->estado === 'PENDIENTE_SUPERVISOR';
    echo "- Respuesta: " . ($debeMostrarBoton ? 'SÍ' : 'NO') . "\n";
    
    if ($debeMostrarBoton) {
        echo "\n✅ Este pedido DEBERÍA mostrar el botón Aprobar en la vista.\n";
        echo "📍 URL para ver este pedido: http://192.168.0.172:8000/supervisor-pedidos?aprobacion=pendiente\n";
    }
} else {
    echo "❌ No se encontró ningún pedido con estado PENDIENTE_SUPERVISOR\n";
}

echo "\nURLs recomendadas:\n";
echo "==================\n";
echo "- Ver todos los pedidos: http://192.168.0.172:8000/supervisor-pedidos\n";
echo "- Ver SOLO pendientes de aprobación: http://192.168.0.172:8000/supervisor-pedidos?aprobacion=pendiente\n";
echo "- Ver aprobados: http://192.168.0.172:8000/supervisor-pedidos?aprobacion=aprobadas\n";
