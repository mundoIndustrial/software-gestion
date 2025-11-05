<?php
require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== ESTRUCTURA DE LA TABLA registro_piso_produccion ===\n\n";
    
    $stmt = $pdo->query("DESCRIBE registro_piso_produccion");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Campo: {$row['Field']}\n";
        echo "  Tipo: {$row['Type']}\n";
        echo "  Null: {$row['Null']}\n";
        echo "  Default: {$row['Default']}\n";
        echo "  Extra: {$row['Extra']}\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
