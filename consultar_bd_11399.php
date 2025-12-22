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
    
    echo "=== CONSULTA 1: pedidos_produccion WHERE id = 11399 ===\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt1 = $pdo->prepare("SELECT * FROM pedidos_produccion WHERE id = 11399");
    $stmt1->execute();
    $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result1)) {
        echo "Registros encontrados: " . count($result1) . "\n\n";
        foreach ($result1 as $row) {
            foreach ($row as $key => $value) {
                echo "{$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No se encontraron registros.\n\n";
    }
    
    echo "=== CONSULTA 2: logo_pedidos WHERE numero_pedido = 'LOGO-00011' ===\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt2 = $pdo->prepare("SELECT * FROM logo_pedidos WHERE numero_pedido = 'LOGO-00011'");
    $stmt2->execute();
    $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result2)) {
        echo "Registros encontrados: " . count($result2) . "\n\n";
        foreach ($result2 as $row) {
            foreach ($row as $key => $value) {
                echo "{$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No se encontraron registros.\n\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return $value !== null ? $value : $default;
}
