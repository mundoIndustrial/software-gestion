<?php
// Verificar estructura de datos de prendas en cotización
try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "ESTRUCTURA DE DATOS DE PRENDAS EN COTIZACIONES\n";
    echo "=============================================\n\n";
    
    // Ver tabla de cotizaciones_prendas (si existe)
    $result = $mysqli->query("SHOW TABLES LIKE 'cotizaciones%'");
    
    echo "Tablas relacionadas con cotizaciones:\n";
    while ($row = $result->fetch_row()) {
        echo "  - " . $row[0] . "\n";
    }
    
    echo "\n\nESTRUCTURA DE COTIZACIONES:\n";
    echo "==========================\n";
    
    $result = $mysqli->query("DESCRIBE cotizaciones");
    while ($col = $result->fetch_assoc()) {
        if (stripos($col['Field'], 'desc') !== false || stripos($col['Field'], 'observ') !== false) {
            echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
    
    echo "\n\nREVISANDO PRENDAS ANTIGUAS DEL PEDIDO 45452:\n";
    echo "============================================\n\n";
    
    // Buscar de qué cotización viene el pedido 45452
    $result = $mysqli->query("
        SELECT cotizacion_id FROM pedidos_produccion 
        WHERE numero_pedido = 45452
    ");
    
    if ($result->num_rows > 0) {
        $pedido = $result->fetch_assoc();
        $cot_id = $pedido['cotizacion_id'];
        
        echo "Cotización ID: " . $cot_id . "\n\n";
        
        // Ver si hay tabla con datos de prendas de esa cotización
        $result = $mysqli->query("
            SELECT * FROM cotizaciones 
            WHERE id = " . $cot_id
        );
        
        if ($result->num_rows > 0) {
            $cot = $result->fetch_assoc();
            echo "Descripción de Cotización: " . (strlen($cot['descripcion'] ?? '') > 0 ? substr($cot['descripcion'], 0, 100) : 'NULL') . "\n";
            echo "Notas: " . (strlen($cot['notas'] ?? '') > 0 ? substr($cot['notas'], 0, 100) : 'NULL') . "\n";
        }
    }
    
    // Revisar campos en prendas_pedido que podrían tener la descripción original
    echo "\n\nCOMPARACIÓN DE PRENDAS:\n";
    echo "======================\n\n";
    
    $result = $mysqli->query("
        SELECT 
            p1.nombre_prenda,
            p1.descripcion as desc_45452,
            p1.cantidad_talla as tallas_45452,
            p2.nombre_prenda as p2_nombre,
            p2.descripcion as desc_nuevo,
            p2.cantidad_talla as tallas_nuevo
        FROM prendas_pedido p1
        LEFT JOIN prendas_pedido p2 ON 1=0
        WHERE p1.numero_pedido = 45452
        LIMIT 1
    ");
    
    if ($result->num_rows > 0) {
        $comp = $result->fetch_assoc();
        echo "Prenda 45452: " . $comp['nombre_prenda'] . "\n";
        echo "Tallas formato: " . (strlen($comp['tallas_45452'] ?? '') > 0 ? substr($comp['tallas_45452'], 0, 50) : 'NULL') . "\n";
        echo "¿Son datos que vienen de campos múltiples construidos como texto?\n";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
