<?php
// Test de la ruta /asesores/api/tipos-manga

try {
    $output = file_get_contents('http://127.0.0.1:8000/asesores/api/tipos-manga');
    echo "=== RESPUESTA EXITOSA ===\n";
    echo "Contenido:\n";
    echo $output . "\n";
    echo "\n=== FIN RESPUESTA ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
