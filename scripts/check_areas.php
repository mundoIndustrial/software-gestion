#!/usr/bin/env php
<?php

set_error_handler(function($errno, $errstr) {
    echo "\n✗ ERROR: $errstr\n";
    exit(1);
});

// Load Laravel
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
} catch (Exception $e) {
    echo "\n✗ No se pudo cargar Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

use Illuminate\Support\Facades\DB;

$prendaId = 4;

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "ANÁLISIS DE ÁREAS PARA PRENDA $prendaId\n";
echo str_repeat("=", 100) . "\n";

// 1. Procesos
echo "\n[1] PROCESOS (pedidos_procesos_prenda_detalles)\n";
echo str_repeat("-", 100) . "\n";
$procesos = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', $prendaId)
    ->get(['id', 'tipo_proceso_id', 'estado']);

if ($procesos->isEmpty()) {
    echo "  ✗ NO HAY PROCESOS\n";
} else {
    echo "  ✓ Encontrados: " . $procesos->count() . " procesos\n";
    foreach ($procesos as $p) {
        echo "    └─ ID: {$p->id}, tipo_proceso_id: {$p->tipo_proceso_id}, estado: {$p->estado}\n";
    }
}

// 2. Áreas registradas
echo "\n[2] ÁREAS REGISTRADAS (prenda_areas_logo_pedido)\n";
echo str_repeat("-", 100) . "\n";
$areas = DB::table('prenda_areas_logo_pedido')
    ->where('prenda_pedido_id', $prendaId)
    ->orderBy('proceso_prenda_detalle_id')
    ->get(['id', 'proceso_prenda_detalle_id', 'area', 'created_at']);

if ($areas->isEmpty()) {
    echo "  ✗ NO HAY ÁREAS REGISTRADAS\n";
} else {
    echo "  ✓ Encontrados: " . $areas->count() . " registros\n";
    foreach ($areas as $a) {
        echo "    └─ proceso_id: {$a->proceso_prenda_detalle_id}, área: {$a->area}, creado: {$a->created_at}\n";
    }
}

// 3. Recibos
echo "\n[3] RECIBOS (consecutivos_recibos_pedidos)\n";
echo str_repeat("-", 100) . "\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->where('prenda_id', $prendaId)
    ->get(['id', 'tipo_recibo', 'area', 'consecutivo_actual', 'activo']);

if ($recibos->isEmpty()) {
    echo "  ✗ NO HAY RECIBOS\n";
} else {
    echo "  ✓ Encontrados: " . $recibos->count() . " recibos\n";
    foreach ($recibos as $r) {
        $activo = $r->activo ? "✓" : "✗";
        echo "    └─ tipo: {$r->tipo_recibo}, área: {$r->area}, consecutivo: {$r->consecutivo_actual}, activo: $activo\n";
    }
}

// 4. Test queries para cada tipo especial
echo "\n[4] TEST QUERIES PARA TIPOS ESPECIALES\n";
echo str_repeat("-", 100) . "\n";

$tiposEspeciales = [
    2 => 'BORDADO',
    3 => 'ESTAMPADO',
    4 => 'DTF',
    5 => 'SUBLIMADO',
];

foreach ($tiposEspeciales as $tipoProcesoId => $nombre) {
    echo "\n  [$nombre - tipo_proceso_id=$tipoProcesoId]\n";
    
    $proc = DB::table('pedidos_procesos_prenda_detalles')
        ->where('prenda_pedido_id', $prendaId)
        ->where('tipo_proceso_id', $tipoProcesoId)
        ->first(['id']);
    
    if (!$proc) {
        echo "    ✗ No existe proceso $nombre en prenda\n";
    } else {
        echo "    ✓ Proceso encontrado: ID={$proc->id}\n";
        
        $area = DB::table('prenda_areas_logo_pedido')
            ->where('prenda_pedido_id', $prendaId)
            ->where('proceso_prenda_detalle_id', $proc->id)
            ->orderByDesc('created_at')
            ->first(['area']);
        
        if (!$area) {
            echo "    ✗ NO hay área registrada para este proceso\n";
        } else {
            echo "    ✓ Área: {$area->area}\n";
        }
    }
}

// 5. Resumen
echo "\n[5] RESUMEN\n";
echo str_repeat("-", 100) . "\n";

$totalProcesos = $procesos->count();
$totalAreas = $areas->count();
$totalRecibos = $recibos->count();
$recibosEspeciales = $recibos->filter(fn($r) => in_array($r->tipo_recibo, ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO', 'REFLECTIVO']))->count();

echo "  • Total procesos: $totalProcesos\n";
echo "  • Total áreas en prenda_areas_logo_pedido: $totalAreas\n";
echo "  • Total recibos: $totalRecibos\n";
echo "  • Recibos especiales: $recibosEspeciales\n";

if ($totalAreas == 0 && $totalProcesos > 0) {
    echo "\n  ⚠️  PROBLEMA: Hay procesos pero NO hay áreas en prenda_areas_logo_pedido\n";
    echo "      El sistema de áreas Logo no está registrando datos.\n";
} elseif ($totalAreas > 0) {
    echo "\n  ✓ OK: Hay datos de áreas registrados\n";
} else {
    echo "\n  ⚠️  No hay datos suficientes para analizar\n";
}

echo "\n" . str_repeat("=", 100) . "\n\n";
