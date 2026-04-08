<?php

/**
 * Script de Análisis: Consecutivos por Proceso y Prenda - Pedido ID 1
 *
 * Propósito: Diagnosticar si TODOS los procesos generan consecutivos
 * por cada prenda que tiene el proceso
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS DE CONSECUTIVOS POR PRENDA - PEDIDO ID 1           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$pedidoId = 1;

// 1. Información del Pedido
echo " INFORMACIÓN DEL PEDIDO\n";
echo str_repeat("─", 70) . "\n";

$pedido = DB::table('pedidos_produccion')->where('id', $pedidoId)->first();

if (!$pedido) {
    echo " ERROR: No se encontró el pedido con ID {$pedidoId}\n";
    exit(1);
}

echo "  ID: {$pedido->id}\n";
echo "  Número: {$pedido->numero_pedido}\n";
echo "  Estado: {$pedido->estado}\n";
echo "  Fecha: {$pedido->created_at}\n\n";

// 2. Análisis de Prendas
echo "👕 ANÁLISIS DE PRENDAS Y PROCESOS\n";
echo str_repeat("─", 70) . "\n";

$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->get();

echo "  Total de prendas: " . $prendas->count() . "\n\n";

$prendasPorProceso = [
    'COSTURA' => [],
    'BORDADO' => [],
    'ESTAMPADO' => [],
    'DTF' => [],
    'SUBLIMADO' => [],
    'REFLECTIVO' => []
];

foreach ($prendas as $index => $prenda) {
    echo "  Prenda #{$prenda->id}\n";
    echo "    - de_bodega: " . ($prenda->de_bodega ? 'SÍ ✓' : 'NO ✗') . "\n";
    
    // COSTURA se genera si NO es de bodega
    if (!$prenda->de_bodega) {
        $prendasPorProceso['COSTURA'][] = $prenda->id;
        echo "    -  COSTURA - DEBERÍA TENER CONSECUTIVO (de_bodega=false)\n";
    }
    
    // Obtener procesos de la prenda
    $procesos = DB::table('procesos_prendas_pedidos as ppp')
        ->join('tipos_procesos as tp', 'ppp.tipo_proceso_id', '=', 'tp.id')
        ->where('ppp.prenda_pedido_id', $prenda->id)
        ->select('tp.id', 'tp.nombre')
        ->get();
    
    if ($procesos->isEmpty()) {
        echo "    - Procesos adicionales: ninguno\n";
    } else {
        $nombresProcesos = $procesos->pluck('nombre')->toArray();
        echo "    - Procesos adicionales: " . implode(', ', $nombresProcesos) . "\n";
        
        // Verificar cada tipo de proceso
        foreach ($procesos as $proceso) {
            $tipoProceso = strtoupper(trim($proceso->nombre));
            
            if ($tipoProceso === 'BORDADO') {
                $prendasPorProceso['BORDADO'][] = $prenda->id;
                echo "    -  BORDADO - DEBERÍA TENER CONSECUTIVO\n";
            }
            if ($tipoProceso === 'ESTAMPADO') {
                $prendasPorProceso['ESTAMPADO'][] = $prenda->id;
                echo "    -  ESTAMPADO - DEBERÍA TENER CONSECUTIVO\n";
            }
            if ($tipoProceso === 'DTF') {
                $prendasPorProceso['DTF'][] = $prenda->id;
                echo "    -  DTF - DEBERÍA TENER CONSECUTIVO\n";
            }
            if ($tipoProceso === 'SUBLIMADO') {
                $prendasPorProceso['SUBLIMADO'][] = $prenda->id;
                echo "    -  SUBLIMADO - DEBERÍA TENER CONSECUTIVO\n";
            }
            if ($tipoProceso === 'REFLECTIVO') {
                $prendasPorProceso['REFLECTIVO'][] = $prenda->id;
                echo "    -  REFLECTIVO";
                if ($prenda->de_bodega) {
                    echo " - DEBERÍA TENER CONSECUTIVO (de_bodega=true)\n";
                } else {
                    echo " - NO genera consecutivo (de_bodega=false)\n";
                    // Remover si no es de bodega
                    $prendasPorProceso['REFLECTIVO'] = array_diff(
                        $prendasPorProceso['REFLECTIVO'],
                        [$prenda->id]
                    );
                }
            }
        }
    }
    echo "\n";
}

// 3. Resumen de Prendas que Deberían Tener Consecutivo
echo " RESUMEN DE PRENDAS POR PROCESO\n";
echo str_repeat("─", 70) . "\n";
foreach ($prendasPorProceso as $proceso => $prendasIds) {
    echo "  {$proceso}: " . count($prendasIds) . " prendas\n";
    if (!empty($prendasIds)) {
        echo "    IDs: " . implode(', ', $prendasIds) . "\n";
    }
}
echo "\n";

// 4. Consecutivos Actuales
echo "🔢 CONSECUTIVOS GENERADOS ACTUALMENTE\n";
echo str_repeat("─", 70) . "\n";

$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->orderBy('tipo_recibo')
    ->orderBy('prenda_id')
    ->get();

if ($consecutivos->isEmpty()) {
    echo "   No se encontraron consecutivos para este pedido\n\n";
} else {
    echo "  Total de consecutivos: " . $consecutivos->count() . "\n\n";
    
    $consecutivosPorTipo = [];
    foreach ($consecutivos as $cons) {
        if (!isset($consecutivosPorTipo[$cons->tipo_recibo])) {
            $consecutivosPorTipo[$cons->tipo_recibo] = 0;
        }
        $consecutivosPorTipo[$cons->tipo_recibo]++;
        
        echo "  ID: {$cons->id} - Tipo: {$cons->tipo_recibo}\n";
        echo "    - Prenda ID: " . ($cons->prenda_id ?? 'NULL') . "\n";
        echo "    - Consecutivo: {$cons->consecutivo_actual}\n";
        echo "    - Activo: " . ($cons->activo ? 'SÍ' : 'NO') . "\n";
        echo "    - Creado: {$cons->created_at}\n\n";
    }
    
    echo "  Resumen por tipo:\n";
    foreach ($consecutivosPorTipo as $tipo => $cantidad) {
        echo "    {$tipo}: {$cantidad} consecutivos\n";
    }
    echo "\n";
}

// 5. Comparación y Diagnóstico por Proceso
echo " DIAGNÓSTICO POR TIPO DE PROCESO\n";
echo str_repeat("─", 70) . "\n";

$problemasEncontrados = false;

foreach ($prendasPorProceso as $proceso => $prendasIds) {
    if (empty($prendasIds)) continue;
    
    $consecutivosProceso = $consecutivos->where('tipo_recibo', $proceso);
    $consecutivosCount = $consecutivosProceso->count();
    $esperados = count($prendasIds);
    
    echo "\n  {$proceso}:\n";
    echo "    Esperados: {$esperados} (prendas: " . implode(', ', $prendasIds) . ")\n";
    echo "    Generados: {$consecutivosCount}\n";
    echo "    Diferencia: " . ($esperados - $consecutivosCount) . "\n";
    
    if ($consecutivosCount < $esperados) {
        $problemasEncontrados = true;
        echo "      PROBLEMA: Faltan " . ($esperados - $consecutivosCount) . " consecutivos\n";
        
        // Identificar qué prendas NO tienen consecutivo
        $prendasConConsecutivo = $consecutivosProceso
            ->pluck('prenda_id')
            ->filter()
            ->toArray();
        
        $prendasSinConsecutivo = array_diff($prendasIds, $prendasConConsecutivo);
        
        if (!empty($prendasSinConsecutivo)) {
            echo "    Prendas sin consecutivo: " . implode(', ', $prendasSinConsecutivo) . " \n";
        }
    } else if ($consecutivosCount > $esperados) {
        echo "      ADVERTENCIA: Hay más consecutivos de los esperados\n";
    } else {
        echo "    ✓ OK\n";
    }
}

if ($problemasEncontrados) {
    echo "\n   CAUSA RAÍZ:\n";
    echo "  En ConsecutivosRecibosService.php, los procesos BORDADO, ESTAMPADO,\n";
    echo "  DTF, SUBLIMADO y REFLECTIVO usan la condición:\n";
    echo "  if (!isset(\$procesosPorPedido['TIPO']))\n\n";
    echo "  Esto solo permite generar UN consecutivo por pedido,\n";
    echo "  en lugar de uno por cada prenda que tenga el proceso.\n\n";
}

// 6. Solución Propuesta
echo "💡 SOLUCIÓN PROPUESTA\n";
echo str_repeat("─", 70) . "\n";
echo "  TODOS los procesos (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)\n";
echo "  deben generar un consecutivo por cada prenda que tenga el proceso.\n\n";
echo "  Cambio necesario en ConsecutivosRecibosService.php:\n\n";
echo "  ANTES (líneas ~220-268):\n";
echo "  ───────────────────────\n";
echo "  case 'BORDADO':\n";
echo "      if (!isset(\$procesosPorPedido['BORDADO'])) {\n";
echo "          \$procesosPorPedido['BORDADO'] = true;\n";
echo "          \$tiposRecibo['BORDADO'] = [...];  // Solo UNO por pedido \n";
echo "      }\n";
echo "      break;\n\n";
echo "  DESPUÉS (correcto):\n";
echo "  ──────────────────\n";
echo "  case 'BORDADO':\n";
echo "      \$tiposRecibo['BORDADO_' . \$prenda->id] = [\n";
echo "          'tipo_recibo' => 'BORDADO',\n";
echo "          'prenda_pedido_id' => \$prenda->id\n";
echo "      ];  // UNO POR PRENDA ✓\n";
echo "      break;\n\n";
echo "  Lo mismo aplica para ESTAMPADO, DTF, SUBLIMADO y REFLECTIVO.\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DEL ANÁLISIS                                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
