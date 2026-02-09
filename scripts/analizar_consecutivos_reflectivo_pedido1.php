<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================================\n";
echo "ANÁLISIS DE CONSECUTIVOS REFLECTIVO - PEDIDO #1\n";
echo "=================================================\n\n";

$pedidoId = 1;

// 1. Información del pedido
echo "📋 INFORMACIÓN DEL PEDIDO #$pedidoId:\n";
echo str_repeat("-", 80) . "\n";

$pedido = DB::table('pedidos_produccion')->where('id', $pedidoId)->first();
if (!$pedido) {
    echo "❌ ERROR: No se encontró el pedido #$pedidoId\n";
    exit(1);
}

echo "ID: {$pedido->id}\n";
echo "Número Pedido: {$pedido->numero_pedido}\n";
echo "Estado: {$pedido->estado}\n";
echo "Cliente ID: {$pedido->cliente_id}\n";
echo "Creado: {$pedido->created_at}\n\n";

// 2. Prendas del pedido con información de_bodega
echo "👕 PRENDAS DEL PEDIDO:\n";
echo str_repeat("-", 80) . "\n";

$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get();

echo "Total prendas: " . $prendas->count() . "\n\n";

foreach ($prendas as $prenda) {
    $deBodega = $prenda->de_bodega ? '✅ SÍ' : '❌ NO';
    echo "  Prenda ID: {$prenda->id}\n";
    echo "  Nombre: {$prenda->nombre_prenda}\n";
    echo "  De Bodega: $deBodega\n";
    
    // Verificar si tiene proceso reflectivo
    $tieneReflectivo = DB::table('pedidos_procesos_prenda_detalles as ppd')
        ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
        ->where('ppd.prenda_pedido_id', $prenda->id)
        ->where('tp.nombre', 'REFLECTIVO')
        ->exists();
    
    echo "  Tiene Proceso REFLECTIVO: " . ($tieneReflectivo ? '✅ SÍ' : '❌ NO') . "\n";
    
    // Verificar consecutivos existentes para esta prenda
    $consecutivos = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedidoId)
        ->where('prenda_id', $prenda->id)
        ->where('tipo_recibo', 'REFLECTIVO')
        ->get();
    
    if ($consecutivos->count() > 0) {
        echo "  Consecutivos REFLECTIVO:\n";
        foreach ($consecutivos as $cons) {
            echo "    - ID: {$cons->id} | Consecutivo: {$cons->consecutivo_inicial} | Activo: " . ($cons->activo ? 'SÍ' : 'NO') . "\n";
        }
    } else {
        echo "  ⚠️ NO tiene consecutivos REFLECTIVO\n";
    }
    
    echo "\n";
}

// 3. Todos los procesos del pedido
echo "\n🔧 TODOS LOS PROCESOS DEL PEDIDO:\n";
echo str_repeat("-", 80) . "\n";

$procesos = DB::table('pedidos_procesos_prenda_detalles as ppd')
    ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
    ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
    ->where('pp.pedido_produccion_id', $pedidoId)
    ->whereNull('pp.deleted_at')
    ->select('ppd.*', 'tp.nombre as tipo_proceso', 'pp.id as prenda_id')
    ->get();

$procesosPorPrenda = $procesos->groupBy('prenda_id');

foreach ($procesosPorPrenda as $prendaId => $procesosP) {
    $prenda = $prendas->firstWhere('id', $prendaId);
    echo "Prenda ID $prendaId ({$prenda->nombre_prenda}):\n";
    foreach ($procesosP as $proceso) {
        echo "  - Tipo: {$proceso->tipo_proceso} | Estado: {$proceso->estado}\n";
    }
    echo "\n";
}

// 4. Todos los consecutivos creados
echo "\n📊 CONSECUTIVOS DE RECIBOS CREADOS:\n";
echo str_repeat("-", 80) . "\n";

$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->get();

if ($consecutivos->count() > 0) {
    foreach ($consecutivos as $cons) {
        $prenda = $prendas->firstWhere('id', $cons->prenda_id);
        $nombrePrenda = $prenda ? $prenda->nombre_prenda : 'N/A';
        echo "ID: {$cons->id}\n";
        echo "  Prenda ID: {$cons->prenda_id} ($nombrePrenda)\n";
        echo "  Tipo: {$cons->tipo_recibo}\n";
        echo "  Consecutivo Inicial: {$cons->consecutivo_inicial}\n";
        echo "  Consecutivo Actual: {$cons->consecutivo_actual}\n";
        echo "  Activo: " . ($cons->activo ? 'SÍ' : 'NO') . "\n";
        echo "  Creado: {$cons->created_at}\n";
        echo "\n";
    }
} else {
    echo "⚠️ No hay consecutivos creados para este pedido\n\n";
}

// 5. Análisis del problema
echo "\n🔍 ANÁLISIS DEL PROBLEMA:\n";
echo str_repeat("-", 80) . "\n";

$prendasConReflectivo = DB::table('prendas_pedido as pp')
    ->join('pedidos_procesos_prenda_detalles as ppd', 'pp.id', '=', 'ppd.prenda_pedido_id')
    ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
    ->where('pp.pedido_produccion_id', $pedidoId)
    ->where('pp.de_bodega', true)
    ->where('tp.nombre', 'REFLECTIVO')
    ->whereNull('pp.deleted_at')
    ->whereNull('ppd.deleted_at')
    ->select('pp.id', 'pp.nombre_prenda', 'pp.de_bodega')
    ->distinct()
    ->get();

echo "✅ Prendas que DEBERÍAN tener consecutivo REFLECTIVO:\n";
echo "   (de_bodega = true Y tiene proceso REFLECTIVO)\n\n";

if ($prendasConReflectivo->count() > 0) {
    foreach ($prendasConReflectivo as $prenda) {
        echo "  - Prenda ID: {$prenda->id} - {$prenda->nombre_prenda}\n";
        
        $tieneConsecutivo = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_id', $prenda->id)
            ->where('tipo_recibo', 'REFLECTIVO')
            ->exists();
        
        if ($tieneConsecutivo) {
            echo "    ✅ Tiene consecutivo REFLECTIVO\n";
        } else {
            echo "    ❌ NO tiene consecutivo REFLECTIVO (PROBLEMA!)\n";
        }
    }
} else {
    echo "  No hay prendas con de_bodega=true y proceso REFLECTIVO\n";
}

echo "\n";
echo "💡 ESPERADO: " . $prendasConReflectivo->count() . " consecutivos REFLECTIVO\n";

$consecutivosActuales = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->where('tipo_recibo', 'REFLECTIVO')
    ->count();

echo "📌 ACTUAL: $consecutivosActuales consecutivos REFLECTIVO\n";

if ($prendasConReflectivo->count() > $consecutivosActuales) {
    echo "\n⚠️ PROBLEMA CONFIRMADO: Faltan " . ($prendasConReflectivo->count() - $consecutivosActuales) . " consecutivos!\n";
} else {
    echo "\n✅ Los consecutivos están correctos\n";
}

echo "\n=================================================\n";
echo "FIN DEL ANÁLISIS\n";
echo "=================================================\n";
