<?php
// Comparar el formato del helper con el que está guardado en el pedido 45452

try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "COMPARACIÓN: Formato Helper vs Pedido 45452\n";
    echo "==========================================\n\n";
    
    // Obtener descripción del pedido 45452
    $result = $mysqli->query("
        SELECT descripcion
        FROM prendas_pedido 
        WHERE numero_pedido = 45452
        LIMIT 1
    ");
    
    if ($result->num_rows > 0) {
        $prenda = $result->fetch_assoc();
        $descActual = $prenda['descripcion'];
        
        echo "DESCRIPCIÓN GUARDADA EN 45452:\n";
        echo "==============================\n";
        echo $descActual;
        echo "\n\n";
        
        // Separar por líneas para análisis
        $lineas = explode("\n", $descActual);
        echo "ANÁLISIS LÍNEA POR LÍNEA:\n";
        echo "=========================\n";
        foreach ($lineas as $i => $linea) {
            echo ($i+1) . ". " . trim($linea) . "\n";
        }
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
