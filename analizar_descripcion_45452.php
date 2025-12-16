<?php
// Analizar cómo está armada la descripción en el pedido 45452
try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "ANÁLISIS COMPARATIVO DE DESCRIPCIONES\n";
    echo "=====================================\n\n";
    
    // Pedido 45452 (bueno)
    echo "PEDIDO 45452 (DESCRIPCIÓN BIEN ARMADA):\n";
    echo "=====================================\n\n";
    
    $result = $mysqli->query("
        SELECT id, nombre_prenda, descripcion, descripcion_variaciones
        FROM prendas_pedido 
        WHERE numero_pedido = 45452
        ORDER BY id
        LIMIT 1
    ");
    
    if ($result->num_rows > 0) {
        $prenda = $result->fetch_assoc();
        echo "Prenda: " . $prenda['nombre_prenda'] . "\n";
        echo "Descripción:\n" . wordwrap($prenda['descripcion'], 80, "\n  ") . "\n\n";
        echo "Variaciones:\n" . (strlen($prenda['descripcion_variaciones'] ?? '') > 0 ? wordwrap($prenda['descripcion_variaciones'], 80, "\n  ") : 'NULL') . "\n\n";
    }
    
    // Comparar con últimas prendas (nuevas)
    echo "\n\nÚLTIMAS PRENDAS (NUEVAS):\n";
    echo "========================\n\n";
    
    $result = $mysqli->query("
        SELECT id, numero_pedido, nombre_prenda, descripcion, descripcion_variaciones
        FROM prendas_pedido 
        WHERE numero_pedido != 45452
        ORDER BY id DESC
        LIMIT 5
    ");
    
    while ($prenda = $result->fetch_assoc()) {
        echo "Pedido: " . $prenda['numero_pedido'] . " | Prenda: " . $prenda['nombre_prenda'] . "\n";
        echo "  Descripción: " . (strlen($prenda['descripcion'] ?? '') > 0 ? substr($prenda['descripcion'], 0, 80) . '...' : 'NULL ⚠️') . "\n";
        echo "  Variaciones: " . (strlen($prenda['descripcion_variaciones'] ?? '') > 0 ? substr($prenda['descripcion_variaciones'], 0, 80) . '...' : 'NULL') . "\n\n";
    }
    
    // Analizar cotización del pedido 45452
    echo "\n\nANALIZANDO CÓMO SE CREÓ EL PEDIDO 45452:\n";
    echo "========================================\n\n";
    
    $result = $mysqli->query("
        SELECT p.numero_pedido, p.cliente, p.forma_de_pago, p.descripcion as descripcion_pedido
        FROM pedidos_produccion p
        WHERE p.numero_pedido = 45452
    ");
    
    if ($result->num_rows > 0) {
        $pedido = $result->fetch_assoc();
        echo "Cliente: " . ($pedido['cliente'] ?? 'NULL') . "\n";
        echo "Forma de Pago: " . ($pedido['forma_de_pago'] ?? 'NULL') . "\n";
        echo "Descripción Pedido: " . (strlen($pedido['descripcion_pedido'] ?? '') > 0 ? substr($pedido['descripcion_pedido'], 0, 80) : 'NULL') . "\n\n";
    }
    
    // Revisar estructura de descripción en el 45452
    echo "ESTRUCTURA EXACTA DE DESCRIPCIÓN EN 45452:\n";
    echo "==========================================\n\n";
    
    $result = $mysqli->query("
        SELECT descripcion
        FROM prendas_pedido 
        WHERE numero_pedido = 45452
        LIMIT 1
    ");
    
    if ($result->num_rows > 0) {
        $prenda = $result->fetch_assoc();
        $desc = $prenda['descripcion'];
        
        // Contar separadores
        $separadores = substr_count($desc, '|');
        $lineas = explode('|', $desc);
        
        echo "Total de líneas: " . count($lineas) . "\n";
        echo "Separador usado: | (pipe)\n\n";
        
        echo "Desglose línea por línea:\n";
        foreach ($lineas as $i => $linea) {
            echo ($i+1) . ". " . trim($linea) . "\n";
        }
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
