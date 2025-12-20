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
    
    echo "=== TABLAS DISPONIBLES EN LA BASE DE DATOS ===\n";
    echo str_repeat("=", 80) . "\n\n";
    
    $stmt = $pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Total de tablas: " . count($tables) . "\n\n";
    
    // Mostrar tablas relevantes
    $relevant_tables = ['pedidos_produccion', 'logo_pedidos', 'pedidos', 'pedido_prendas'];
    
    foreach ($relevant_tables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Tabla '{$table}' EXISTE\n";
        } else {
            echo "✗ Tabla '{$table}' NO EXISTE\n";
        }
    }
    
    echo "\n\nTodas las tablas disponibles:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
    echo "\n\n=== DATOS DEL LOGO_PEDIDOS ID 11399 ===\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Buscar logo_pedidos con pedido_id = 11399
    $stmt = $pdo->prepare("SELECT * FROM logo_pedidos WHERE pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Se encontraron " . count($result) . " registro(s):\n\n";
        foreach ($result as $row) {
            echo "--- REGISTRO ---\n";
            foreach ($row as $key => $value) {
                if (strlen($value) > 150) {
                    echo "{$key}:\n";
                    // Pretty print JSON si es posible
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
                    } else {
                        echo substr($value, 0, 150) . "...\n\n";
                    }
                } else {
                    echo "{$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
        }
    } else {
        echo "No se encontraron registros con pedido_id = 11399\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return $value !== null ? $value : $default;
}
