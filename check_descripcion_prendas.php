<?php
// Conectar y obtener pedidos con y sin descripción
try {
    $mysqli = new mysqli(
        'localhost', 
        'root', 
        '29522628', 
        'mundo_bd'
    );
    
    echo "PEDIDOS CON DESCRIPCIÓN:\n";
    echo "=======================\n\n";
    
    // Ver pedidos con descripción
    $result = $mysqli->query("
        SELECT COUNT(id) as total 
        FROM prendas_pedido 
        WHERE descripcion IS NOT NULL AND descripcion != ''
    ");
    $row = $result->fetch_assoc();
    echo "Total con descripción: " . $row['total'] . "\n\n";
    
    // Ver ejemplo
    $result = $mysqli->query("
        SELECT id, nombre_prenda, descripcion 
        FROM prendas_pedido 
        WHERE descripcion IS NOT NULL AND descripcion != ''
        LIMIT 3
    ");
    
    echo "Ejemplos:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "  Nombre: " . $row['nombre_prenda'] . "\n";
        echo "  Descripción: " . substr($row['descripcion'], 0, 100) . "...\n\n";
    }
    
    echo "\nPRENDAS RECIENTES SIN DESCRIPCIÓN:\n";
    echo "==================================\n";
    
    $result = $mysqli->query("
        SELECT id, nombre_prenda, descripcion, created_at 
        FROM prendas_pedido 
        WHERE descripcion IS NULL OR descripcion = ''
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " (Nombre: " . $row['nombre_prenda'] . ") - " . $row['created_at'] . "\n";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
