<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ANÁLISIS DE TABLAS NO UTILIZADAS ===\n\n";

// Obtener todas las tablas
$database = config('database.connections.mysql.database');
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_' . $database;

$allTables = [];
foreach ($tables as $table) {
    $allTables[] = $table->$tableKey;
}

echo "📊 Total de tablas: " . count($allTables) . "\n\n";

// Tablas que se sabe que se usan (basado en modelos y relaciones)
$tablasUsadas = [
    // Sistema base
    'users',
    'roles',
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
    'password_reset_tokens',
    'sessions',
    
    // Producción
    'prendas',
    'balanceos',
    'operaciones_balanceo',
    'registro_piso_produccion',
    'registro_piso_polo',
    'registro_piso_corte',
    'tiempo_ciclos',
    'horas',
    'maquinas',
    'telas',
    'procesos_prenda',
    
    // Órdenes de asesor
    'ordenes_asesores',
    'productos_pedido',
    
    // Tabla original (legacy)
    'tabla_original',
    'tabla_original_bodega',
    'registros_por_orden',
    'registros_por_orden_bodega',
    
    // Entregas
    'entrega_pedido_corte',
    'entrega_bodega_corte',
    'entregas_pedido_costura',
    'entregas_bodega_costura',
    
    // Noticias
    'news',
    
    // Festivos
    'festivos',
    
    // Inventario
    'inventario_telas',
    'inventario_telas_historial',
    
    // Cotizaciones (nuevas)
    'cotizaciones',
    'prendas_cotizaciones',
    'logo_cotizaciones',
    'historial_cotizaciones',
    'pedidos_produccion',
    'prendas_pedido',
    
    // Clientes y reportes
    'clientes',
    'reportes',
    
    // Catálogos
    'catalogo_colores',
    'catalogo_hilos',
    'catalogo_telas',
    'categorias_prendas',
    'tipos_prendas',
    'producto_imagenes',
    
    // Variantes
    'tipos_prenda',
    'prenda_variaciones_disponibles',
    'colores_prenda',
    'telas_prenda',
    'generos_prenda',
    'tipos_manga',
    'tipos_broche',
    'variantes_prenda',
    'tipos_cotizacion',
];

// Encontrar tablas no utilizadas
$tablasNoUsadas = array_diff($allTables, $tablasUsadas);
$tablasUsadasEnBD = array_intersect($allTables, $tablasUsadas);

echo "✅ TABLAS UTILIZADAS: " . count($tablasUsadasEnBD) . "\n";
foreach ($tablasUsadasEnBD as $tabla) {
    echo "   ✓ $tabla\n";
}

echo "\n⚠️  TABLAS NO UTILIZADAS: " . count($tablasNoUsadas) . "\n";
if (count($tablasNoUsadas) > 0) {
    foreach ($tablasNoUsadas as $tabla) {
        // Contar registros
        $count = DB::table($tabla)->count();
        echo "   ✗ $tabla (registros: $count)\n";
    }
    
    echo "\n💡 RECOMENDACIÓN:\n";
    echo "Estas tablas pueden ser eliminadas si no se usan en la aplicación.\n";
    echo "Asegúrate de que no haya referencias en modelos o migraciones antes de eliminarlas.\n";
} else {
    echo "   ✓ Todas las tablas se están utilizando\n";
}

echo "\n✅ Análisis completado\n";
