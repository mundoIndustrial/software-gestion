<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== REPARACIÓN COMPLETA DE MIGRACIONES ===\n\n";

// Tablas que ya existen en la BD
$tablasExistentes = [
    'categorias_prendas',
    'tipos_prendas',
    'colores_prenda',
    'telas_prenda',
    'generos_prenda',
    'tipos_manga',
    'tipos_broche',
    'variantes_prenda',
    'cotizaciones',
    'pedidos_produccion',
    'clientes',
    'reportes',
    'logo_cotizaciones',
    'prendas_cotizaciones',
    'historial_cotizaciones',
    'inventario_telas',
    'inventario_telas_historial',
    'producto_imagenes',
];

// Mapeo de migraciones a tablas
$migracionesATablas = [
    '2025_11_10_150946_create_categorias_prendas_table' => 'categorias_prendas',
    '2025_11_10_150946_create_tipos_prendas_table' => 'tipos_prendas',
    '2025_11_10_152858_create_catalogo_colores_table' => 'colores_prenda',
    '2025_11_10_152858_create_catalogo_telas_table' => 'telas_prenda',
    '2025_11_22_000005_create_generos_prenda_table' => 'generos_prenda',
    '2025_11_22_000006_create_tipos_manga_table' => 'tipos_manga',
    '2025_11_22_000007_create_tipos_broche_table' => 'tipos_broche',
    '2025_11_22_000008_create_variantes_prenda_table' => 'variantes_prenda',
    '2025_11_19_105041_create_cotizaciones_table' => 'cotizaciones',
    '2025_11_19_110000_create_pedidos_produccion_table' => 'pedidos_produccion',
    '2025_11_19_110228_create_clientes_table' => 'clientes',
    '2025_11_19_110233_create_reportes_table' => 'reportes',
    '2025_11_20_create_logo_cotizaciones_table' => 'logo_cotizaciones',
    '2025_11_20_create_prendas_cotizaciones_table' => 'prendas_cotizaciones',
    '2025_11_21_create_historial_cotizaciones_table' => 'historial_cotizaciones',
    '2025_11_10_164512_create_inventario_telas_table' => 'inventario_telas',
    '2025_11_10_175226_create_inventario_telas_historial_table' => 'inventario_telas_historial',
    '2025_11_10_152859_create_producto_imagenes_table' => 'producto_imagenes',
];

$marcadas = 0;
$yaExisten = 0;

echo "🔍 Verificando migraciones...\n\n";

foreach ($migracionesATablas as $migracion => $tabla) {
    if (Schema::hasTable($tabla)) {
        $existe = DB::table('migrations')->where('migration', $migracion)->exists();
        
        if (!$existe) {
            DB::table('migrations')->insert([
                'migration' => $migracion,
                'batch' => 1
            ]);
            echo "✅ Marcada como ejecutada: $migracion\n";
            $marcadas++;
        } else {
            echo "⏭️  Ya existe en migrations: $migracion\n";
            $yaExisten++;
        }
    } else {
        echo "⚠️  Tabla '$tabla' no existe - Migración: $migracion\n";
    }
}

echo "\n📊 RESUMEN:\n";
echo "✅ Marcadas como ejecutadas: $marcadas\n";
echo "⏭️  Ya existían: $yaExisten\n";
echo "\n✅ Reparación completada\n";
