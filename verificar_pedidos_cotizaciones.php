#!/usr/bin/env php
<?php

// Script para verificar si los números de pedidos son consecutivos
// cuando se crean desde cotizaciones de diferentes tipos

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔍 ANÁLISIS: Generación de Pedidos desde Cotizaciones\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar secuencias disponibles
echo "📊 SECUENCIAS DISPONIBLES EN BD:\n";
echo "─────────────────────────────────────────────\n";
$secuencias = DB::table('numero_secuencias')->get();
foreach ($secuencias as $seq) {
    echo "   • {$seq->tipo}: siguiente = {$seq->siguiente}\n";
}

// 2. Verificar últimas 10 cotizaciones
echo "\n📋 ÚLTIMAS 10 COTIZACIONES:\n";
echo "─────────────────────────────────────────────\n";
$cotizaciones = DB::table('cotizaciones')
    ->join('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
    ->select('cotizaciones.id', 'cotizaciones.numero_cotizacion', 'tipos_cotizacion.codigo', 'tipos_cotizacion.nombre', 'cotizaciones.created_at')
    ->orderBy('cotizaciones.created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($cotizaciones as $cot) {
    $fecha = is_string($cot->created_at) ? $cot->created_at : $cot->created_at->format('Y-m-d H:i:s');
    echo sprintf(
        "  %d. %s (Tipo: %s - %s) | %s\n",
        $cot->id,
        $cot->numero_cotizacion ?? 'SIN NÚMERO',
        $cot->codigo,
        $cot->nombre,
        $fecha
    );
}

// 3. Verificar últimos 10 pedidos
echo "\n🏭 ÚLTIMOS 10 PEDIDOS:\n";
echo "─────────────────────────────────────────────\n";
$pedidos = DB::table('pedidos_produccion')
    ->select('id', 'numero_pedido', 'cotizacion_id', 'cliente', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($pedidos as $ped) {
    $cotizacion_id = $ped->cotizacion_id ?? 'N/A';
    $fecha = is_string($ped->created_at) ? $ped->created_at : $ped->created_at->format('Y-m-d H:i:s');
    echo sprintf(
        "  #%s | Cliente: %-20s | Cot ID: %s | %s\n",
        str_pad($ped->numero_pedido, 6),
        substr($ped->cliente, 0, 20),
        $cotizacion_id,
        $fecha
    );
}

// 4. Análisis: Relación Cotización → Pedido
echo "\n🔗 RELACIÓN COTIZACIÓN → PEDIDO:\n";
echo "─────────────────────────────────────────────\n";

$relaciones = DB::table('pedidos_produccion')
    ->join('cotizaciones', 'pedidos_produccion.cotizacion_id', '=', 'cotizaciones.id')
    ->join('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
    ->select(
        'pedidos_produccion.numero_pedido',
        'cotizaciones.numero_cotizacion',
        'tipos_cotizacion.codigo as tipo_codigo',
        'tipos_cotizacion.nombre as tipo_nombre'
    )
    ->orderBy('pedidos_produccion.created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($relaciones as $rel) {
    echo sprintf(
        "  Pedido: %s → Cotización: %s (Tipo: %s)\n",
        str_pad($rel->numero_pedido, 6),
        $rel->numero_cotizacion ?? 'SIN NÚMERO',
        $rel->tipo_codigo
    );
}

// 5. PROBLEMA DETECTADO
echo "\n⚠️  PROBLEMA DETECTADO:\n";
echo "─────────────────────────────────────────────\n";
echo "   1. Cotizaciones usan: numero_secuencias.cotizaciones_universal\n";
echo "   2. Pedidos usan:      numero_secuencias.pedido_produccion\n";
echo "   3. Resultado: SECUENCIAS SEPARADAS ❌\n\n";

// 6. Verificar si los números de pedidos son consecutivos
$todosLosPedidos = DB::table('pedidos_produccion')
    ->select('numero_pedido')
    ->where('numero_pedido', '!=', null)
    ->orderBy('numero_pedido', 'asc')
    ->pluck('numero_pedido')
    ->toArray();

if (!empty($todosLosPedidos)) {
    echo "📊 ANÁLISIS DE SECUENCIALIDAD DE PEDIDOS:\n";
    echo "─────────────────────────────────────────────\n";
    
    // Convertir a integers para análisis
    $numeros = array_map(function($n) {
        // Intentar extraer el número después del prefijo
        if (is_numeric($n)) {
            return (int)$n;
        }
        // Si tiene formato "PED-00001", extraer solo los números
        if (preg_match('/\d+/', $n, $m)) {
            return (int)$m[0];
        }
        return (int)$n;
    }, $todosLosPedidos);

    $numeros = array_unique($numeros);
    sort($numeros);

    echo "   Total pedidos: " . count($numeros) . "\n";
    echo "   Primero: " . $numeros[0] . "\n";
    echo "   Último: " . end($numeros) . "\n";
    
    // Verificar saltos
    $saltos = [];
    for ($i = 1; $i < count($numeros); $i++) {
        $diferencia = $numeros[$i] - $numeros[$i-1];
        if ($diferencia > 1) {
            $saltos[] = "De {$numeros[$i-1]} a {$numeros[$i]} (salto de " . ($diferencia - 1) . ")";
        }
    }
    
    if (!empty($saltos)) {
        echo "   ⚠️  SALTOS DETECTADOS:\n";
        foreach ($saltos as $salto) {
            echo "      • $salto\n";
        }
    } else {
        echo "   ✅ Números perfectamente consecutivos\n";
    }
}

// 7. Recomendación
echo "\n🎯 RECOMENDACIÓN:\n";
echo "─────────────────────────────────────────────\n";
echo "   Para que los pedidos TAMBIÉN sean consecutivos como las cotizaciones:\n";
echo "   \n";
echo "   OPCIÓN 1 (Recomendada):\n";
echo "   ├─ Usar secuencia GLOBAL para pedidos también\n";
echo "   ├─ Cambiar: pedido_produccion → usar numero_secuencias_universal\n";
echo "   └─ Resultado: PED-000001, PED-000002, ... (sin gaps)\n";
echo "   \n";
echo "   OPCIÓN 2:\n";
echo "   ├─ Mantener secuencia separada para pedidos\n";
echo "   ├─ Cambiar: numero_pedido a usar MISMO formato que cotizaciones\n";
echo "   └─ Resultado: PED-45121, PED-45122, ... (consecutivo por tipo)\n";
echo "\n";
