<?php
// Verificar estructura de tabla prendas_pedido
try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "ESTRUCTURA DE TABLA: prendas_pedido\n";
    echo "===================================\n\n";
    
    $result = $mysqli->query("DESCRIBE prendas_pedido");
    
    while ($col = $result->fetch_assoc()) {
        echo "Campo: " . $col['Field'] . "\n";
        echo "  Tipo: " . $col['Type'] . "\n";
        echo "  Nulo: " . ($col['Null'] === 'YES' ? 'Sí' : 'No') . "\n";
        echo "  Clave: " . ($col['Key'] ? $col['Key'] : 'No') . "\n";
        echo "  Default: " . ($col['Default'] !== null ? $col['Default'] : 'NULL') . "\n";
        echo "\n";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
