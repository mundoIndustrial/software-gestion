<?php

require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurar conexión a la BD
$host = env('DB_HOST', 'localhost');
$database = env('DB_DATABASE', 'mundo_bd5');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '12345');

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database}",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ANÁLISIS COMPLETO DEL PEDIDO 11399 ===\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // 1. Buscar en pedidos
    echo "1. BÚSQUEDA EN TABLA 'pedidos':\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                if (strlen($value) > 100) {
                    echo "{$key}: [JSON DATA - " . strlen($value) . " bytes]\n";
                } else {
                    echo "{$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
        }
    } else {
        echo "No se encontraron registros.\n";
    }
    
    echo "\n\n";
    
    // 2. Logo Pedidos
    echo "2. BÚSQUEDA EN TABLA 'logo_pedidos' CON pedido_id = 11399:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM logo_pedidos WHERE pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                if (strlen($value) > 200) {
                    echo "{$key}: [JSON DATA - " . strlen($value) . " bytes]\n";
                } else {
                    echo "{$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "No se encontraron registros.\n";
    }
    
    echo "\n\n";
    
    // 3. Contar registros en pedidos_produccion
    echo "3. TABLA 'pedidos_produccion':\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos_produccion");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de registros en pedidos_produccion: " . $count['total'] . "\n";
    
    // Mostrar últimos 5 registros
    echo "\nÚltimos 5 registros:\n";
    $stmt = $pdo->prepare("SELECT * FROM pedidos_produccion ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        echo "ID: {$row['id']} | Pedido ID: {$row['pedido_id']} | Estado: {$row['estado']}\n";
    }
    
    echo "\n\n";
    
    // 4. Información sobre la estructura de ambas tablas
    echo "4. INFORMACIÓN DE COLUMNAS:\n";
    echo str_repeat("-", 80) . "\n";
    
    echo "\nTabla 'logo_pedidos' (con pedido_id = 11399):\n";
    $stmt = $pdo->prepare("DESCRIBE logo_pedidos");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return $value !== null ? $value : $default;
}
