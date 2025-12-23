<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
    
    $stmt = $pdo->prepare("DESCRIBE logo_pedidos");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== VERIFICACIÓN: Columnas en logo_pedidos ===\n\n";
    
    $cantidadEncontrada = false;
    foreach ($columns as $col) {
        $field = $col['Field'];
        $type = $col['Type'];
        
        if ($field === 'cantidad') {
            echo "✅ NUEVA COLUMNA AGREGADA: {$field} ({$type})\n";
            $cantidadEncontrada = true;
        }
    }
    
    if (!$cantidadEncontrada) {
        echo "❌ Columna 'cantidad' NO ENCONTRADA\n";
    } else {
        echo "\n✅ Migración ejecutada exitosamente\n";
    }
    
    // Mostrar todas las columnas para contexto
    echo "\n📋 Todas las columnas:\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
