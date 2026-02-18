<?php

// Intentar leer configuración desde config/database.php
$configPath = __DIR__ . '/config/database.php';

if (file_exists($configPath)) {
    $config = include $configPath;
    
    echo "=== CONFIGURACIÓN DE BASE DE DATOS ===\n\n";
    
    if (isset($config['connections']['mysql'])) {
        $mysqlConfig = $config['connections']['mysql'];
        echo "Host: " . $mysqlConfig['host'] . "\n";
        echo "Database: " . $mysqlConfig['database'] . "\n";
        echo "Username: " . $mysqlConfig['username'] . "\n";
        echo "Password: " . (empty($mysqlConfig['password']) ? '(vacío)' : '(configurado)') . "\n\n";
        
        // Intentar conectar con estas credenciales
        try {
            $pdo = new PDO(
                "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['database']}", 
                $mysqlConfig['username'], 
                $mysqlConfig['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "✅ Conexión exitosa a la base de datos\n\n";
            
            // Verificar tabla bodega_detalles_talla
            $stmt = $pdo->query("SHOW TABLES LIKE 'bodega_detalles_talla'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Tabla 'bodega_detalles_talla' existe\n\n";
                
                // Contar items por área
                $stmt = $pdo->query("SELECT area, COUNT(*) as total FROM bodega_detalles_talla GROUP BY area");
                echo "Items por área:\n";
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "- {$row['area']}: {$row['total']}\n";
                }
                
                // Verificar items EPP
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM bodega_detalles_talla WHERE area = 'EPP'");
                $eppCount = $stmt->fetchColumn();
                echo "\nItems EPP totales: $eppCount\n";
                
                // Verificar items EPP con estado_pendiente
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM bodega_detalles_talla WHERE area = 'EPP' AND (estado_bodega = 'Pendiente' OR estado_bodega IS NULL OR estado_bodega = '')");
                $eppPendientes = $stmt->fetchColumn();
                echo "Items EPP con estado_pendiente: $eppPendientes\n";
                
            } else {
                echo "❌ Tabla 'bodega_detalles_talla' no existe\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error de conexión: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ No se encontró configuración MySQL\n";
    }
    
} else {
    echo "❌ No se encontró archivo config/database.php\n";
}

?>
