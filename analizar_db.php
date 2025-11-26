<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║            ANÁLISIS DE INTEGRIDAD DE BASE DE DATOS                            ║\n";
echo "║            Verificación de Migraciones vs Estado Actual                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

// ========================================
// 1. TABLA: pedidos_produccion
// ========================================
echo "1️⃣  TABLA: pedidos_produccion\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$columns_esperadas = [
    'id' => 'bigint',
    'numero_pedido' => 'string|UNIQUE',
    'cliente_id' => 'bigint',
    'asesor_id' => 'bigint',
    'fecha_de_creacion_de_orden' => 'date',
    'fecha_estimada_de_entrega' => 'date',
    'estado' => 'enum/string',
    'area_actual' => 'string|nullable',
    'encargado_actual' => 'string|nullable'
];

if (Schema::hasTable('pedidos_produccion')) {
    echo "✓ Tabla EXISTS\n\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pedidos_produccion' AND TABLE_SCHEMA = DATABASE()");
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $col) {
        $key = $col->COLUMN_KEY ? " [KEY: $col->COLUMN_KEY]" : "";
        $nullable = $col->IS_NULLABLE === 'YES' ? " [NULL]" : " [NOT NULL]";
        echo "  • {$col->COLUMN_NAME}: {$col->COLUMN_TYPE}{$nullable}{$key}\n";
    }
    
    $count = DB::table('pedidos_produccion')->count();
    echo "\nRegistros en tabla: $count\n";
    
    // Verificar integridad de datos
    echo "\n✓ Verificando integridad de datos:\n";
    $sin_numero = DB::table('pedidos_produccion')->whereNull('numero_pedido')->count();
    echo "  • Órdenes sin numero_pedido: $sin_numero\n";
    
    $duplicados = DB::table('pedidos_produccion')
        ->select('numero_pedido', DB::raw('COUNT(*) as count'))
        ->groupBy('numero_pedido')
        ->having(DB::raw('COUNT(*)'), '>', 1)
        ->count();
    echo "  • Números de pedido duplicados: $duplicados\n";
    
    if ($duplicados > 0) {
        echo "  ⚠️  ADVERTENCIA: Hay duplicados en numero_pedido\n";
        $dups = DB::table('pedidos_produccion')
            ->select('numero_pedido', DB::raw('COUNT(*) as count'))
            ->groupBy('numero_pedido')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();
        foreach ($dups as $dup) {
            echo "     - {$dup->numero_pedido}: {$dup->count} registros\n";
        }
    }
} else {
    echo "❌ Tabla NO EXISTE - Necesita ser creada\n";
}

echo "\n";

// ========================================
// 2. TABLA: prendas_pedido
// ========================================
echo "2️⃣  TABLA: prendas_pedido\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if (Schema::hasTable('prendas_pedido')) {
    echo "✓ Tabla EXISTS\n\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'prendas_pedido' AND TABLE_SCHEMA = DATABASE() ORDER BY ORDINAL_POSITION");
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $col) {
        $nullable = $col->IS_NULLABLE === 'YES' ? " [NULL]" : " [NOT NULL]";
        echo "  • {$col->COLUMN_NAME}: {$col->COLUMN_TYPE}{$nullable}\n";
    }
    
    $count = DB::table('prendas_pedido')->count();
    echo "\nRegistros en tabla: $count\n";
    
    // Verificar estructura esperada
    echo "\n✓ Verificando estructura esperada:\n";
    
    $campos_esperados = [
        'color_id' => 'Campo de color',
        'tela_id' => 'Campo de tela',
        'tipo_manga_id' => 'Campo de tipo de manga',
        'tipo_broche_id' => 'Campo de tipo de broche',
        'cantidad_talla' => 'Campo JSON para cantidad por talla',
        'tiene_bolsillos' => 'Campo booleano',
        'tiene_reflectivo' => 'Campo booleano',
        'descripcion_variaciones' => 'Campo de descripción'
    ];
    
    foreach ($campos_esperados as $campo => $descripcion) {
        if (Schema::hasColumn('prendas_pedido', $campo)) {
            echo "  ✓ {$campo} EXISTS - $descripcion\n";
        } else {
            echo "  ❌ {$campo} FALTA - $descripcion\n";
        }
    }
    
} else {
    echo "❌ Tabla NO EXISTE\n";
}

echo "\n";

