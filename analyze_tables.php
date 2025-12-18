<?php

/**
 * Script para analizar tablas de la base de datos y recomendar índices
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE TABLAS PARA ÍNDICES ===\n\n";

// Tablas a analizar
$tablasRelevantes = [
    'cotizaciones',
    'prendas_cot',
    'reflectivo_cotizacion',
    'reflectivo_fotos_cotizacion',
    'prenda_fotos_cot',
    'prenda_tela_fotos_cot',
    'prenda_tallas_cot',
    'talla_prenda_cot',
    'prenda_variantes_cot',
    'logo_cotizacion',
    'logo_fotos_cotizacion'
];

foreach ($tablasRelevantes as $tabla) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TABLA: {$tabla}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    try {
        // Verificar si la tabla existe
        $exists = DB::select("SHOW TABLES LIKE '{$tabla}'");
        
        if (empty($exists)) {
            echo "❌ Tabla NO existe\n\n";
            continue;
        }
        
        echo "✅ Tabla existe\n\n";
        
        // Obtener estructura de la tabla
        echo "COLUMNAS:\n";
        $columns = DB::select("DESCRIBE {$tabla}");
        foreach ($columns as $col) {
            $key = $col->Key ? " [{$col->Key}]" : "";
            echo "  - {$col->Field} ({$col->Type}){$key}\n";
        }
        
        echo "\nÍNDICES ACTUALES:\n";
        $indexes = DB::select("SHOW INDEX FROM {$tabla}");
        
        if (empty($indexes)) {
            echo "  ⚠️ Sin índices\n";
        } else {
            $indexGroups = [];
            foreach ($indexes as $idx) {
                $indexGroups[$idx->Key_name][] = $idx->Column_name;
            }
            
            foreach ($indexGroups as $indexName => $columns) {
                $colList = implode(', ', $columns);
                echo "  - {$indexName}: ({$colList})\n";
            }
        }
        
        // Contar registros
        $count = DB::table($tabla)->count();
        echo "\nREGISTROS: {$count}\n";
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== RECOMENDACIONES DE ÍNDICES ===\n\n";

echo "Basado en las relaciones de cotizaciones reflectivo:\n\n";

echo "1. cotizaciones:\n";
echo "   - INDEX (asesor_id) - para filtrar por asesor\n";
echo "   - INDEX (tipo) - para filtrar por tipo (RF, PL, etc)\n";
echo "   - INDEX (es_borrador) - para filtrar borradores\n";
echo "   - INDEX (asesor_id, tipo, es_borrador) - índice compuesto\n\n";

echo "2. prendas_cot:\n";
echo "   - INDEX (cotizacion_id) - para joins con cotizaciones\n\n";

echo "3. reflectivo_cotizacion:\n";
echo "   - INDEX (cotizacion_id) - para joins con cotizaciones\n";
echo "   - INDEX (prenda_cot_id) - para joins con prendas\n";
echo "   - INDEX (cotizacion_id, prenda_cot_id) - índice compuesto\n\n";

echo "4. reflectivo_fotos_cotizacion:\n";
echo "   - INDEX (reflectivo_cotizacion_id) - para cargar fotos\n\n";

echo "5. prenda_fotos_cot:\n";
echo "   - INDEX (prenda_cot_id) - para cargar fotos de prenda\n\n";

echo "6. prenda_tela_fotos_cot:\n";
echo "   - INDEX (prenda_cot_id) - para cargar fotos de tela\n\n";

echo "7. prenda_tallas_cot o talla_prenda_cot (verificar cuál existe):\n";
echo "   - INDEX (prenda_cot_id) - para cargar tallas\n\n";

echo "8. prenda_variantes_cot:\n";
echo "   - INDEX (prenda_cot_id) - para cargar variantes\n\n";

echo "=== FIN DEL ANÁLISIS ===\n";
