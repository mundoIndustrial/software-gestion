<?php

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
    
    echo "Limpiando tabla prendas_pedido...\n";
    $pdo->exec("DELETE FROM prendas_pedido");
    echo "✅ Tabla prendas_pedido limpiada\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
