<?php

/**
 * SCRIPT DE ANÁLISIS DE TABLAS DE BASE DE DATOS
 * 
 * Propósito: Identificar tablas usadas vs no usadas
 * Uso: php analizar_tablas_db.php
 */

// Cargar configuración de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         ANÁLISIS DE TABLAS DE BASE DE DATOS                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener todas las tablas
$tablesArray = Schema::getTableListing();

echo "📊 TOTAL DE TABLAS: " . count($tablesArray) . "\n\n";

// Agrupar tablas por categoría
$categories = [
    'Prendas' => ['prendas', 'prendas_cot', 'prenda_fotos_cot', 'prendas_cotizacion', 'prendas_cotizacion_friendly'],
    'Cotizaciones' => ['cotizaciones', 'cotizaciones_prendas', 'logo_cotizaciones', 'historial_cotizaciones'],
    'Órdenes' => ['pedidos_produccion', 'prendas_pedido', 'procesos_produccion', 'entregas'],
    'Usuarios' => ['users', 'roles', 'user_roles'],
    'Sistema' => ['migrations', 'failed_jobs', 'password_reset_tokens', 'sessions'],
    'Otras' => []
];

// Clasificar tablas
$classified = [];
foreach ($tablesArray as $table) {
    $found = false;
    foreach ($categories as $category => $patterns) {
        foreach ($patterns as $pattern) {
            if (stripos($table, $pattern) !== false) {
                if (!isset($classified[$category])) {
                    $classified[$category] = [];
                }
                $classified[$category][] = $table;
                $found = true;
                break 2;
            }
        }
    }
    if (!$found) {
        $classified['Otras'][] = $table;
    }
}

// Mostrar tablas por categoría
foreach ($classified as $category => $tables) {
    if (empty($tables)) continue;
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📁 $category (" . count($tables) . " tablas)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    foreach ($tables as $table) {
        // Contar registros
        try {
            $count = DB::table($table)->count();
            $status = $count > 0 ? "✅ ACTIVA" : "⚠️ VACÍA";
            echo sprintf("  %-40s %s (%d registros)\n", $table, $status, $count);
        } catch (\Exception $e) {
            echo sprintf("  %-40s ❌ ERROR\n", $table);
        }
    }
    echo "\n";
}

// Análisis de tablas deprecadas
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║         ANÁLISIS DE TABLAS DEPRECADAS                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$deprecatedTables = [
    'prendas_cotizacion_friendly' => 'Reemplazada por nueva arquitectura',
    'prendas_cotizacion' => 'Reemplazada por prendas_cot',
];

foreach ($deprecatedTables as $table => $reason) {
    if (in_array($table, $tablesArray)) {
        try {
            $count = DB::table($table)->count();
            echo "⚠️  $table\n";
            echo "   Razón: $reason\n";
            echo "   Registros: $count\n";
            echo "   Estado: " . ($count > 0 ? "CON DATOS - NO ELIMINAR" : "VACÍA - PUEDE ELIMINARSE") . "\n\n";
        } catch (\Exception $e) {
            echo "❌ $table - Error al acceder\n\n";
        }
    }
}

// Resumen
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║         RESUMEN Y RECOMENDACIONES                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ TABLAS ACTIVAS (CON DATOS):\n";
$activeTables = [];
foreach ($tablesArray as $table) {
    try {
        $count = DB::table($table)->count();
        if ($count > 0) {
            $activeTables[] = $table;
            echo "   • $table ($count registros)\n";
        }
    } catch (\Exception $e) {
        // Ignorar errores
    }
}

echo "\n⚠️  TABLAS VACÍAS (PUEDEN ELIMINARSE):\n";
$emptyTables = [];
foreach ($tablesArray as $table) {
    try {
        $count = DB::table($table)->count();
        if ($count === 0) {
            $emptyTables[] = $table;
            echo "   • $table\n";
        }
    } catch (\Exception $e) {
        // Ignorar errores
    }
}

echo "\n📊 ESTADÍSTICAS:\n";
echo "   Total de tablas: " . count($tablesArray) . "\n";
echo "   Tablas activas: " . count($activeTables) . "\n";
echo "   Tablas vacías: " . count($emptyTables) . "\n";

echo "\n";
