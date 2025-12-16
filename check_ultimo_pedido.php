<?php
// Conectar y obtener último pedido
try {
    $mysqli = new mysqli(
        'localhost', 
        'root', 
        '29522628', 
        'mundo_bd'
    );
    
    echo "ÚLTIMO PEDIDO CREADO:\n";
    echo "====================\n\n";
    
    // Ver ultimo pedido
    $result = $mysqli->query("
        SELECT id, numero_pedido, estado, cliente, cliente_id, forma_de_pago, descripcion, created_at 
        FROM pedidos_produccion 
        ORDER BY id DESC 
        LIMIT 1
    ");
    
    if ($result->num_rows == 0) {
        echo "No hay pedidos en la BD";
        exit;
    }
    
    $pedido = $result->fetch_assoc();
    echo "ID: " . $pedido['id'] . "\n";
    echo "Número: " . ($pedido['numero_pedido'] !== null ? $pedido['numero_pedido'] : 'NULL ⚠️') . "\n";
    echo "Estado: " . $pedido['estado'] . "\n";
    echo "Cliente: " . (strlen($pedido['cliente'] ?? '') > 0 ? $pedido['cliente'] : 'NULL ⚠️') . "\n";
    echo "Cliente ID: " . (isset($pedido['cliente_id']) && $pedido['cliente_id'] ? $pedido['cliente_id'] : 'NULL ⚠️') . "\n";
    echo "Forma de Pago: " . (strlen($pedido['forma_de_pago'] ?? '') > 0 ? $pedido['forma_de_pago'] : 'NULL ⚠️') . "\n";
    echo "Descripción: " . (strlen($pedido['descripcion'] ?? '') > 0 ? substr($pedido['descripcion'], 0, 60) : 'NULL ⚠️') . "\n";
    echo "Creado: " . $pedido['created_at'] . "\n\n";
    
    echo "PRENDAS DEL PEDIDO:\n";
    echo "==================\n";
    
    $result = $mysqli->query("
        SELECT id, nombre_prenda, descripcion, descripcion_variaciones, cantidad, cantidad_talla 
        FROM prendas_pedido 
        WHERE numero_pedido = '" . $mysqli->real_escape_string($pedido['numero_pedido']) . "'
        ORDER BY id
    ");
    
    echo "Total: " . $result->num_rows . " prendas\n\n";
    
    if ($result->num_rows > 0) {
        while ($prenda = $result->fetch_assoc()) {
            echo "- " . $prenda['nombre_prenda'] . "\n";
            echo "  Descripción: " . (strlen($prenda['descripcion'] ?? '') > 0 ? substr($prenda['descripcion'], 0, 50) : 'NULL ⚠️') . "\n";
            echo "  Variaciones: " . (strlen($prenda['descripcion_variaciones'] ?? '') > 0 ? substr($prenda['descripcion_variaciones'], 0, 50) : 'NULL') . "\n";
            echo "  Cantidad: " . $prenda['cantidad'] . "\n";
            echo "\n";
        }
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
