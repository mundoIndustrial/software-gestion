<?php

/**
 * Script de Análisis: Consecutivos Reflectivo - Pedido ID 1
 * 
 * Propósito: Diagnosticar por qué no se generaron consecutivos para todas 
 * las prendas con reflectivo y de_bodega = true
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS DE CONSECUTIVOS REFLECTIVO - PEDIDO ID 1           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$pedidoId = 1;

// 1. Información del Pedido
echo "📋 INFORMACIÓN DEL PEDIDO\n";
echo str_repeat("─", 70) . "\n";

$pedido = DB::table('pedidos_produccion')->where('id', $pedidoId)->first();

if (!$pedido) {
    echo "❌ ERROR: No se encontró el pedido con ID {$pedidoId}\n";
    exit(1);
}

echo "  ID: {$pedido->id}\n";
echo "  Número: {$pedido->numero_pedido}\n";
echo "  Estado: {$pedido->estado}\n";
echo "  Fecha: {$pedido->created_at}\n\n";

// 2. Análisis de Prendas
echo "👕 ANÁLISIS DE PRENDAS\n";
echo str_repeat("─", 70) . "\n";

$prendas = DB::table('pedido_produccion_prendas')
    ->where('pedido_produccion_id', $pedidoId)
    ->get();

echo "  Total de prendas: " . $prendas->count() . "\n\n";

$prendasConReflectivo = [];
$prendasDeBodegaConReflectivo = [];

foreach ($prendas as $index => $prenda) {
    echo "  Prenda #{$prenda->id}\n";
    echo "    - de_bodega: " . ($prenda->de_bodega ? 'SÍ ✓' : 'NO ✗') . "\n";
    echo "    - cantidad: {$prenda->cantidad}\n";
    
    // Obtener procesos de la prenda
    $procesos = DB::table('pedido_produccion_prenda_procesos as pppp')
        ->join('tipos_procesos as tp', 'pppp.tipo_proceso_id', '=', 'tp.id')
        ->where('pppp.pedido_produccion_prenda_id', $prenda->id)
        ->select('tp.id', 'tp.nombre')
        ->get();
    
    echo "    - Procesos: ";
    if ($procesos->isEmpty()) {
        echo "ninguno\n";
    } else {
        $nombresProcesos = $procesos->pluck('nombre')->toArray();
        echo implode(', ', $nombresProcesos) . "\n";
        
        // Verificar si tiene REFLECTIVO
        $tieneReflectivo = false;
        foreach ($procesos as $proceso) {
            if (strtoupper(trim($proceso->nombre)) === 'REFLECTIVO') {
                $tieneReflectivo = true;
                break;
            }
        }
        
        if ($tieneReflectivo) {
            $prendasConReflectivo[] = $prenda->id;
            echo "    - 🔹 Tiene proceso REFLECTIVO\n";
            
            if ($prenda->de_bodega) {
                $prendasDeBodegaConReflectivo[] = $prenda->id;
                echo "    - ⚠️  DEBERÍA TENER CONSECUTIVO (de_bodega=true + REFLECTIVO)\n";
            }
        }
    }
    echo "\n";
}

// 3. Resumen de Prendas que Deberían Tener Consecutivo
echo "📊 RESUMEN DE PRENDAS CON REFLECTIVO\n";
echo str_repeat("─", 70) . "\n";
echo "  Prendas con proceso REFLECTIVO: " . count($prendasConReflectivo) . "\n";
echo "  Prendas de_bodega=true con REFLECTIVO: " . count($prendasDeBodegaConReflectivo) . "\n";
echo "  IDs de prendas que deberían tener consecutivo: " . implode(', ', $prendasDeBodegaConReflectivo) . "\n\n";

// 4. Consecutivos Actuales
echo "🔢 CONSECUTIVOS GENERADOS ACTUALMENTE\n";
echo str_repeat("─", 70) . "\n";

$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->orderBy('tipo_recibo')
    ->orderBy('prenda_id')
    ->get();

if ($consecutivos->isEmpty()) {
    echo "  ❌ No se encontraron consecutivos para este pedido\n\n";
} else {
    echo "  Total de consecutivos: " . $consecutivos->count() . "\n\n";
    
    $consecutivosReflectivo = $consecutivos->where('tipo_recibo', 'REFLECTIVO');
    
    foreach ($consecutivos as $cons) {
        $marca = '';
        if ($cons->tipo_recibo === 'REFLECTIVO') {
            $marca = ' 🔹';
        }
        
        echo "  ID: {$cons->id} - Tipo: {$cons->tipo_recibo}{$marca}\n";
        echo "    - Prenda ID: " . ($cons->prenda_id ?? 'NULL') . "\n";
        echo "    - Consecutivo: {$cons->consecutivo_actual}\n";
        echo "    - Activo: " . ($cons->activo ? 'SÍ' : 'NO') . "\n";
        echo "    - Creado: {$cons->created_at}\n\n";
    }
    
    echo "  Consecutivos REFLECTIVO encontrados: " . $consecutivosReflectivo->count() . "\n\n";
}

// 5. Comparación y Diagnóstico
echo "🔍 DIAGNÓSTICO DEL PROBLEMA\n";
echo str_repeat("─", 70) . "\n";

$consecutivosReflectivoCount = $consecutivos->where('tipo_recibo', 'REFLECTIVO')->count();
$prendasQueDeberian = count($prendasDeBodegaConReflectivo);

echo "  Consecutivos REFLECTIVO esperados: {$prendasQueDeberian}\n";
echo "  Consecutivos REFLECTIVO generados: {$consecutivosReflectivoCount}\n";
echo "  Diferencia: " . ($prendasQueDeberian - $consecutivosReflectivoCount) . "\n\n";

if ($consecutivosReflectivoCount < $prendasQueDeberian) {
    echo "  ⚠️  PROBLEMA CONFIRMADO:\n";
    echo "  El sistema solo generó {$consecutivosReflectivoCount} consecutivo(s) REFLECTIVO\n";
    echo "  pero debería haber generado {$prendasQueDeberian} (uno por cada prenda con de_bodega=true y REFLECTIVO)\n\n";
    
    // Identificar qué prendas NO tienen consecutivo
    $prendasConConsecutivo = $consecutivos
        ->where('tipo_recibo', 'REFLECTIVO')
        ->pluck('prenda_id')
        ->filter()
        ->toArray();
    
    $prendasSinConsecutivo = array_diff($prendasDeBodegaConReflectivo, $prendasConConsecutivo);
    
    if (!empty($prendasSinConsecutivo)) {
        echo "  Prendas SIN consecutivo REFLECTIVO:\n";
        foreach ($prendasSinConsecutivo as $prendaId) {
            echo "    - Prenda ID: {$prendaId} ❌\n";
        }
        echo "\n";
    }
    
    echo "  📝 CAUSA RAÍZ:\n";
    echo "  En ConsecutivosRecibosService.php (línea ~260), el código usa:\n";
    echo "  if (\$prenda->de_bodega && !isset(\$procesosPorPedido['REFLECTIVO']))\n\n";
    echo "  Esta condición solo permite generar UN consecutivo REFLECTIVO por pedido,\n";
    echo "  en lugar de uno por cada prenda que cumpla las condiciones.\n\n";
    
} else if ($consecutivosReflectivoCount > $prendasQueDeberian) {
    echo "  ⚠️  ADVERTENCIA: Se generaron MÁS consecutivos de los esperados\n\n";
} else {
    echo "  ✓ OK: La cantidad de consecutivos es correcta\n\n";
}

// 6. Solución Propuesta
echo "💡 SOLUCIÓN PROPUESTA\n";
echo str_repeat("─", 70) . "\n";
echo "  El código de REFLECTIVO debe funcionar como COSTURA:\n";
echo "  generar un consecutivo por cada prenda con de_bodega=true + REFLECTIVO\n\n";
echo "  Cambio necesario en ConsecutivosRecibosService.php:\n\n";
echo "  ANTES (línea ~260):\n";
echo "  ──────────────────\n";
echo "  case 'REFLECTIVO':\n";
echo "      if (\$prenda->de_bodega && !isset(\$procesosPorPedido['REFLECTIVO'])) {\n";
echo "          \$procesosPorPedido['REFLECTIVO'] = true;\n";
echo "          \$tiposRecibo['REFLECTIVO'] = [...];  // Solo UNO\n";
echo "      }\n";
echo "      break;\n\n";
echo "  DESPUÉS (sugerido):\n";
echo "  ───────────────────\n";
echo "  case 'REFLECTIVO':\n";
echo "      if (\$prenda->de_bodega) {\n";
echo "          \$tiposRecibo['REFLECTIVO_' . \$prenda->id] = [\n";
echo "              'tipo_recibo' => 'REFLECTIVO',\n";
echo "              'prenda_pedido_id' => \$prenda->id\n";
echo "          ];  // UNO POR PRENDA\n";
echo "      }\n";
echo "      break;\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DEL ANÁLISIS                                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
