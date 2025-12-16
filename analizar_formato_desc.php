<?php
// Verificar de dónde viene la descripción del pedido 45452
try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "ANALIZANDO DESCRIPCIÓN DEL PEDIDO 45452\n";
    echo "======================================\n\n";
    
    // Obtener el formato de la descripción
    $result = $mysqli->query("
        SELECT descripcion
        FROM prendas_pedido 
        WHERE numero_pedido = 45452
        LIMIT 1
    ");
    
    $prenda = $result->fetch_assoc();
    $desc = $prenda['descripcion'];
    
    // Extraer patrón
    echo "FORMATO OBSERVADO:\n";
    echo "==================\n\n";
    
    preg_match('/Prenda \d+: (.+?)\s+Descripción:/', $desc, $matches);
    if ($matches) {
        echo "Comienza con: 'Prenda N: [nombre]' \n";
        echo "Luego: 'Descripción: [descripcion]'\n";
        echo "Después: 'Tela: [tela]'\n";
        echo "Color: [color]\n";
        echo "Manga: [manga]\n";
        echo "Bolsillos: [si/no] - [detalles]\n";
        echo "Reflectivo: [si/no] - [detalles]\n";
        echo "Tallas: [talla1:cant1, talla2:cant2, ...]\n";
    }
    
    // Verificar si en la cotización original hay esa información
    echo "\n\nBÚSCANDO CÓMO SE CONSTRUYE ESA DESCRIPCIÓN:\n";
    echo "===========================================\n\n";
    
    // Ver si hay prendas en cotización con estructura similar
    $result = $mysqli->query("
        SELECT COUNT(*) as total FROM cotizaciones_prenda
        WHERE cotizacion_id IN (
            SELECT cotizacion_id FROM pedidos_produccion 
            WHERE numero_pedido = 45452
        )
    ");
    
    $count = $result->fetch_assoc();
    echo "Prendas en la cotización: " . $count['total'] . "\n\n";
    
    // Buscar si existe un campo descripcion_completa o similar
    $result = $mysqli->query("SHOW COLUMNS FROM cotizaciones_prenda");
    echo "Columnas en cotizaciones_prenda:\n";
    while ($col = $result->fetch_assoc()) {
        if (stripos($col['Field'], 'desc') !== false) {
            echo "  - " . $col['Field'] . "\n";
        }
    }
    
    echo "\n\nCONCLUSIÓN:\n";
    echo "===========\n";
    echo "La descripción en 45452 se ve como si fuera construida por CADA PRENDA\n";
    echo "con información de: nombre, descripción, tela, color, manga, bolsillos, reflectivo, tallas\n";
    echo "Esto sugiere que proviene de CotizacionPrendaService o similar.\n\n";
    
    echo "Los nuevos pedidos usan el método construirDescripcionCompleta() del frontend\n";
    echo "que usa un formato diferente (con '|' como separador).\n";
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
