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

    echo "=== VERIFICAR TRIGGERS EN registro_piso_produccion ===\n\n";
    
    $stmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'registro_piso_produccion'");
    
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triggers)) {
        echo "No hay triggers en la tabla registro_piso_produccion\n";
    } else {
        foreach ($triggers as $trigger) {
            echo "Trigger: {$trigger['Trigger']}\n";
            echo "  Event: {$trigger['Event']}\n";
            echo "  Timing: {$trigger['Timing']}\n";
            echo "  Statement: {$trigger['Statement']}\n\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
