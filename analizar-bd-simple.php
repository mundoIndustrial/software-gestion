<?php
/**
 * analizar-bd-simple.php
 * 
 * Análisis simple de la base de datos sin dependencias de Laravel
 * Ejecutar: php analizar-bd-simple.php
 */

// Leer el archivo .env para obtener credenciales
$envFile = __DIR__ . '/.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') === false) {
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

// Conexión
$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$db = $env['DB_DATABASE'] ?? 'mundoindustrial';

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_error) {
        die("❌ Error de conexión: " . $mysqli->connect_error);
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "ANÁLISIS DE BASE DE DATOS\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";
    
    // 1. Listar todas las tablas
    echo "1️⃣  TABLAS EN LA BASE DE DATOS\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $result = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row['TABLE_NAME'];
        echo "   • " . $row['TABLE_NAME'] . "\n";
    }
    
    echo "\n";
    
    // 2. Analizar tablas clave
    $keyTables = ['pedidos_produccion', 'prendas_pedido', 'prendas_reflectivo', 'procesos_prenda'];
    
    foreach ($keyTables as $tableName) {
        // Verificar si tabla existe
        $check = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' LIMIT 1");
        
        if ($check->num_rows > 0) {
            echo "2️⃣  TABLA: {$tableName}\n";
            echo "─────────────────────────────────────────────────────────\n";
            
            $columns = $mysqli->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' ORDER BY ORDINAL_POSITION");
            
            echo sprintf("%-25s %-40s %-10s %s\n", "Columna", "Tipo", "Nulo", "Key");
            echo "─────────────────────────────────────────────────────────\n";
            
            while ($col = $columns->fetch_assoc()) {
                $nullable = $col['IS_NULLABLE'] === 'YES' ? '✓' : '✗';
                echo sprintf("%-25s %-40s %-10s %s\n",
                    substr($col['COLUMN_NAME'], 0, 24),
                    substr($col['COLUMN_TYPE'], 0, 39),
                    $nullable,
                    $col['COLUMN_KEY'] ?: '-'
                );
            }
            
            echo "\n";
        }
    }
    
    // 3. Contar registros en tablas de procesos
    echo "3️⃣  DATOS EN TABLAS EXISTENTES\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $checkTables = [
        'pedidos_produccion' => 'Pedidos de Producción',
        'prendas_pedido' => 'Prendas por Pedido',
        'prendas_reflectivo' => 'Reflectivos en Prendas',
        'procesos_prenda' => 'Procesos de Prenda'
    ];
    
    foreach ($checkTables as $table => $label) {
        if (in_array($table, $tables)) {
            $count = $mysqli->query("SELECT COUNT(*) as cnt FROM $table");
            $row = $count->fetch_assoc();
            echo "   ✓ {$label} ({$table}): " . $row['cnt'] . " registros\n";
        } else {
            echo "   ✗ {$label} ({$table}): NO EXISTE\n";
        }
    }
    
    echo "\n";
    
    // 4. Analizar relación pedidos - pedido_items
    echo "4️⃣  RELACIONES ENCONTRADAS\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $fks = $mysqli->query("
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
    
    $fkCount = 0;
    while ($fk = $fks->fetch_assoc()) {
        echo "   • {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        $fkCount++;
    }
    
    if ($fkCount === 0) {
        echo "   ⚠️  No se encontraron relaciones foráneas\n";
    }
    
    echo "\n";
    
    // 5. Ver ejemplo de datos
    echo "5️⃣  EJEMPLO DE DATOS EXISTENTES\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    echo "\n📊 Últimos 3 PEDIDOS DE PRODUCCIÓN:\n";
    $pedidos = $mysqli->query("SELECT id, numero_pedido, estado, created_at FROM pedidos_produccion ORDER BY id DESC LIMIT 3");
    if ($pedidos && $pedidos->num_rows > 0) {
        while ($p = $pedidos->fetch_assoc()) {
            echo "   • Pedido #{$p['numero_pedido']} (ID: {$p['id']}) - Estado: {$p['estado']} - Fecha: {$p['created_at']}\n";
        }
    } else {
        echo "   (sin registros)\n";
    }
    
    echo "\n📊 Últimas 3 PRENDAS DE PEDIDO:\n";
    $prendas = $mysqli->query("SELECT id, numero_pedido, color_id, tela_id FROM prendas_pedido ORDER BY id DESC LIMIT 3");
    if ($prendas && $prendas->num_rows > 0) {
        while ($p = $prendas->fetch_assoc()) {
            echo "   • Prenda ID: {$p['id']} (Pedido: {$p['numero_pedido']}, Color ID: {$p['color_id']}, Tela ID: {$p['tela_id']})\n";
        }
    } else {
        echo "   (sin registros)\n";
    }
    
    if (in_array('prendas_reflectivo', $tables)) {
        echo "\n📊 Últimos 3 REFLECTIVOS EN PRENDAS:\n";
        $ref = $mysqli->query("SELECT id, prenda_pedido_id, nombre_producto, cantidad_total FROM prendas_reflectivo ORDER BY id DESC LIMIT 3");
        if ($ref && $ref->num_rows > 0) {
            while ($r = $ref->fetch_assoc()) {
                echo "   • ID: {$r['id']} (Prenda: {$r['prenda_pedido_id']}, Producto: {$r['nombre_producto']}, Cantidad: {$r['cantidad_total']})\n";
            }
        } else {
            echo "   (sin registros)\n";
        }
    }
    
    if (in_array('procesos_prenda', $tables)) {
        echo "\n📊 Últimos 3 PROCESOS DE PRENDA:\n";
        $proc = $mysqli->query("SELECT id, numero_pedido, proceso, estado_proceso FROM procesos_prenda ORDER BY id DESC LIMIT 3");
        if ($proc && $proc->num_rows > 0) {
            while ($p = $proc->fetch_assoc()) {
                echo "   • ID: {$p['id']} (Pedido: {$p['numero_pedido']}, Proceso: {$p['proceso']}, Estado: {$p['estado_proceso']})\n";
            }
        } else {
            echo "   (sin registros)\n";
        }
    }
    
    echo "\n";
    
    // 6. Recomendación
    echo "6️⃣  RECOMENDACIÓN DE ALMACENAMIENTO\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    echo "Para guardar los procesos (Reflectivo, Bordado, Estampado, DTF, Sublimado)\n";
    echo "que estás configurando en el modal con ubicaciones, observaciones, tallas e imágenes:\n\n";
    
    echo "OPCIÓN A: EXTENDER LA TABLA EXISTENTE prendas_reflectivo\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "Convertir prendas_reflectivo en tabla genérica para todos los procesos:\n\n";
    echo "```sql\n";
    echo "ALTER TABLE prendas_reflectivo\n";
    echo "ADD COLUMN tipo_proceso ENUM('reflectivo','bordado','estampado','dtf','sublimado') DEFAULT 'reflectivo',\n";
    echo "ADD COLUMN ubicaciones JSON COMMENT 'Array: [\"Frente\", \"Espalda\"]',\n";
    echo "ADD COLUMN observaciones TEXT,\n";
    echo "ADD COLUMN tallas_dama JSON COMMENT 'Array: [\"S\", \"M\", \"L\"]',\n";
    echo "ADD COLUMN tallas_caballero JSON COMMENT 'Array: [\"M\", \"L\", \"XL\"]',\n";
    echo "ADD COLUMN imagen_ruta VARCHAR(255);\n";
    echo "```\n\n";
    
    echo "OPCIÓN B: CREAR NUEVA TABLA GENÉRICA procesos_prenda_nuevo\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "Mantener prendas_reflectivo como está y crear tabla nueva para todos los procesos:\n\n";
    echo "```sql\n";
    echo "CREATE TABLE procesos_prenda_detalles (\n";
    echo "    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,\n";
    echo "    prenda_pedido_id BIGINT UNSIGNED NOT NULL,\n";
    echo "    tipo_proceso ENUM('reflectivo','bordado','estampado','dtf','sublimado') NOT NULL,\n";
    echo "    ubicaciones JSON NOT NULL COMMENT 'Array: [\"Frente\", \"Espalda\"]',\n";
    echo "    observaciones TEXT,\n";
    echo "    tallas_dama JSON COMMENT 'Array: [\"S\", \"M\", \"L\"]',\n";
    echo "    tallas_caballero JSON COMMENT 'Array: [\"M\", \"L\", \"XL\"]',\n";
    echo "    imagen_ruta VARCHAR(255),\n";
    echo "    estado VARCHAR(50) DEFAULT 'PENDIENTE',\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
    echo "    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id) ON DELETE CASCADE,\n";
    echo "    UNIQUE KEY unique_proceso (prenda_pedido_id, tipo_proceso),\n";
    echo "    INDEX idx_estado (estado),\n";
    echo "    INDEX idx_tipo (tipo_proceso)\n";
    echo ");\n";
    echo "```\n\n";
    
    echo "RECOMENDACIÓN: OPCIÓN B\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "✓ Mantiene prendas_reflectivo intacto para compatibilidad\n";
    echo "✓ Diseño más limpio y escalable para múltiples procesos\n";
    echo "✓ Mejor para reporting y análisis\n";
    echo "✓ Permite agregar más procesos sin alterar tabla existente\n";
    echo "✓ JSON fields permiten flexibilidad en datos de ubicaciones y tallas\n\n";
    
    echo "EJEMPLO DE DATOS A GUARDAR:\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "```json\n";
    echo "{\n";
    echo "    \"id\": 1,\n";
    echo "    \"prenda_pedido_id\": 150,\n";
    echo "    \"tipo_proceso\": \"reflectivo\",\n";
    echo "    \"ubicaciones\": [\"Frente\", \"Espalda\", \"Manga derecha\"],\n";
    echo "    \"observaciones\": \"Reflectivo de 3M color plateado, visibilidad máxima\",\n";
    echo "    \"tallas_dama\": [\"S\", \"M\", \"L\"],\n";
    echo "    \"tallas_caballero\": [\"M\", \"L\", \"XL\"],\n";
    echo "    \"imagen_ruta\": \"/storage/procesos/reflectivo-150.jpg\",\n";
    echo "    \"estado\": \"PENDIENTE\"\n";
    echo "}\n";
    echo "```\n";
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ Análisis completado\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
