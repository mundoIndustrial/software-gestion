<?php
// Conectar y obtener datos de numero_secuencias
try {
    $mysqli = new mysqli(
        'localhost', 
        'root', 
        '29522628', 
        'mundo_bd'
    );
    
    // Verificar si existe la tabla
    $result = $mysqli->query("SHOW TABLES LIKE 'numero_secuencias'");
    if ($result->num_rows == 0) {
        echo "ERROR: Tabla 'numero_secuencias' NO EXISTE\n";
        exit(1);
    }
    
    echo "✓ Tabla numero_secuencias EXISTE\n\n";
    
    // Ver contenido
    $result = $mysqli->query("SELECT * FROM numero_secuencias");
    echo "Contenido de numero_secuencias:\n";
    echo "================================\n";
    
    if ($result->num_rows == 0) {
        echo "⚠ TABLA ESTÁ VACÍA!\n";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "Tipo: " . $row['tipo'] . "\n";
            echo "Siguiente: " . $row['siguiente'] . "\n";
            echo "Creado: " . $row['created_at'] . "\n\n";
        }
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
