<?php
/**
 * SCRIPT DE RECREACIÓN DE TABLA
 * logo_cotizacion_tecnica_prendas
 */

// Parsear .env
$envFile = file_get_contents('.env');
$env = [];
foreach (preg_split("/\r\n|\n|\r/", $envFile) as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $value = trim($value, '"\'');
    $env[$key] = $value;
}

$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? 3306;
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$database = $env['DB_DATABASE'] ?? '';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RECREAR TABLA: logo_cotizacion_tecnica_prendas                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $database, $port);
    
    if ($mysqli->connect_error) {
        die("❌ Error de conexión: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Conectado a: {$database}\n\n";

    // SQL para crear la tabla
    $sql = "CREATE TABLE IF NOT EXISTS `logo_cotizacion_tecnica_prendas` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        
        `logo_cotizacion_tecnica_id` bigint unsigned NOT NULL,
        
        `nombre_prenda` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `descripcion` longtext COLLATE utf8mb4_unicode_ci,
        `ubicaciones` json NOT NULL,
        `tallas` json DEFAULT NULL,
        `cantidad` int NOT NULL DEFAULT '1',
        
        `especificaciones` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `color_hilo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `puntos_estimados` int DEFAULT NULL,
        
        `orden` int NOT NULL DEFAULT '0',
        `activo` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        
        KEY `lctp_lct_id_idx` (`logo_cotizacion_tecnica_id`),
        KEY `lctp_activo_idx` (`activo`),
        
        CONSTRAINT `lctp_lct_fk` FOREIGN KEY (`logo_cotizacion_tecnica_id`) 
            REFERENCES `logo_cotizacion_tecnicas` (`id`) 
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    echo "Ejecutando SQL...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($mysqli->query($sql)) {
        echo "✅ Tabla recreada exitosamente\n\n";
        
        // Verificar la tabla
        $result = $mysqli->query("DESCRIBE `logo_cotizacion_tecnica_prendas`");
        
        if ($result) {
            echo "Estructura de la tabla:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            while ($row = $result->fetch_assoc()) {
                echo sprintf("  %-30s %-15s %s\n", 
                    $row['Field'],
                    $row['Type'],
                    ($row['Null'] === 'YES' ? '(nullable)' : '')
                );
            }
            
            echo "\n";
        }
        
        // Verificar registros
        $countResult = $mysqli->query("SELECT COUNT(*) as cnt FROM `logo_cotizacion_tecnica_prendas`");
        $count = $countResult->fetch_assoc()['cnt'];
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ Tabla lista para usar (contiene {$count} registros)\n\n";
        
    } else {
        echo "❌ Error al crear tabla: " . $mysqli->error . "\n";
    }
    
    $mysqli->close();
    
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  COMPLETADO                                                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
