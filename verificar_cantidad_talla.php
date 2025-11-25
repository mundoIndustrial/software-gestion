<?php

// Script simple para verificar cantidad_talla en prendas_pedido

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "VERIFICACIÓN: Campo cantidad_talla en prendas_pedido\n";
echo str_repeat("=", 100) . "\n\n";

// Leer .env
$env = [];
$lines = file(__DIR__ . '/.env');
foreach ($lines as $line) {
    $line = trim($line);
    if (!empty($line) && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $env[$key] = trim($value, '"\'');
    }
}

// Crear conexión con PDO
$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'];
$username = $env['DB_USERNAME'];
$password = $env['DB_PASSWORD'];

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conectado a BD: $database\n\n";
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// Contar totales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM prendas_pedido WHERE cantidad_talla IS NOT NULL");
$conTalla = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM prendas_pedido WHERE cantidad_talla IS NULL");
$sinTalla = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM prendas_pedido");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "📊 ESTADÍSTICAS:\n";
echo "   Total prendas: $total\n";
echo "   ✅ Con cantidad_talla: $conTalla\n";
echo "   ❌ SIN cantidad_talla: $sinTalla\n\n";

// Obtener algunas prendas con datos
$stmt = $pdo->query("
    SELECT id, pedido_produccion_id, nombre_prenda, cantidad, descripcion, cantidad_talla
    FROM prendas_pedido 
    WHERE cantidad_talla IS NOT NULL 
    LIMIT 5
");
$prendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($prendas) > 0) {
    echo "📋 PRIMERAS 5 PRENDAS CON DATOS:\n";
    echo str_repeat("-", 100) . "\n\n";
    
    foreach ($prendas as $index => $prenda) {
        echo "PRENDA " . ($index + 1) . ":\n";
        echo "  ID: {$prenda['id']}\n";
        echo "  Pedido ID: {$prenda['pedido_produccion_id']}\n";
        echo "  Nombre: {$prenda['nombre_prenda']}\n";
        echo "  Cantidad Total: {$prenda['cantidad']}\n";
        echo "  Descripción: " . substr($prenda['descripcion'] ?? '', 0, 60) . "...\n";
        echo "  Cantidad Talla (JSON):\n";
        
        if ($prenda['cantidad_talla']) {
            $tallas = json_decode($prenda['cantidad_talla'], true);
            if (is_array($tallas)) {
                foreach ($tallas as $talla) {
                    echo "    - {$talla['talla']}: {$talla['cantidad']}\n";
                }
            } else {
                echo "    ⚠️ JSON NO válido\n";
                echo "    Contenido: {$prenda['cantidad_talla']}\n";
            }
        } else {
            echo "    ⚠️ SIN DATOS\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️ NO HAY PRENDAS CON cantidad_talla (todas podrían tener NULL)\n";
}

echo str_repeat("=", 100) . "\n";
echo "✅ VERIFICACIÓN COMPLETADA\n";
echo str_repeat("=", 100) . "\n\n";
