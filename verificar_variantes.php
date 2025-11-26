<?php
// Verificar estructura de tabla variantes_prenda

$database = 'mysql';
$host = '127.0.0.1';
$username = 'root';
$password = '123456';
$db_name = 'mundo_bd';

try {
    $conn = new PDO("$database:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========== ESTRUCTURA TABLA variantes_prenda ==========\n\n";
    
    // Ver estructura de la tabla
    $query = 'DESCRIBE variantes_prenda';
    $result = $conn->query($query);
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "Field: {$col['Field']}\n";
        echo "  Type: {$col['Type']}\n";
        echo "  Null: {$col['Null']}\n";
        echo "  Key: {$col['Key']}\n";
        echo "  Default: {$col['Default']}\n";
        echo "\n";
    }
    
    echo "\n========== TIPOS DE PRENDA DISPONIBLES ==========\n\n";
    
    // Ver tipos de prenda
    $query = 'SELECT id, nombre, palabras_clave FROM tipos_prenda LIMIT 10';
    $result = $conn->query($query);
    $tipos = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total tipos: " . count($tipos) . "\n\n";
    foreach ($tipos as $tipo) {
        echo "ID: {$tipo['id']} - Nombre: {$tipo['nombre']}\n";
        echo "  Palabras clave: {$tipo['palabras_clave']}\n";
    }
    
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
