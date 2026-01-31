<?php

/**
 * Script para probar la generación de consecutivos
 * Ejecutar: php test-consecutivos.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Services\ConsecutivosRecibosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🧪 Probando generación de consecutivos...\n\n";

try {
    // 1. Buscar un pedido en estado PENDIENTE_SUPERVISOR
    $pedido = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
        ->first();

    if (!$pedido) {
        echo "❌ No hay pedidos en estado PENDIENTE_SUPERVISOR\n";
        echo "📋 Estados disponibles:\n";
        $estados = PedidoProduccion::distinct()->pluck('estado');
        foreach ($estados as $estado) {
            echo "   - $estado\n";
        }
        exit(1);
    }

    echo "📦 Pedido encontrado:\n";
    echo "   ID: {$pedido->id}\n";
    echo "   Número: " . ($pedido->numero_pedido ?? 'SIN NÚMERO') . "\n";
    echo "   Cliente: {$pedido->cliente}\n";
    echo "   Estado actual: {$pedido->estado}\n\n";

    // 2. Verificar si ya tiene consecutivos
    $consecutivosExistentes = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedido->id)
        ->count();

    if ($consecutivosExistentes > 0) {
        echo "⚠️  El pedido ya tiene {$consecutivosExistentes} consecutivos generados\n";
        echo "🔍 Mostrando consecutivos existentes:\n";
        
        $existentes = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedido->id)
            ->get();
            
        foreach ($existentes as $cons) {
            echo "   - {$cons->tipo_recibo}: {$cons->consecutivo_actual}\n";
        }
        echo "\n";
    }

    // 3. Simular el cambio de estado
    echo "🔄 Simulando cambio de estado: PENDIENTE_SUPERVISOR → PENDIENTE_INSUMOS\n";
    
    $estadoAnterior = $pedido->estado;
    $estadoNuevo = 'PENDIENTE_INSUMOS';
    
    // 4. Probar el servicio directamente
    $service = new ConsecutivosRecibosService();
    $resultado = $service->generarConsecutivosSiAplica($pedido, $estadoAnterior, $estadoNuevo);
    
    if ($resultado) {
        echo "✅ Consecutivos generados exitosamente\n";
        
        // Mostrar los consecutivos generados
        $nuevosConsecutivos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedido->id)
            ->get();
            
        echo "📋 Consecutivos del pedido:\n";
        foreach ($nuevosConsecutivos as $cons) {
            echo "   - {$cons->tipo_recibo}: {$cons->consecutivo_actual} (inicial: {$cons->consecutivo_inicial})\n";
            echo "     Notas: {$cons->notas}\n";
        }
        
        // Actualizar realmente el estado del pedido
        $pedido->update(['estado' => $estadoNuevo]);
        echo " Estado del pedido actualizado a: {$estadoNuevo}\n";
        
    } else {
        echo "ℹ️  No se generaron consecutivos (revisar logs para más detalles)\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    echo "📁 Archivo: " . $e->getFile() . "\n";
}

echo "\n🏁 Fin de la prueba\n";
