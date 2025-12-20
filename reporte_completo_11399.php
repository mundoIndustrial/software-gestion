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
    
    echo "=== REPORTE COMPLETO: PEDIDO 11399 ===\n";
    echo str_repeat("=", 100) . "\n\n";
    
    // 1. Pedidos Producción
    echo "1. TABLA: pedidos_produccion\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM pedidos_produccion WHERE id = 11399 OR pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                echo "  {$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // 2. Logo Pedidos
    echo "2. TABLA: logo_pedidos (pedido_id = 11399)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM logo_pedidos WHERE pedido_id = 11399 OR id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                if (strlen($value) > 100) {
                    echo "  {$key}: [JSON - " . strlen($value) . " chars]\n";
                } else {
                    echo "  {$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // 3. Prendas Pedido
    echo "3. TABLA: prendas_pedido (pedido_id = 11399)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM prendas_pedido WHERE pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            echo "  ID: {$row['id']}\n";
            echo "  Pedido ID: {$row['pedido_id']}\n";
            echo "  Prenda ID: {$row['prenda_id']}\n";
            if (isset($row['variante'])) echo "  Variante: {$row['variante']}\n";
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // 4. Procesos Pedidos Logo
    echo "4. TABLA: procesos_pedidos_logo (pedido_id = 11399)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM procesos_pedidos_logo WHERE pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            echo "  ID: {$row['id']}\n";
            echo "  Pedido ID: {$row['pedido_id']}\n";
            if (isset($row['proceso'])) echo "  Proceso: {$row['proceso']}\n";
            if (isset($row['estado'])) echo "  Estado: {$row['estado']}\n";
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // 5. Logo Pedido Imágenes
    echo "5. TABLA: logo_pedido_imagenes (logo_pedido_id = 11399 o pedido_id = 11399)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM logo_pedido_imagenes WHERE logo_pedido_id = 11399 OR pedido_id = 11399");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            echo "  ID: {$row['id']}\n";
            if (isset($row['logo_pedido_id'])) echo "  Logo Pedido ID: {$row['logo_pedido_id']}\n";
            if (isset($row['pedido_id'])) echo "  Pedido ID: {$row['pedido_id']}\n";
            if (isset($row['ruta_imagen'])) echo "  Ruta: {$row['ruta_imagen']}\n";
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // 6. Cotizaciones Logo
    echo "6. TABLA: logo_cotizaciones (id = 107, que está referenciada en logo_pedidos)\n";
    echo str_repeat("-", 100) . "\n";
    $stmt = $pdo->prepare("SELECT * FROM logo_cotizaciones WHERE id = 107");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($result)) {
        echo "Registros encontrados: " . count($result) . "\n\n";
        foreach ($result as $row) {
            foreach ($row as $key => $value) {
                if (strlen($value) > 100) {
                    echo "  {$key}: [DATA - " . strlen($value) . " chars]\n";
                } else {
                    echo "  {$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "⚠ NO SE ENCONTRARON REGISTROS\n\n";
    }
    
    // Resumen
    echo "\n" . str_repeat("=", 100) . "\n";
    echo "RESUMEN:\n";
    echo "- Logo Pedido con número LOGO-00011 asociado a pedido_id: 11399\n";
    echo "- Estado: pendiente\n";
    echo "- Área: creacion_de_orden\n";
    echo "- Técnicas: BORDADO\n";
    echo "- Ubicaciones: CAMISA (PECHO, ESPALDA, MANGA) y JEAN_SUDADERA\n";
    echo "- Cotización Logo ID: 107\n";
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return $value !== null ? $value : $default;
}
