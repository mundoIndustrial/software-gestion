<?php
/**
 * analizar-base-datos.php
 * 
 * Script para analizar la estructura de la base de datos
 * y generar un reporte sobre cómo guardar los procesos de pedidos
 * 
 * Uso: php analizar-base-datos.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE BASE DE DATOS - ESTRUCTURA ACTUAL\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // 1. Listar todas las tablas
    echo "1️⃣  TABLAS EN LA BASE DE DATOS\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    
    foreach ($tables as $table) {
        echo "   • {$table->TABLE_NAME}\n";
    }
    
    echo "\n";
    
    // 2. Analizar tablas clave
    $keyTables = ['pedidos', 'pedido_items', 'pedido_prendas', 'reflectivo', 'estampado', 'bordado'];
    
    foreach ($keyTables as $tableName) {
        $tableExists = DB::selectOne("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$tableName]);
        
        if ($tableExists) {
            echo "2️⃣  TABLA: {$tableName}\n";
            echo "─────────────────────────────────────────────────────────\n";
            
            $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION", [$tableName]);
            
            echo sprintf("%-25s %-30s %-10s %-10s\n", "Columna", "Tipo", "Nullable", "Key");
            echo "─────────────────────────────────────────────────────────\n";
            
            foreach ($columns as $col) {
                $nullable = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
                echo sprintf("%-25s %-30s %-10s %-10s\n", 
                    $col->COLUMN_NAME,
                    $col->COLUMN_TYPE,
                    $nullable,
                    $col->COLUMN_KEY ?: '-'
                );
            }
            
            echo "\n";
        }
    }
    
    // 3. Relaciones foráneas
    echo "3️⃣  RELACIONES FORÁNEAS\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $fks = DB::select("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME
    ");
    
    if (count($fks) > 0) {
        foreach ($fks as $fk) {
            echo "   • {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
        }
    } else {
        echo "   ⚠️  No se encontraron relaciones foráneas\n";
    }
    
    echo "\n";
    
    // 4. Analizar estructura actual de procesos
    echo "4️⃣  ANÁLISIS DE TABLAS DE PROCESOS\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $processTables = ['reflectivo', 'estampado', 'bordado', 'dtf', 'sublimado'];
    $processTableInfo = [];
    
    foreach ($processTables as $table) {
        $exists = DB::selectOne("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
        if ($exists) {
            $count = DB::selectOne("SELECT COUNT(*) as cnt FROM {$table}");
            $processTableInfo[$table] = $count->cnt ?? 0;
            echo "   ✓ {$table}: {$processTableInfo[$table]} registros\n";
        } else {
            echo "   ✗ {$table}: NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 5. Generar recomendación
    echo "5️⃣  RECOMENDACIÓN DE ESTRUCTURA\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    echo "Para guardar los procesos (Reflectivo, Bordado, Estampado, DTF, Sublimado)\n";
    echo "con ubicaciones, observaciones y tallas, se propone:\n\n";
    
    echo "OPCIÓN RECOMENDADA: Tabla Única para Todos los Procesos\n\n";
    
    echo "```sql\n";
    echo "CREATE TABLE pedido_procesos (\n";
    echo "    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,\n";
    echo "    pedido_id BIGINT UNSIGNED NOT NULL,\n";
    echo "    pedido_item_id BIGINT UNSIGNED NOT NULL,\n";
    echo "    tipo_proceso ENUM('reflectivo','bordado','estampado','dtf','sublimado'),\n";
    echo "    ubicaciones JSON NOT NULL COMMENT 'Array: [\"Frente\", \"Espalda\"]',\n";
    echo "    observaciones TEXT,\n";
    echo "    tallas_dama JSON COMMENT 'Array: [\"S\", \"M\", \"L\"]',\n";
    echo "    tallas_caballero JSON COMMENT 'Array: [\"M\", \"L\", \"XL\"]',\n";
    echo "    imagen LONGBLOB,\n";
    echo "    estado VARCHAR(50) DEFAULT 'PENDIENTE',\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
    echo "    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,\n";
    echo "    FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE,\n";
    echo "    UNIQUE KEY unique_proceso (pedido_id, pedido_item_id, tipo_proceso)\n";
    echo ");\n";
    echo "```\n\n";
    
    echo "VENTAJAS:\n";
    echo "  ✓ Una sola tabla para todos los procesos\n";
    echo "  ✓ JSON para tallas (flexible y estándar)\n";
    echo "  ✓ Fácil de consultar y escalar\n";
    echo "  ✓ Mejor rendimiento\n";
    echo "  ✓ Menos complejidad en código\n";
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ Análisis completado\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}
