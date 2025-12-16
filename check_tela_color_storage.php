<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS: DÓNDE SE GUARDAN TELA, COLOR Y DESCRIPCIÓN ===\n\n";

// Obtener algunas prendas con datos
$prendas = DB::table('prendas_pedido')
    ->whereNotNull('tela_id')
    ->orWhereNotNull('color_id')
    ->orWhereNotNull('descripcion_variaciones')
    ->limit(5)
    ->get();

echo "📊 Total de prendas analizadas: " . count($prendas) . "\n\n";

if (count($prendas) > 0) {
    foreach ($prendas as $index => $prenda) {
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║ PRENDA " . ($index + 1) . " - ID: {$prenda->id}\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "📌 CAMPOS CON DATOS:\n";
        echo "─────────────────────────────────────────────────────────────────\n";
        
        // nombre_prenda
        echo "✓ nombre_prenda: {$prenda->nombre_prenda}\n";
        
        // cantidad
        echo "✓ cantidad: {$prenda->cantidad}\n";
        
        // descripcion
        if ($prenda->descripcion) {
            echo "✓ descripcion (LONGTEXT):\n";
            echo "   Primeras 200 chars: " . substr($prenda->descripcion, 0, 200) . "\n";
            echo "   Largo total: " . strlen($prenda->descripcion) . " chars\n";
        } else {
            echo "✗ descripcion: VACÍO\n";
        }
        
        // color_id
        if ($prenda->color_id) {
            echo "✓ color_id: {$prenda->color_id}\n";
            $color = DB::table('colores_prenda')->where('id', $prenda->color_id)->first();
            if ($color) {
                echo "   → Nombre color: {$color->nombre}\n";
            }
        } else {
            echo "✗ color_id: VACÍO/NULL\n";
        }
        
        // tela_id
        if ($prenda->tela_id) {
            echo "✓ tela_id: {$prenda->tela_id}\n";
            $tela = DB::table('telas_prenda')->where('id', $prenda->tela_id)->first();
            if ($tela) {
                echo "   → Nombre tela: {$tela->nombre}\n";
            }
        } else {
            echo "✗ tela_id: VACÍO/NULL\n";
        }
        
        // tipo_manga_id
        if ($prenda->tipo_manga_id) {
            echo "✓ tipo_manga_id: {$prenda->tipo_manga_id}\n";
        } else {
            echo "✗ tipo_manga_id: VACÍO/NULL\n";
        }
        
        // tipo_broche_id
        if ($prenda->tipo_broche_id) {
            echo "✓ tipo_broche_id: {$prenda->tipo_broche_id}\n";
        } else {
            echo "✗ tipo_broche_id: VACÍO/NULL\n";
        }
        
        // tiene_bolsillos
        echo "✓ tiene_bolsillos: " . ($prenda->tiene_bolsillos ? 'SÍ' : 'NO') . "\n";
        
        // tiene_reflectivo
        echo "✓ tiene_reflectivo: " . ($prenda->tiene_reflectivo ? 'SÍ' : 'NO') . "\n";
        
        // descripcion_variaciones
        if ($prenda->descripcion_variaciones) {
            echo "✓ descripcion_variaciones (JSON/LONGTEXT):\n";
            // Intentar parsear como JSON
            $decoded = json_decode($prenda->descripcion_variaciones, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "   ✓ ES JSON VÁLIDO\n";
                echo "   Contenido: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "   ✗ NO es JSON válido (texto plano)\n";
                echo "   Primeras 300 chars: " . substr($prenda->descripcion_variaciones, 0, 300) . "\n";
            }
        } else {
            echo "✗ descripcion_variaciones: VACÍO/NULL\n";
        }
        
        // cantidad_talla
        if ($prenda->cantidad_talla) {
            echo "✓ cantidad_talla (JSON):\n";
            $tallas = json_decode($prenda->cantidad_talla, true);
            if ($tallas) {
                foreach ($tallas as $talla => $cant) {
                    echo "   - $talla: $cant\n";
                }
            }
        } else {
            echo "✗ cantidad_talla: VACÍO/NULL\n";
        }
        
        echo "\n";
    }
} else {
    echo "❌ No hay prendas con datos de tela o color\n\n";
}

echo "\n=== ESTADÍSTICAS ===\n";
echo str_repeat("─", 60) . "\n";

$stats = DB::table('prendas_pedido')
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN tela_id IS NOT NULL THEN 1 ELSE 0 END) as con_tela,
        SUM(CASE WHEN color_id IS NOT NULL THEN 1 ELSE 0 END) as con_color,
        SUM(CASE WHEN descripcion IS NOT NULL AND descripcion != "" THEN 1 ELSE 0 END) as con_descripcion,
        SUM(CASE WHEN descripcion_variaciones IS NOT NULL AND descripcion_variaciones != "" THEN 1 ELSE 0 END) as con_variaciones,
        SUM(CASE WHEN cantidad_talla IS NOT NULL AND cantidad_talla != "" THEN 1 ELSE 0 END) as con_tallas
    ')
    ->first();

echo "Total prendas_pedido: {$stats->total}\n";
echo "Con tela_id: {$stats->con_tela} (" . round(($stats->con_tela/$stats->total)*100, 1) . "%)\n";
echo "Con color_id: {$stats->con_color} (" . round(($stats->con_color/$stats->total)*100, 1) . "%)\n";
echo "Con descripcion: {$stats->con_descripcion} (" . round(($stats->con_descripcion/$stats->total)*100, 1) . "%)\n";
echo "Con descripcion_variaciones: {$stats->con_variaciones} (" . round(($stats->con_variaciones/$stats->total)*100, 1) . "%)\n";
echo "Con cantidad_talla: {$stats->con_tallas} (" . round(($stats->con_tallas/$stats->total)*100, 1) . "%)\n";

echo "\n=== RESPUESTA A TU PREGUNTA ===\n";
echo str_repeat("─", 60) . "\n";
echo "¿Dónde se guarda la referencia de tela?\n";
echo "✓ En columna: tela_id (BIGINT UNSIGNED)\n";
echo "✓ Relaciona con: telas_prenda.id\n";
echo "\n¿Dónde se guarda color?\n";
echo "✓ En columna: color_id (BIGINT UNSIGNED)\n";
echo "✓ Relaciona con: colores_prenda.id\n";
echo "\n¿Qué contiene descripcion_variaciones?\n";
if ($stats->con_variaciones > 0) {
    echo "✓ Almacena información adicional (JSON o texto)\n";
    echo "   Se usa en: " . round(($stats->con_variaciones/$stats->total)*100, 1) . "% de prendas\n";
} else {
    echo "✗ NO se usa (todas vacías)\n";
}
