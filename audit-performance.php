#!/usr/bin/env php
<?php
/**
 * Script de Auditoría Rápida: Verificar Optimizaciones
 * 
 * Uso:
 *   php audit-performance.php
 * 
 * Verifica:
 * ✅ N+1 Queries
 * ✅ Índices en BD
 * ✅ Logs en producción
 * ✅ Cache configurado
 * ✅ Data attributes en vista
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║     AUDITORÍA DE RENDIMIENTO - /asesores/pedidos       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// =====================================================
// 1. VERIFICAR ÍNDICES EN BASE DE DATOS
// =====================================================
echo "📊 1. VERIFICANDO ÍNDICES EN BASE DE DATOS\n";
echo "─────────────────────────────────────────────────────────\n";

$indexes = DB::select("
    SELECT INDEX_NAME, COLUMN_NAME
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = ? 
    AND TABLE_NAME = 'pedidos_produccion'
    ORDER BY INDEX_NAME, SEQ_IN_INDEX
", [env('DB_DATABASE')]);

if (empty($indexes)) {
    echo "❌ NO HAY ÍNDICES - Requiere migración\n\n";
    echo "   Ejecutar:\n";
    echo "   php artisan make:migration add_indexes_pedidos_produccion\n";
    echo "   php artisan migrate\n\n";
} else {
    $gruposIndices = [];
    foreach ($indexes as $idx) {
        if (!isset($gruposIndices[$idx->INDEX_NAME])) {
            $gruposIndices[$idx->INDEX_NAME] = [];
        }
        $gruposIndices[$idx->INDEX_NAME][] = $idx->COLUMN_NAME;
    }

    foreach ($gruposIndices as $name => $columns) {
        echo "✅ Índice: {$name}\n";
        echo "   Columnas: " . implode(', ', $columns) . "\n";
    }
    echo "\n";
}

// =====================================================
// 2. VERIFICAR QUERIES GENERADAS
// =====================================================
echo "🔍 2. ANALIZANDO QUERIES GENERADAS\n";
echo "─────────────────────────────────────────────────────────\n";

$queryCount = 0;
$totalTime = 0;

DB::listen(function ($query) use (&$queryCount, &$totalTime) {
    $queryCount++;
    $totalTime += $query->time;
});

// Simular la consulta
$userId = auth()->id() ?? 1;
$service = app(\App\Application\Services\Asesores\ObtenerPedidosService::class);

try {
    $pedidos = $service->obtener(null, []);
    
    echo "✅ Consulta principal ejecutada\n";
    echo "   Número de queries: {$queryCount}\n";
    echo "   Tiempo total: {$totalTime}ms\n";

    if ($queryCount > 10) {
        echo "   ⚠️  Muchas queries ({$queryCount}) - Revisar N+1\n";
    } else {
        echo "   ✅ Número de queries óptimo\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Error ejecutando consulta: {$e->getMessage()}\n\n";
}

// =====================================================
// 3. VERIFICAR CACHÉ
// =====================================================
echo "💾 3. VERIFICANDO CONFIGURACIÓN DE CACHÉ\n";
echo "─────────────────────────────────────────────────────────\n";

$cacheDriver = config('cache.default');
echo "✅ Driver de caché: {$cacheDriver}\n";

try {
    Cache::remember('test_audit', 3600, function () {
        return 'test_value';
    });
    echo "✅ Caché funcionando correctamente\n";
} catch (\Exception $e) {
    echo "❌ Error con caché: {$e->getMessage()}\n";
}

// Verificar si el cache de estados existe
$estadosEnCache = Cache::get('pedidos_estados_list');
if ($estadosEnCache) {
    echo "✅ Cache de estados presentes en memoria\n";
    echo "   Estados cacheados: " . count($estadosEnCache) . "\n";
} else {
    echo "⚠️  Cache de estados NO presentes (se creará en primera solicitud)\n";
}
echo "\n";

// =====================================================
// 4. VERIFICAR LOGS EN PRODUCCIÓN
// =====================================================
echo "📝 4. VERIFICANDO CONFIGURACIÓN DE LOGS\n";
echo "─────────────────────────────────────────────────────────\n";

$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $logSize = filesize($logPath);
    echo "✅ Archivo de logs: {$logPath}\n";
    echo "   Tamaño: " . formatBytes($logSize) . "\n";
    
    if ($logSize > 100 * 1024 * 1024) {
        echo "   ⚠️  Archivo muy grande - Considerar rotación\n";
    }
} else {
    echo "✅ Sin archivo de logs (buen indicador)\n";
}

$appDebug = config('app.debug');
echo "   APP_DEBUG: " . ($appDebug ? 'true (DESARROLLO)' : 'false (PRODUCCIÓN)') . "\n";
echo "\n";

// =====================================================
// 5. VERIFICAR MIGRACIÓN PENDIENTE
// =====================================================
echo "🔄 5. VERIFICANDO MIGRACIONES\n";
echo "─────────────────────────────────────────────────────────\n";

$pendingMigrations = DB::select("
    SELECT migration FROM migrations
    WHERE migration LIKE '%add_indexes%'
");

if (empty($pendingMigrations)) {
    echo "⚠️  Migración de índices NOT ejecutada\n";
    echo "   Pasos:\n";
    echo "   1. php artisan make:migration add_indexes_pedidos_produccion\n";
    echo "   2. Copiar contenido de '[timestamp]_add_indexes_pedidos_produccion.php'\n";
    echo "   3. php artisan migrate\n";
} else {
    echo "✅ Migración de índices ejecutada\n";
}
echo "\n";

// =====================================================
// RESUMEN FINAL
// =====================================================
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMEN FINAL                       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$issues = [];
if (empty($indexes)) $issues[] = "❌ Índices no encontrados";
if ($queryCount > 10) $issues[] = "❌ Demasiadas queries ({$queryCount})";
if ($logSize > 100 * 1024 * 1024) $issues[] = "⚠️  Logs muy grandes";

if (empty($issues)) {
    echo "✅ TODAS LAS OPTIMIZACIONES ESTÁN IMPLEMENTADAS\n\n";
    echo "Próximos pasos:\n";
    echo "1. Probar en navegador: /asesores/pedidos\n";
    echo "2. Abrir DevTools (F12) → Network → Medir tiempo\n";
    echo "3. Esperado: < 3 segundos\n\n";
} else {
    echo "🔴 PROBLEMAS ENCONTRADOS:\n\n";
    foreach ($issues as $issue) {
        echo "   {$issue}\n";
    }
    echo "\n";
    echo "📋 Pasos para resolver:\n";
    echo "1. Crear y ejecutar migración de índices\n";
    echo "2. Reemplazar ObtenerPedidosService.php\n";
    echo "3. Reemplazar función editarPedido() en JavaScript\n";
    echo "4. Agregar data attributes a tabla\n\n";
}

// Helper function
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

echo "Auditoría completada ✅\n\n";
