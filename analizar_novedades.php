<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ANÁLISIS DE TABLAS PARA GUARDAR NOVEDADES ===\n\n";

// Tablas a analizar
$tablasAnalizar = [
    'proceso_prenda',
    'pedidos_produccion',
    'tabla_original_bodega'
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
    
    // Obtener claves foráneas
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

// Ver la relación entre proceso_prenda y pedidos_produccion
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ ANÁLISIS: Relación proceso_prenda ↔ pedidos_produccion\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Buscar en modelo ProcesoPrenda
$procesoPrendaModel = app(App\Models\ProcesoPrenda::class);
echo "📄 Modelo: App\Models\ProcesoPrenda\n";
echo "   Archivo: app/Models/ProcesoPrenda.php\n";

if (Schema::hasTable('proceso_prenda')) {
    $sample = DB::table('proceso_prenda')->first();
    if ($sample) {
        echo "\n📊 MUESTRA DE REGISTRO:\n";
        foreach ((array)$sample as $column => $value) {
            echo "   • $column: " . substr((string)$value, 0, 50) . "\n";
        }
    }
}

// Verificar si proceso_prenda tiene campo novedades
echo "\n🔍 ¿Existe campo 'novedades' en proceso_prenda?\n";
if (Schema::hasColumn('proceso_prenda', 'novedades')) {
    echo "   ✅ SÍ existe\n";
} else {
    echo "   ❌ NO existe - NECESITA MIGRACIÓN\n";
}

// Verificar en tabla_original_bodega
if (Schema::hasTable('tabla_original_bodega')) {
    echo "\n🔍 ¿Existe campo 'novedades' en tabla_original_bodega?\n";
    if (Schema::hasColumn('tabla_original_bodega', 'novedades')) {
        echo "   ✅ SÍ existe\n";
    } else {
        echo "   ❌ NO existe - NECESITA MIGRACIÓN\n";
    }
}

// Ver el código del método reportarNovedad
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ ANÁLISIS: Método reportarNovedad() actual\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Ubicación: app/Infrastructure/Http/Controllers/Operario/OperarioController.php\n";
echo "Método: reportarNovedad()\n";
echo "Estado actual: Guarda solo estado a 'Pendiente'\n";
echo "Acción requerida: Guardar también la novedad en campo novedades\n";

echo "\n✅ Análisis completado\n";
