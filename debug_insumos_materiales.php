<?php
/**
 * Script de DEBUG - Análisis de Insumos/Materiales
 * 
 * Propósito: Identificar por qué no se muestran prendas en estado CORTE/COSTURA
 * en http://localhost:8000/insumos/materiales
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  DEBUG: ANÁLISIS DE INSUMOS - MATERIALES\n";
echo "════════════════════════════════════════════════════════════════\n";

// ============ PASO 1: Estado actual de la BD ============
echo "\n✓ PASO 1: RECIBOS EN BD (Estado actual)\n";
echo "─────────────────────────────────────────────────────────────────\n";

$allRecibos = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->select(
        'consecutivos_recibos_pedidos.id',
        'consecutivos_recibos_pedidos.consecutivo_actual',
        'consecutivos_recibos_pedidos.estado as recibo_estado',
        'consecutivos_recibos_pedidos.area as recibo_area',
        'pedidos_produccion.numero_pedido',
        'pedidos_produccion.cliente',
        'pedidos_produccion.estado as pedido_estado',
        'pedidos_produccion.area as pedido_area'
    )
    ->limit(50)
    ->get();

echo "Total de recibos COSTURA activos encontrados: " . $allRecibos->count() . "\n\n";

$estadosRecibo = $allRecibos->pluck('recibo_estado')->unique()->values();
$areasRecibo = $allRecibos->pluck('recibo_area')->unique()->values();
$areasPedido = $allRecibos->pluck('pedido_area')->unique()->values();

echo "Estados ÚNICOS en recibo (consecutivos_recibos_pedidos.estado):\n";
foreach ($estadosRecibo as $estado) {
    $count = $allRecibos->where('recibo_estado', $estado)->count();
    echo "  • '{$estado}' → {$count} recibos\n";
}

echo "\nÁreas ÚNICAS en recibo (consecutivos_recibos_pedidos.area):\n";
foreach ($areasRecibo as $area) {
    $count = $allRecibos->where('recibo_area', $area)->count();
    echo "  • '{$area}' → {$count} recibos\n";
}

echo "\nÁreas ÚNICAS en pedido (pedidos_produccion.area):\n";
foreach ($areasPedido as $area) {
    $count = $allRecibos->where('pedido_area', $area)->count();
    echo "  • '{$area}' → {$count} recibos\n";
}

// ============ PASO 2: Query Base Actual ============
echo "\n\n✓ PASO 2: QUERY BASE ACTUAL (buildBaseQuery())\n";
echo "─────────────────────────────────────────────────────────────────\n";

$queryActual = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->select(
        'consecutivos_recibos_pedidos.id',
        'consecutivos_recibos_pedidos.consecutivo_actual',
        'consecutivos_recibos_pedidos.estado as recibo_estado',
        'consecutivos_recibos_pedidos.area as recibo_area',
        'pedidos_produccion.numero_pedido',
        'pedidos_produccion.cliente',
        'pedidos_produccion.estado as pedido_estado',
        'pedidos_produccion.area as pedido_area'
    )
    ->where(function ($q) {
        $q->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
            ->orWhere('pedidos_produccion.area', 'LIKE', '%Corte%')
            ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion%orden%')
            ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion de orden%');
    })
    ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');

$resultadosActuales = $queryActual->get();

echo "SQL Query Base:\n";
echo $queryActual->toSql() . "\n";
echo "\nBindings: " . json_encode($queryActual->getBindings()) . "\n";
echo "\nTotal de recibos que SALEN con query base actual: " . $resultadosActuales->count() . "\n";

// Mostrar primeros 10
if ($resultadosActuales->count() > 0) {
    echo "\nPrimeros 10 recibos encontrados:\n";
    foreach ($resultadosActuales->take(10) as $idx => $recibo) {
        echo "  " . ($idx + 1) . ". Recibo: {$recibo->consecutivo_actual} " .
             "| Estado: {$recibo->recibo_estado} " .
             "| Área (recibo): {$recibo->recibo_area} " .
             "| Pedido: {$recibo->numero_pedido}\n";
    }
}

// ============ PASO 3: Recibos que DEBERÍAN aparecer ============
echo "\n\n✓ PASO 3: RECIBOS QUE DEBERÍAN APARECER (Estado CORTE/COSTURA)\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Buscar recibos con área = CORTE o COSTURA
$recibosPerdidos = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->select(
        'consecutivos_recibos_pedidos.id',
        'consecutivos_recibos_pedidos.consecutivo_actual',
        'consecutivos_recibos_pedidos.estado as recibo_estado',
        'consecutivos_recibos_pedidos.area as recibo_area',
        'pedidos_produccion.numero_pedido',
        'pedidos_produccion.cliente',
        'pedidos_produccion.estado as pedido_estado',
        'pedidos_produccion.area as pedido_area'
    )
    ->whereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA'])
    ->get();

echo "Recibos con área (recibo) = CORTE o COSTURA: " . $recibosPerdidos->count() . "\n";

if ($recibosPerdidos->count() > 0) {
    echo "\n⚠️  ESTOS RECIBOS DEBERÍAN APARECER PERO NO LO HACEN:\n";
    foreach ($recibosPerdidos->take(10) as $idx => $recibo) {
        $enActual = $resultadosActuales->where('id', $recibo->id)->count() > 0 ? "✓ SÍ" : "✗ NO";
        echo "  " . ($idx + 1) . ". [" . $enActual . "] Recibo: {$recibo->consecutivo_actual} " .
             "| Área: {$recibo->recibo_area} " .
             "| Estado: {$recibo->recibo_estado} " .
             "| Pedido: {$recibo->numero_pedido} ({$recibo->cliente})\n";
    }
}

// ============ PASO 4: Análisis de la condición WHERE ============
echo "\n\n✓ PASO 4: ANÁLISIS DE LA CONDICIÓN WHERE PROBLEMÁTICA\n";
echo "─────────────────────────────────────────────────────────────────\n";

echo "Condición actual en buildBaseQuery():\n";
echo "→ estado == 'PENDIENTE_INSUMOS' OR\n";
echo "→ pedidos_produccion.area LIKE '%Corte%' OR\n";
echo "→ pedidos_produccion.area LIKE '%Creacion%orden%' OR\n";
echo "→ pedidos_produccion.area LIKE '%Creacion de orden%'\n\n";

echo "PROBLEMA: La condición busca en 'pedidos_produccion.area' (área del PEDIDO)\n";
echo "pero los recibos tienen estado en 'consecutivos_recibos_pedidos.area'\n\n";

// Contar qué cumplen cada subcondición
$cond1 = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
    ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
    ->count();

$cond2 = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->where('pedidos_produccion.area', 'LIKE', '%Corte%')
    ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
    ->count();

$cond3 = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->whereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA'])
    ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
    ->count();

echo "Recibos que cumplen 'estado == PENDIENTE_INSUMOS': {$cond1}\n";
echo "Recibos que cumplen 'pedido.area LIKE Corte': {$cond2}\n";
echo "Recibos que cumplen 'recibo.area IN (CORTE, COSTURA)': {$cond3}\n";

// ============ PASO 5: Query Propuesta ============
echo "\n\n✓ PASO 5: QUERY PROPUESTA (CORRECCIÓN)\n";
echo "─────────────────────────────────────────────────────────────────\n";

$queryCorregida = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->select(
        'consecutivos_recibos_pedidos.id',
        'consecutivos_recibos_pedidos.consecutivo_actual',
        'consecutivos_recibos_pedidos.estado as recibo_estado',
        'consecutivos_recibos_pedidos.area as recibo_area',
        'pedidos_produccion.numero_pedido',
        'pedidos_produccion.cliente',
        'pedidos_produccion.estado as pedido_estado',
        'pedidos_produccion.area as pedido_area'
    )
    ->where(function ($q) {
        $q->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
            ->orWhereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA']);  // ← CAMBIO: buscar en recibo.area
    })
    ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');

$resultadosCorregidos = $queryCorregida->get();

echo "SQL Query Corregida:\n";
echo $queryCorregida->toSql() . "\n";
echo "\nBindings: " . json_encode($queryCorregida->getBindings()) . "\n";
echo "\nTotal de recibos con query CORREGIDA: " . $resultadosCorregidos->count() . "\n";

// Mostrar primeros 10
if ($resultadosCorregidos->count() > 0) {
    echo "\nPrimeros 10 recibos encontrados (CORREGIDOS):\n";
    foreach ($resultadosCorregidos->take(10) as $idx => $recibo) {
        echo "  " . ($idx + 1) . ". Recibo: {$recibo->consecutivo_actual} " .
             "| Estado: {$recibo->recibo_estado} " .
             "| Área: {$recibo->recibo_area} " .
             "| Pedido: {$recibo->numero_pedido}\n";
    }
}

// ============ RESUMEN ============
echo "\n\n════════════════════════════════════════════════════════════════\n";
echo "  RESUMEN\n";
echo "════════════════════════════════════════════════════════════════\n";

$diferencia = $resultadosCorregidos->count() - $resultadosActuales->count();
echo "Recibos mostrados actualmente: " . $resultadosActuales->count() . "\n";
echo "Recibos con query corregida: " . $resultadosCorregidos->count() . "\n";
echo "Diferencia: " . ($diferencia > 0 ? "+" : "") . $diferencia . " recibos faltantes\n";

echo "\n✓ Debug completado\n\n";
