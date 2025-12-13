<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ANÁLISIS DE ESTRUCTURA DE TABLAS ===\n\n";

// Tablas a analizar
$tablasAnalizar = [
    'pedidos_produccion',
    'prendas_cot',
    'prenda_fotos_cot',
    'prenda_tela_fotos_cot',
    'logo_cotizaciones',
    'logo_fotos_cot',
    'proceso_prenda'
];

foreach ($tablasAnalizar as $tabla) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║ TABLA: $tabla\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    
    // Verificar si la tabla existe
    if (!Schema::hasTable($tabla)) {
        echo "❌ TABLA NO EXISTE\n\n";
        continue;
    }
    
    // Contar registros
    $count = DB::table($tabla)->count();
    echo "📊 REGISTROS: $count\n\n";
    
    // Obtener columnas
    echo "📋 COLUMNAS:\n";
    $columns = DB::select("SHOW COLUMNS FROM $tabla");
    foreach ($columns as $col) {
        echo "   • {$col->Field} ({$col->Type})";
        if ($col->Null === 'NO') echo " [NOT NULL]";
        if ($col->Key === 'PRI') echo " [PRIMARY KEY]";
        if ($col->Key === 'MUL') echo " [INDEX]";
        echo "\n";
    }
    
    // Obtener índices
    echo "\n🔍 ÍNDICES:\n";
    $indexes = DB::select("SHOW INDEX FROM $tabla");
    $indexNames = [];
    foreach ($indexes as $idx) {
        $key = $idx->Key_name;
        if (!isset($indexNames[$key])) {
            $indexNames[$key] = [
                'columns' => [],
                'unique' => $idx->Non_unique == 0 ? 'SÍ' : 'NO'
            ];
        }
        $indexNames[$key]['columns'][] = $idx->Column_name;
    }
    
    foreach ($indexNames as $name => $info) {
        $cols = implode(', ', $info['columns']);
        echo "   • $name ($cols) - Único: {$info['unique']}\n";
    }
    
    // Obtener relaciones (Foreign Keys)
    echo "\n🔗 CLAVES FORÁNEAS:\n";
    $fkeys = DB::select("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$tabla' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if (count($fkeys) > 0) {
        foreach ($fkeys as $fk) {
            echo "   • {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
        }
    } else {
        echo "   • Sin relaciones\n";
    }
    
    echo "\n";
}

// Análisis de las consultas del controlador
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ ANÁLISIS DE CONSULTAS - obtenerFotosPedido()\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "CONSULTA 1: SELECT numero_pedido FROM pedidos_produccion WHERE numero_pedido = ?\n";
$testNumPedido = 45452;
$pedido = DB::table('pedidos_produccion')
    ->select('id', 'cotizacion_id')
    ->where('numero_pedido', $testNumPedido)
    ->first();

if ($pedido) {
    echo "✅ RESULTADO: Pedido encontrado\n";
    echo "   - ID: {$pedido->id}\n";
    echo "   - Cotización ID: {$pedido->cotizacion_id}\n\n";
    
    echo "CONSULTA 2: SELECT id FROM prendas_cot WHERE cotizacion_id = ?\n";
    $prendasIds = DB::table('prendas_cot')
        ->where('cotizacion_id', $pedido->cotizacion_id)
        ->pluck('id')
        ->toArray();
    
    echo "✅ RESULTADO: Se encontraron " . count($prendasIds) . " prendas\n";
    echo "   - IDs: " . implode(', ', $prendasIds) . "\n\n";
    
    if (!empty($prendasIds)) {
        echo "CONSULTA 3: SELECT * FROM prenda_fotos_cot WHERE prenda_cot_id IN (...)\n";
        $fotosPrendas = DB::table('prenda_fotos_cot')
            ->select('ruta_webp', 'ruta_original')
            ->whereIn('prenda_cot_id', $prendasIds)
            ->get();
        echo "✅ RESULTADO: " . count($fotosPrendas) . " fotos de prendas\n";
        foreach ($fotosPrendas as $foto) {
            echo "   • " . ($foto->ruta_webp ?: $foto->ruta_original) . "\n";
        }
        
        echo "\nCONSULTA 4: SELECT * FROM prenda_tela_fotos_cot WHERE prenda_cot_id IN (...)\n";
        $fotosTelas = DB::table('prenda_tela_fotos_cot')
            ->select('ruta_webp', 'ruta_original')
            ->whereIn('prenda_cot_id', $prendasIds)
            ->get();
        echo "✅ RESULTADO: " . count($fotosTelas) . " fotos de telas\n";
        foreach ($fotosTelas as $foto) {
            echo "   • " . ($foto->ruta_webp ?: $foto->ruta_original) . "\n";
        }
        
        echo "\nCONSULTA 5: SELECT id FROM logo_cotizaciones WHERE cotizacion_id = ?\n";
        $logoIds = DB::table('logo_cotizaciones')
            ->select('id')
            ->where('cotizacion_id', $pedido->cotizacion_id)
            ->pluck('id')
            ->toArray();
        echo "✅ RESULTADO: " . count($logoIds) . " logos\n";
        echo "   - IDs: " . implode(', ', $logoIds) . "\n\n";
        
        if (!empty($logoIds)) {
            echo "CONSULTA 6: SELECT * FROM logo_fotos_cot WHERE logo_cotizacion_id IN (...)\n";
            $fotosLogos = DB::table('logo_fotos_cot')
                ->select('ruta_webp', 'ruta_original')
                ->whereIn('logo_cotizacion_id', $logoIds)
                ->get();
            echo "✅ RESULTADO: " . count($fotosLogos) . " fotos de logos\n";
            foreach ($fotosLogos as $foto) {
                echo "   • " . ($foto->ruta_webp ?: $foto->ruta_original) . "\n";
            }
        } else {
            echo "⚠️ Sin logos para esta cotización\n";
        }
    }
} else {
    echo "❌ RESULTADO: No se encontró el pedido #$testNumPedido\n";
    echo "Mostrando pedidos disponibles:\n";
    $pedidosMuestra = DB::table('pedidos_produccion')
        ->select('id', 'numero_pedido', 'cotizacion_id')
        ->limit(5)
        ->get();
    foreach ($pedidosMuestra as $p) {
        echo "   • Pedido: {$p->numero_pedido} (ID: {$p->id}, Cot: {$p->cotizacion_id})\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ RESUMEN DE ÍNDICES RECOMENDADOS\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$indexesRecomendados = [
    'pedidos_produccion' => ['numero_pedido'],
    'prendas_cot' => ['cotizacion_id'],
    'prenda_fotos_cot' => ['prenda_cot_id'],
    'prenda_tela_fotos_cot' => ['prenda_cot_id'],
    'logo_cotizaciones' => ['cotizacion_id'],
    'logo_fotos_cot' => ['logo_cotizacion_id'],
    'proceso_prenda' => ['numero_pedido', ['proceso', 'encargado']]
];

foreach ($indexesRecomendados as $tabla => $cols) {
    echo "ALTER TABLE $tabla ADD";
    $primero = true;
    foreach ($cols as $col) {
        if (!$primero) echo ",";
        if (is_array($col)) {
            echo " INDEX idx_" . implode('_', $col) . " (" . implode(', ', $col) . ")";
        } else {
            echo " INDEX idx_$col ($col)";
        }
        $primero = false;
    }
    echo ";\n";
}

echo "\n✅ Análisis completado\n";
