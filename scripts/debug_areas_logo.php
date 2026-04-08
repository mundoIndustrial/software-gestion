<?php

/**
 * Script para analizar datos de áreas en prenda_areas_logo_pedido
 * Uso: php scripts/debug_areas_logo.php
 */

require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = app();

// Prenda a analizar
$prendaId = 4;
$pedidoId = 13;

echo "\n=== ANÁLISIS DE ÁREAS PARA BORDADO/ESTAMPADO/DTF/SUBLIMADO ===\n";
echo "Prenda ID: $prendaId | Pedido ID: $pedidoId\n\n";

// 1. Verificar procesos de la prenda
echo "1. PROCESOS DE LA PRENDA (pedidos_procesos_prenda_detalles)\n";
echo str_repeat("-", 80) . "\n";
$procesos = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', $prendaId)
    ->get(['id', 'prenda_pedido_id', 'tipo_proceso_id', 'estado', 'created_at']);

if ($procesos->isEmpty()) {
    echo "  ✗ NO HAY PROCESOS\n";
} else {
    echo "  Encontrados: " . $procesos->count() . " procesos\n";
    foreach ($procesos as $p) {
        echo "    - ID: {$p->id}, tipo_proceso_id: {$p->tipo_proceso_id}, estado: {$p->estado}\n";
    }
}

// 2. Verificar áreas registradas para esta prenda
echo "\n2. ÁREAS REGISTRADAS (prenda_areas_logo_pedido)\n";
echo str_repeat("-", 80) . "\n";
$areas = DB::table('prenda_areas_logo_pedido')
    ->where('prenda_pedido_id', $prendaId)
    ->get(['id', 'prenda_pedido_id', 'proceso_prenda_detalle_id', 'area', 'created_at']);

if ($areas->isEmpty()) {
    echo "  ✗ NO HAY ÁREAS REGISTRADAS\n";
} else {
    echo "  Encontrados: " . $areas->count() . " registros\n";
    foreach ($areas as $a) {
        echo "    - proceso_prenda_detalle_id: {$a->proceso_prenda_detalle_id}, área: {$a->area}, creado: {$a->created_at}\n";
    }
}

// 3. Verificar recibos (consecutivos_recibos_pedidos)
echo "\n3. RECIBOS (consecutivos_recibos_pedidos)\n";
echo str_repeat("-", 80) . "\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->where('prenda_id', $prendaId)
    ->get(['id', 'tipo_recibo', 'area', 'consecutivo_actual', 'activo']);

if ($recibos->isEmpty()) {
    echo "  ✗ NO HAY RECIBOS\n";
} else {
    echo "  Encontrados: " . $recibos->count() . " recibos\n";
    foreach ($recibos as $r) {
        echo "    - tipo: {$r->tipo_recibo}, área: {$r->area}, consecutivo: {$r->consecutivo_actual}, activo: {$r->activo}\n";
    }
}

// 4. Test del query completo para BORDADO
echo "\n4. TEST QUERY PARA BORDADO (tipo_proceso_id = 2)\n";
echo str_repeat("-", 80) . "\n";

$tipoProcesoId = 2; // BORDADO
$procesoBordado = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', $prendaId)
    ->where('tipo_proceso_id', $tipoProcesoId)
    ->whereNull('deleted_at')
    ->first(['id', 'tipo_proceso_id']);

if (!$procesoBordado) {
    echo "  ✗ NO ENCONTRÓ PROCESO BORDADO (tipo_proceso_id=2)\n";
    echo "    Procesos disponibles en la prenda:\n";
    $procesos = DB::table('pedidos_procesos_prenda_detalles')
        ->where('prenda_pedido_id', $prendaId)
        ->get(['id', 'tipo_proceso_id']);
    foreach ($procesos as $p) {
        echo "      - ID: {$p->id}, tipo_proceso_id: {$p->tipo_proceso_id}\n";
    }
} else {
    echo "  ✓ ENCONTRÓ PROCESO BORDADO\n";
    echo "    Proceso ID: {$procesoBordado->id}\n";
    
    // Buscar área para este proceso
    $areaBordado = DB::table('prenda_areas_logo_pedido')
        ->where('prenda_pedido_id', $prendaId)
        ->where('proceso_prenda_detalle_id', $procesoBordado->id)
        ->orderByDesc('created_at')
        ->first(['area', 'created_at']);
    
    if (!$areaBordado) {
        echo "  ✗ NO ENCONTRÓ ÁREA PARA BORDADO EN prenda_areas_logo_pedido\n";
        echo "    Áreas disponibles:\n";
        $todasAreas = DB::table('prenda_areas_logo_pedido')
            ->where('prenda_pedido_id', $prendaId)
            ->get(['proceso_prenda_detalle_id', 'area']);
        if ($todasAreas->isEmpty()) {
            echo "      (No hay ninguna)\n";
        } else {
            foreach ($todasAreas as $a) {
                echo "      - proceso_id: {$a->proceso_prenda_detalle_id}, área: {$a->area}\n";
            }
        }
    } else {
        echo "  ✓ ENCONTRÓ ÁREA PARA BORDADO\n";
        echo "    Área: {$areaBordado->area}\n";
    }
}

// 5. Verificar mapeo de tipo_proceso_id
echo "\n5. MAPEO DE TIPOS DE PROCESO\n";
echo str_repeat("-", 80) . "\n";
$tiposProc = [
    2 => 'BORDADO',
    3 => 'ESTAMPADO',
    4 => 'DTF',
    5 => 'SUBLIMADO',
];

foreach ($tiposProc as $id => $nombre) {
    $count = DB::table('pedidos_procesos_prenda_detalles')
        ->where('prenda_pedido_id', $prendaId)
        ->where('tipo_proceso_id', $id)
        ->count();
    echo "  $nombre (tipo_proceso_id=$id): " . ($count > 0 ? "✓ Existe" : "✗ NO existe") . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";
