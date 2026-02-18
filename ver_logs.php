<?php

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    echo "=== ÚLTIMAS 50 LÍNEAS DE LOG ===\n\n";
    
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    
    foreach ($lastLines as $line) {
        echo $line;
    }
    
} else {
    echo "Archivo de logs no encontrado: $logFile\n";
}

?>
