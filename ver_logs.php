<?php
/**
 * Script para ver los últimos logs del backend
 * Uso: php ver_logs.php [número de líneas]
 */

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Archivo de logs no encontrado: $logFile\n";
    exit(1);
}

// Número de líneas a mostrar (por defecto 100)
$lineas = isset($argv[1]) ? (int)$argv[1] : 100;

// Leer el archivo
$contenido = file_get_contents($logFile);
$lineasArray = explode("\n", $contenido);

// Obtener las últimas N líneas
$ultimasLineas = array_slice($lineasArray, -$lineas);

echo "\n📋 Últimas $lineas líneas del archivo de logs:\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($ultimasLineas as $linea) {
    if (!empty(trim($linea))) {
        echo $linea . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Total de líneas mostradas: " . count(array_filter($ultimasLineas)) . "\n";
?>
