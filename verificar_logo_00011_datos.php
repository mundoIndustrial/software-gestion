<?php
/**
 * Script de Verificación Rápida - LOGO-00011
 * Verifica si PedidoProduccion 11399 tiene los datos que deberían llenar a LogoPedido
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN: Datos para llenar LOGO-00011                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Iniciar Laravel
require __DIR__ . '/bootstrap/app.php';
$app = require __DIR__ . '/bootstrap/app.php';

// Hacer que inicie la aplicación
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // 1. Buscar LogoPedido
    echo "📌 PASO 1: Verificar LogoPedido LOGO-00011\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $logoPedido = \App\Models\LogoPedido::where('numero_pedido', 'LOGO-00011')->first();
    
    if ($logoPedido) {
        echo "✅ LogoPedido encontrado (ID: {$logoPedido->id})\n";
        echo "   - cliente en BD: '{$logoPedido->cliente}'\n";
        echo "   - asesora en BD: '{$logoPedido->asesora}'\n";
        echo "   - descripcion en BD: '{$logoPedido->descripcion}'\n";
        echo "   - fecha_de_creacion_de_orden en BD: '{$logoPedido->fecha_de_creacion_de_orden}'\n";
        echo "   - created_at en BD: '{$logoPedido->created_at}'\n";
        echo "   - pedido_id: {$logoPedido->pedido_id}\n";
        echo "   - logo_cotizacion_id: {$logoPedido->logo_cotizacion_id}\n";
    } else {
        echo "❌ LogoPedido NO encontrado\n";
        exit(1);
    }
    
    // 2. Buscar PedidoProduccion relacionado
    echo "\n📌 PASO 2: Verificar PedidoProduccion {$logoPedido->pedido_id}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($logoPedido->pedido_id) {
        $pedido = \App\Models\PedidoProduccion::with('asesora')->find($logoPedido->pedido_id);
        
        if ($pedido) {
            echo "✅ PedidoProduccion encontrado (ID: {$pedido->id})\n";
            echo "   - numero_pedido: '{$pedido->numero_pedido}'\n";
            echo "   - cliente: '{$pedido->cliente}'\n";
            echo "   - asesor_id: {$pedido->asesor_id}\n";
            
            if ($pedido->asesora) {
                echo "   - asesora->name: '{$pedido->asesora->name}'\n";
            } else {
                echo "   - asesora: NO ENCONTRADA\n";
            }
            
            echo "   - forma_de_pago: '{$pedido->forma_de_pago}'\n";
            echo "   - fecha_de_creacion_de_orden: '{$pedido->fecha_de_creacion_de_orden}'\n";
            echo "   - descripcion_prendas: " . (strlen($pedido->descripcion_prendas ?? '') > 50 ? "✅ Presente" : "❌ Vacía") . "\n";
            
            // Verificar si esto completaría LogoPedido
            echo "\n📊 RESULTADO: Con esta información se completaría:\n";
            echo "   ✅ cliente: '{$pedido->cliente}'\n";
            echo "   ✅ asesora: '" . ($pedido->asesora?->name ?? 'NO DISPONIBLE') . "'\n";
            echo "   ✅ fecha_de_creacion_de_orden: '{$pedido->fecha_de_creacion_de_orden}'\n";
            
        } else {
            echo "❌ PedidoProduccion NO encontrado\n";
            echo "   Intentando usar fecha de created_at...\n";
        }
    } else {
        echo "⚠️  LogoPedido no tiene pedido_id, buscando en LogoCotizacion...\n";
    }
    
    // 3. Buscar LogoCotizacion como fallback
    if ($logoPedido->logo_cotizacion_id) {
        echo "\n📌 PASO 3: Verificar LogoCotizacion {$logoPedido->logo_cotizacion_id}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $logoCot = \App\Models\LogoCotizacion::with('cotizacion')->find($logoPedido->logo_cotizacion_id);
        
        if ($logoCot && $logoCot->cotizacion) {
            echo "✅ LogoCotizacion encontrada\n";
            echo "   - cliente: '{$logoCot->cotizacion->cliente}'\n";
            echo "   - fecha_de_creacion: '{$logoCot->cotizacion->fecha_de_creacion}'\n";
            
            if ($logoCot->cotizacion->asesor) {
                echo "   - asesor->name: '{$logoCot->cotizacion->asesor->name}'\n";
            }
        } else {
            echo "❌ LogoCotizacion NO encontrada o sin cotización\n";
        }
    }
    
    // 4. Resumen final
    echo "\n📊 RESUMEN FINAL\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $completado = [
        'cliente' => !empty($pedido->cliente) ? "✅ {$pedido->cliente}" : "❌ NO DISPONIBLE",
        'asesora' => !empty($pedido->asesora?->name) ? "✅ {$pedido->asesora->name}" : "❌ NO DISPONIBLE",
        'descripcion' => !empty($pedido->descripcion_prendas) ? "✅ Presente" : "❌ NO DISPONIBLE",
        'fecha' => !empty($pedido->fecha_de_creacion_de_orden) ? "✅ {$pedido->fecha_de_creacion_de_orden}" : "⚠️  Usar created_at: {$logoPedido->created_at}"
    ];
    
    foreach ($completado as $campo => $estado) {
        echo "$campo: $estado\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
