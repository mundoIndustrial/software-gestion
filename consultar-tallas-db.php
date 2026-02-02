<?php
/**
 * Script para consultar estructura de tablas de Logo y Reflectivo en Cotizaciones
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ Estructura de Tablas: LOGO y REFLECTIVO en Cotizaciones      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Helper para imprimir JSON
function printValue($value) {
    if (is_string($value)) {
        $json = @json_decode($value, true);
        if ($json !== null) {
            return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
    return $value;
}

// ========================================
// TABLA: logo_cotizaciones
// ========================================
echo "📋 TABLA: logo_cotizaciones\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('logo_cotizaciones')) {
    $columns = Schema::getColumns('logo_cotizaciones');
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  • {$col['name']} ({$col['type_name']})\n";
    }
    
    $count = DB::table('logo_cotizaciones')->count();
    echo "\nTotal registros: $count\n";
    
    if ($count > 0) {
        $data = DB::table('logo_cotizaciones')->first();
        echo "\nPrimer registro:\n";
        foreach ((array)$data as $key => $value) {
            echo "  • $key: " . printValue($value) . "\n";
        }
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n";

// ========================================
// TABLA: logo_cotizacion_tecnica_prenda
// ========================================
echo "📋 TABLA: logo_cotizacion_tecnica_prenda\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('logo_cotizacion_tecnica_prenda')) {
    $columns = Schema::getColumns('logo_cotizacion_tecnica_prenda');
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  • {$col['name']} ({$col['type_name']})\n";
    }
    
    $count = DB::table('logo_cotizacion_tecnica_prenda')->count();
    echo "\nTotal registros: $count\n";
    
    if ($count > 0) {
        $data = DB::table('logo_cotizacion_tecnica_prenda')->first();
        echo "\nPrimer registro:\n";
        foreach ((array)$data as $key => $value) {
            echo "  • $key: " . printValue($value) . "\n";
        }
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n";

// ========================================
// TABLA: reflectivo_cotizacion
// ========================================
echo "📋 TABLA: reflectivo_cotizacion\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('reflectivo_cotizacion')) {
    $columns = Schema::getColumns('reflectivo_cotizacion');
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  • {$col['name']} ({$col['type_name']})\n";
    }
    
    $count = DB::table('reflectivo_cotizacion')->count();
    echo "\nTotal registros: $count\n";
    
    if ($count > 0) {
        $data = DB::table('reflectivo_cotizacion')->first();
        echo "\nPrimer registro:\n";
        foreach ((array)$data as $key => $value) {
            echo "  • $key: " . printValue($value) . "\n";
        }
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n";

// ========================================
// TABLA: prenda_cot_reflectivo
// ========================================
echo "📋 TABLA: prenda_cot_reflectivo\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('prenda_cot_reflectivo')) {
    $columns = Schema::getColumns('prenda_cot_reflectivo');
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  • {$col['name']} ({$col['type_name']})\n";
    }
    
    $count = DB::table('prenda_cot_reflectivo')->count();
    echo "\nTotal registros: $count\n";
    
    if ($count > 0) {
        $data = DB::table('prenda_cot_reflectivo')->first();
        echo "\nPrimer registro:\n";
        foreach ((array)$data as $key => $value) {
            echo "  • $key: " . printValue($value) . "\n";
        }
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n";

// ========================================
// TABLA: logo_cotizacion_telas_prenda
// ========================================
echo "📋 TABLA: logo_cotizacion_telas_prenda\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('logo_cotizacion_telas_prenda')) {
    $columns = Schema::getColumns('logo_cotizacion_telas_prenda');
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  • {$col['name']} ({$col['type_name']})\n";
    }
    
    $count = DB::table('logo_cotizacion_telas_prenda')->count();
    echo "\nTotal registros: $count\n";
    
    if ($count > 0) {
        $data = DB::table('logo_cotizacion_telas_prenda')->first();
        echo "\nPrimer registro:\n";
        foreach ((array)$data as $key => $value) {
            echo "  • $key: " . printValue($value) . "\n";
        }
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ RESUMEN: ¿DÓNDE SE GUARDAN TALLAS Y CANTIDADES?              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "🔍 LOGO:\n";
echo "   • Tabla principal: logo_cotizaciones\n";
echo "   • Prendas por técnica: logo_cotizacion_tecnica_prenda\n";
echo "   • Telas y colores: logo_cotizacion_telas_prenda\n";
echo "   → Buscar campos: talla, cantidad, cantidad_estimada\n\n";

echo "🔍 REFLECTIVO:\n";
echo "   • Tabla principal: reflectivo_cotizacion\n";
echo "   • Por prenda: prenda_cot_reflectivo\n";
echo "   → Buscar campos: talla, cantidad, cantidad_estimada\n\n";

echo "✅ Script completado\n\n";
