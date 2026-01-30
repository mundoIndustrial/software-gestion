use App\Models\PedidoProduccion;
use App\Services\ConsecutivosRecibosService;

echo "🧪 Probando generación de consecutivos...\n";

try {
    // 1. Buscar un pedido en estado PENDIENTE_SUPERVISOR
    $pedido = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')->first();

    if (!$pedido) {
        echo "❌ No hay pedidos en estado PENDIENTE_SUPERVISOR\n";
        echo "📋 Estados disponibles:\n";
        $estados = PedidoProduccion::distinct()->pluck('estado');
        foreach ($estados as $estado) {
            echo "   - $estado\n";
        }
        exit(0);
    }

    echo "📦 Pedido encontrado:\n";
    echo "   ID: {$pedido->id}\n";
    echo "   Número: " . ($pedido->numero_pedido ?? 'SIN NÚMERO') . "\n";
    echo "   Cliente: {$pedido->cliente}\n";
    echo "   Estado actual: {$pedido->estado}\n";

    // 2. Verificar si ya tiene consecutivos
    $consecutivosExistentes = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedido->id)
        ->count();

    if ($consecutivosExistentes > 0) {
        echo "⚠️  El pedido ya tiene {$consecutivosExistentes} consecutivos generados\n";
    }

    // 3. Probar el servicio directamente
    $service = new ConsecutivosRecibosService();
    $resultado = $service->generarConsecutivosSiAplica($pedido, 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS');
    
    if ($resultado) {
        echo "✅ Consecutivos generados exitosamente\n";
        
        // Mostrar los consecutivos generados
        $nuevosConsecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedido->id)
            ->get();
            
        echo "📋 Consecutivos del pedido:\n";
        foreach ($nuevosConsecutivos as $cons) {
            echo "   - {$cons->tipo_recibo}: {$cons->consecutivo_actual}\n";
        }
        
    } else {
        echo "ℹ️  No se generaron consecutivos\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}

echo "\n🏁 Fin de la prueba\n";
