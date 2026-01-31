<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Services\ConsecutivosRecibosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    $pedidoId = 2;
    
    echo "=== GENERANDO CONSECUTIVOS PARA PEDIDO $pedidoId ===\n";
    
    // Obtener el pedido
    $pedido = PedidoProduccion::find($pedidoId);
    if (!$pedido) {
        echo "❌ Pedido $pedidoId no encontrado\n";
        exit(1);
    }
    
    echo "📋 Pedido: {$pedido->numero_pedido}\n";
    echo "🔄 Estado actual: {$pedido->estado}\n";
    
    // Obtener usuario real con rol supervisor
    $user = \App\Models\User::where('email', 'yus22@gmail.com')->first();
    if (!$user) {
        echo "❌ Usuario supervisor no encontrado\n";
        exit(1);
    }
    
    // Forzar autenticación
    auth()->login($user);
    
    // Simular cambio de estado para forzar generación
    $estadoAnterior = 'PENDIENTE_SUPERVISOR';
    $estadoNuevo = 'PENDIENTE_INSUMOS';
    
    echo "🔄 Simulando cambio de estado:\n";
    echo "   De: $estadoAnterior\n";
    echo "   A: $estadoNuevo\n";
    
    // Generar consecutivos
    $service = new ConsecutivosRecibosService();
    $resultado = $service->generarConsecutivosSiAplica($pedido, $estadoAnterior, $estadoNuevo);
    
    if ($resultado) {
        echo "✅ Consecutivos generados exitosamente\n";
        
        // Verificar consecutivos creados
        $consecutivos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('activo', 1)
            ->get();
            
        echo "📊 Total consecutivos creados: {$consecutivos->count()}\n";
        
        foreach ($consecutivos as $cons) {
            echo "   - {$cons->tipo_recibo}: {$cons->consecutivo_actual}\n";
        }
        
    } else {
        echo "❌ No se generaron consecutivos\n";
        echo "   Revisa los logs para más detalles\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
}
