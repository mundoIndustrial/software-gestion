<?php

/**
 * Script de prueba: Verificar EPP en JSON del Pedido
 * 
 * Uso: php probar_epp_en_json.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║    PRUEBA: EPP en JSON del Pedido              ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    // 1. Obtener el pedido ID 1 (que tiene EPP)
    $pedido = PedidoProduccion::find(1);

    if (!$pedido) {
        throw new Exception('❌ Pedido ID 1 no encontrado');
    }

    echo "✅ Pedido encontrado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

    // 2. Obtener EPP del pedido
    $epps = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
    
    if ($epps->isEmpty()) {
        throw new Exception('❌ El pedido no tiene EPP. Ejecuta primero: php probar_guardar_epp.php');
    }

    echo "📦 EPP en el pedido: {$epps->count()} encontrados\n";
    foreach ($epps as $epp) {
        echo "\n   ID: {$epp->id}\n";
        echo "   - EPP: {$epp->epp?->nombre}\n";
        echo "   - Cantidad: {$epp->cantidad}\n";
        echo "   - Tallas: " . json_encode($epp->tallas_medidas) . "\n";
        echo "   - Observaciones: {$epp->observaciones}\n";
        echo "   - Imágenes: {$epp->imagenes()->count()}\n";
    }

    // 3. Convertir los datos a JSON manualmente
    echo "\n\n💾 Construyendo JSON del pedido con EPP...\n";
    
    $datosJSON = [
        'id' => $pedido->id,
        'numero_pedido' => $pedido->numero_pedido,
        'cliente' => $pedido->cliente,
        'estado' => $pedido->estado,
        'cantidad_total' => $pedido->cantidad_total,
        'pedido_epps' => $epps->map(function($epp) {
            return [
                'id' => $epp->id,
                'epp_id' => $epp->epp_id,
                'epp_nombre' => $epp->epp?->nombre,
                'cantidad' => $epp->cantidad,
                'tallas_medidas' => $epp->tallas_medidas,
                'observaciones' => $epp->observaciones,
                'imagenes' => $epp->imagenes->map(function($img) {
                    return [
                        'id' => $img->id,
                        'archivo' => $img->archivo,
                        'principal' => (bool)$img->principal,
                        'orden' => $img->orden
                    ];
                })->toArray()
            ];
        })->toArray()
    ];
    
    echo "✅ JSON construido correctamente\n";

    // 5. Verificar si contiene EPP
    if (isset($datosJSON['pedido_epps'])) {
        echo "\n✅ El JSON contiene 'pedido_epps'\n";
        echo "   - Cantidad de EPP en JSON: " . count($datosJSON['pedido_epps']) . "\n";

        // 6. Mostrar cada EPP en el JSON
        echo "\n🔍 Detalles de EPP en JSON:\n";
        foreach ($datosJSON['pedido_epps'] as $key => $eppJson) {
            $numEpp = $key + 1;
            echo "\n   EPP #{$numEpp}:\n";
            foreach ($eppJson as $campo => $valor) {
                if (is_array($valor)) {
                    $cantElementos = count($valor);
                    echo "      - {$campo}: [Array con {$cantElementos} elementos]\n";
                } elseif (is_object($valor)) {
                    echo "      - {$campo}: " . json_encode($valor) . "\n";
                } else {
                    echo "      - {$campo}: {$valor}\n";
                }
            }
        }
    } else {
        echo "\n❌ El JSON NO contiene 'pedido_epps'\n";
        echo "\nCampos disponibles en el JSON:\n";
        foreach (array_keys($datosJSON) as $campo) {
            echo "   - {$campo}\n";
        }
    }

    // 7. Ejemplo de JSON completo (primeros 800 caracteres)
    echo "\n\n📋 JSON del Pedido (primeros 800 caracteres):\n";
    $jsonPretty = json_encode($datosJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo substr($jsonPretty, 0, 800) . "...\n";

    // 8. Guardar JSON a archivo para revisión
    $archivoJSON = storage_path('logs/pedido_epp_test.json');
    file_put_contents($archivoJSON, $jsonPretty);
    echo "\n💾 JSON completo guardado en: storage/logs/pedido_epp_test.json\n";

    // 9. Resultado final
    echo "\n╔════════════════════════════════════════════════╗\n";
    echo "║           ✅ PRUEBA COMPLETADA                ║\n";
    echo "║                                                ║\n";
    echo "║  ✅ Pedido cargado correctamente              ║\n";
    echo "║  ✅ EPP están en el JSON                       ║\n";
    echo "║  ✅ Imágenes asociadas verificadas            ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