// ========================================
// 3. TABLA: procesos_prenda
// ========================================
echo "3️⃣  TABLA: procesos_prenda\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if (Schema::hasTable('procesos_prenda')) {
    echo "✓ Tabla EXISTS\n\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'procesos_prenda' AND TABLE_SCHEMA = DATABASE()");
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $col) {
        $key = $col->COLUMN_KEY ? " [KEY: $col->COLUMN_KEY]" : "";
        $nullable = $col->IS_NULLABLE === 'YES' ? " [NULL]" : " [NOT NULL]";
        echo "  • {$col->COLUMN_NAME}: {$col->COLUMN_TYPE}{$nullable}{$key}\n";
    }
    
    $count = DB::table('procesos_prenda')->count();
    echo "\nRegistros en tabla: $count\n";
    
    // Verificar relación correcta
    echo "\n✓ Verificando relación con pedidos_produccion:\n";
    
    // Verificar que usa numero_pedido (no prenda_pedido_id)
    if (Schema::hasColumn('procesos_prenda', 'numero_pedido')) {
        echo "  ✓ Campo numero_pedido EXISTS\n";
        
        $sin_numero = DB::table('procesos_prenda')->whereNull('numero_pedido')->count();
        echo "    - Registros sin numero_pedido: $sin_numero\n";
        
        // Verificar foreign key
        $fks = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'procesos_prenda' AND TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        
        if (count($fks) > 0) {
            echo "    - Foreign keys:\n";
            foreach ($fks as $fk) {
                echo "      • {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}\n";
            }
        } else {
            echo "    ⚠️  ADVERTENCIA: No hay foreign keys definidas\n";
        }
    } else {
        echo "  ❌ Campo numero_pedido FALTA\n";
    }
    
    if (Schema::hasColumn('procesos_prenda', 'prenda_pedido_id')) {
        echo "  ⚠️  ADVERTENCIA: Campo prenda_pedido_id aún EXISTS (debería estar eliminado)\n";
    }
    
    // Verificar que proceso sea VARCHAR (no ENUM)
    $procesoType = DB::select("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'procesos_prenda' AND COLUMN_NAME = 'proceso' AND TABLE_SCHEMA = DATABASE()");
    if ($procesoType) {
        $type = $procesoType[0]->COLUMN_TYPE;
        echo "  ✓ Campo 'proceso' es: $type\n";
        if (strpos($type, 'enum') !== false) {
            echo "    ⚠️  ADVERTENCIA: Aún es ENUM, debería ser VARCHAR\n";
        }
    }
    
} else {
    echo "❌ Tabla NO EXISTE\n";
}

echo "\n";

// ========================================
// 4. TABLA: festivos
// ========================================
echo "4️⃣  TABLA: festivos\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if (Schema::hasTable('festivos')) {
    echo "✓ Tabla EXISTS\n\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'festivos' AND TABLE_SCHEMA = DATABASE()");
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $col) {
        echo "  • {$col->COLUMN_NAME}: {$col->COLUMN_TYPE}\n";
    }
    
    $count = DB::table('festivos')->count();
    echo "\nRegistros en tabla: $count\n";
} else {
    echo "⚠️  Tabla NO EXISTE (necesaria para cálculo de días hábiles)\n";
}

echo "\n";

// ========================================
// 5. RESUMEN Y RECOMENDACIONES
// ========================================
echo "═════════════════════════════════════════════════════════════════════════════════\n";
echo "5️⃣  RESUMEN Y RECOMENDACIONES\n";
echo "═════════════════════════════════════════════════════════════════════════════════\n\n";

$issues = [];

// Verificar integridad referencial
echo "✓ Verificando integridad referencial:\n\n";

if (Schema::hasTable('procesos_prenda') && Schema::hasTable('pedidos_produccion')) {
    $procesos_sin_orden = DB::table('procesos_prenda')
        ->leftJoin('pedidos_produccion', 'procesos_prenda.numero_pedido', '=', 'pedidos_produccion.numero_pedido')
        ->whereNull('pedidos_produccion.numero_pedido')
        ->count();
    
    if ($procesos_sin_orden > 0) {
        echo "  ❌ CRÍTICO: Hay $procesos_sin_orden procesos sin orden asociada\n";
        $issues[] = "Procesos huérfanos detectados";
    } else {
        echo "  ✓ Todos los procesos tienen orden asociada\n";
    }
}

if (Schema::hasTable('prendas_pedido') && Schema::hasTable('pedidos_produccion')) {
    $prendas_sin_orden = DB::table('prendas_pedido')
        ->leftJoin('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
        ->whereNull('pedidos_produccion.id')
        ->count();
    
    if ($prendas_sin_orden > 0) {
        echo "  ❌ CRÍTICO: Hay $prendas_sin_orden prendas sin orden asociada\n";
        $issues[] = "Prendas huérfanas detectadas";
    } else {
        echo "  ✓ Todas las prendas tienen orden asociada\n";
    }
}

echo "\n";

if (count($issues) === 0) {
    echo "✅ BASE DE DATOS EN ESTADO CORRECTO\n";
    echo "   Las migraciones son seguras de aplicar\n";
} else {
    echo "⚠️  PROBLEMAS DETECTADOS:\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
}

echo "\n";
?>
