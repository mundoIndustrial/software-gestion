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
    
    echo "=== ESTRUCTURA Y DATOS: PEDIDO 11399 ===\n";
    echo str_repeat("=", 100) . "\n\n";
    
    // Estructura de pedidos_produccion
    echo "1. ESTRUCTURA TABLA: pedidos_produccion\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("DESCRIBE pedidos_produccion");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']}: {$col['Type']} " . ($col['Null'] == 'NO' ? '[NOT NULL]' : '[NULLABLE]') . "\n";
    }
    
    echo "\n\n2. DATOS: pedidos_produccion ID = 11399\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM pedidos_produccion WHERE id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                echo "  {$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
            }
        }
    } else {
        echo "⚠ NO EXISTE REGISTRO CON ID = 11399\n";
    }
    
    echo "\n\n3. DATOS: logo_pedidos (pedido_id = 11399)\n";
    echo str_repeat("-", 100) . "\n";
    
    // Primero verificar estructura
    echo "Estructura de logo_pedidos:\n";
    $stmt = $pdo->prepare("DESCRIBE logo_pedidos");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']}: {$col['Type']}\n";
    }
    
    echo "\n\nRegistros con pedido_id = 11399:\n";
    $stmt = $pdo->prepare("SELECT id, pedido_id, numero_pedido, estado, area, tecnicas, ubicaciones FROM logo_pedidos WHERE pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            echo "  ID: {$row['id']}\n";
            echo "  Pedido ID: {$row['pedido_id']}\n";
            echo "  Número Pedido: {$row['numero_pedido']}\n";
            echo "  Estado: {$row['estado']}\n";
            echo "  Área: {$row['area']}\n";
            echo "  Técnicas: {$row['tecnicas']}\n";
            if (strlen($row['ubicaciones']) < 200) {
                echo "  Ubicaciones: {$row['ubicaciones']}\n";
            } else {
                echo "  Ubicaciones: [JSON - " . strlen($row['ubicaciones']) . " chars]\n";
            }
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS CON pedido_id = 11399\n";
    }
    
    echo "\n\n4. BÚSQUEDA: Logo Cotizaciones (id = 107)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT id, numero_cotizacion, estado FROM logo_cotizaciones WHERE id = 107");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            echo "  ID: {$row['id']}\n";
            echo "  Número Cotización: {$row['numero_cotizacion']}\n";
            echo "  Estado: {$row['estado']}\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return $value !== null ? $value : $default;
}
