<?php
/**
 * Script para consultar estructura de tablas de Logo y Reflectivo en Cotizaciones
 * Muestra donde se guardan tallas y cantidades
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ CONSULTA: Estructura de Tallas y Cantidades en Logo y Reflectivo ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ========================================
// TABLA: logo_cotizaciones
// ========================================
echo "📋 TABLA: logo_cotizaciones\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('logo_cotizaciones')) {
    $columnsLogo = Schema::getColumns('logo_cotizaciones');
    echo "Columnas encontradas:\n";
    foreach ($columnsLogo as $column) {
        echo "  • {$column['name']} ({$column['type_name']})\n";
    }
    
    // Mostrar datos de ejemplo
    $datosLogo = DB::table('logo_cotizaciones')->limit(1)->first();
    echo "\nPrimer registro (si existe):\n";
    if ($datosLogo) {
        foreach ((array)$datosLogo as $key => $value) {
            if (is_string($value) && ($json = @json_decode($value, true)) !== null) {
                echo "  • $key: " . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  • $key: $value\n";
            }
        }
    } else {
        echo "  (Sin registros)\n";
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
    $columnsLogoPrenda = Schema::getColumns('logo_cotizacion_tecnica_prenda');
    echo "Columnas encontradas:\n";
    foreach ($columnsLogoPrenda as $column) {
        echo "  • {$column['name']} ({$column['type_name']})\n";
    }
    
    // Mostrar datos de ejemplo
    $datosLogoPrenda = DB::table('logo_cotizacion_tecnica_prenda')->limit(1)->first();
    echo "\nPrimer registro (si existe):\n";
    if ($datosLogoPrenda) {
        foreach ((array)$datosLogoPrenda as $key => $value) {
            if (is_json($value)) {
                echo "  • $key: " . json_encode(json_decode($value, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  • $key: $value\n";
            }
        }
    } else {
        echo "  (Sin registros)\n";
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
    $columnsReflectivo = Schema::getColumns('reflectivo_cotizacion');
    echo "Columnas encontradas:\n";
    foreach ($columnsReflectivo as $column) {
        echo "  • {$column['name']} ({$column['type_name']})\n";
    }
    
    // Mostrar datos de ejemplo
    $datosReflectivo = DB::table('reflectivo_cotizacion')->limit(1)->first();
    echo "\nPrimer registro (si existe):\n";
    if ($datosReflectivo) {
        foreach ((array)$datosReflectivo as $key => $value) {
            if (is_json($value)) {
                echo "  • $key: " . json_encode(json_decode($value, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  • $key: $value\n";
            }
        }
    } else {
        echo "  (Sin registros)\n";
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
    $columnsPrendaReflectivo = Schema::getColumns('prenda_cot_reflectivo');
    echo "Columnas encontradas:\n";
    foreach ($columnsPrendaReflectivo as $column) {
        echo "  • {$column['name']} ({$column['type_name']})\n";
    }
    
    // Mostrar datos de ejemplo
    $datosPrendaReflectivo = DB::table('prenda_cot_reflectivo')->limit(1)->first();
    echo "\nPrimer registro (si existe):\n";
    if ($datosPrendaReflectivo) {
        foreach ((array)$datosPrendaReflectivo as $key => $value) {
            if (is_json($value)) {
                echo "  • $key: " . json_encode(json_decode($value, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  • $key: $value\n";
            }
        }
    } else {
        echo "  (Sin registros)\n";
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
    $columnsLogTelas = Schema::getColumns('logo_cotizacion_telas_prenda');
    echo "Columnas encontradas:\n";
    foreach ($columnsLogTelas as $column) {
        echo "  • {$column['name']} ({$column['type_name']})\n";
    }
    
    // Mostrar datos de ejemplo
    $datosLogTelas = DB::table('logo_cotizacion_telas_prenda')->limit(1)->first();
    echo "\nPrimer registro (si existe):\n";
    if ($datosLogTelas) {
        foreach ((array)$datosLogTelas as $key => $value) {
            if (is_json($value)) {
                echo "  • $key: " . json_encode(json_decode($value, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  • $key: $value\n";
            }
        }
    } else {
        echo "  (Sin registros)\n";
    }
} else {
    echo "❌ Tabla no encontrada\n";
}

echo "\n";

// ========================================
// RESUMEN: Donde se guardan tallas y cantidades
// ========================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ RESUMEN: ¿DÓNDE SE GUARDAN TALLAS Y CANTIDADES?              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "🔍 LOGO:\n";
echo "   • Tabla principal: logo_cotizaciones\n";
echo "   • Tallas por prenda: logo_cotizacion_tecnica_prenda\n";
echo "   • Telas/Colores: logo_cotizacion_telas_prenda\n";
echo "   → Buscar campos: talla, cantidad, cantidad_estimada, etc.\n\n";

echo "🔍 REFLECTIVO:\n";
echo "   • Tabla principal: reflectivo_cotizacion\n";
echo "   • Por prenda: prenda_cot_reflectivo\n";
echo "   • Información general: reflectivo_cotizacion.observaciones_generales (JSON)\n";
echo "   → Buscar campos: talla, cantidad, cantidad_estimada, etc.\n\n";

echo "✅ Script completado\n\n";
