<?php
/**
 * Script para procesar backup SQL
 * Convierte DROP TABLE en DELETE FROM
 * Mantiene las tablas intactas
 */

$inputFile = 'backup_mundo_bd_2025-12-15_17-45-31.sql';
$outputFile = 'backup_mundo_bd_2025-12-15_17-45-31_LIMPIO.sql';

if (!file_exists($inputFile)) {
    die("Archivo no encontrado: $inputFile\n");
}

echo "Procesando archivo: $inputFile\n";
echo "Tamaño: " . round(filesize($inputFile) / 1024 / 1024, 2) . " MB\n";

$input = fopen($inputFile, 'r');
$output = fopen($outputFile, 'w');

if (!$input || !$output) {
    die("Error al abrir archivos\n");
}

$lineCount = 0;
$dropCount = 0;
$createCount = 0;
$insertCount = 0;
$currentTable = '';

// Escribir encabezado
fwrite($output, "-- Backup modificado - Solo DELETE e INSERT\n");
fwrite($output, "-- Fecha: " . date('Y-m-d H:i:s') . "\n");
fwrite($output, "-- Las tablas NO serán eliminadas, solo se limpiarán los registros\n\n");
fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n");
fwrite($output, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($output, "SET time_zone = \"+00:00\";\n\n");

while (($line = fgets($input)) !== false) {
    $lineCount++;
    
    // Mostrar progreso cada 10000 líneas
    if ($lineCount % 10000 == 0) {
        echo "Procesadas $lineCount líneas...\n";
    }
    
    // Detectar tabla actual desde comentarios
    if (preg_match('/-- Estructura de tabla para `([^`]+)`/', $line, $matches)) {
        $currentTable = $matches[1];
        continue; // No escribir este comentario
    }
    
    // Convertir DROP TABLE en DELETE FROM
    if (preg_match('/^DROP TABLE IF EXISTS `([^`]+)`;/', $line, $matches)) {
        $tableName = $matches[1];
        $currentTable = $tableName;
        fwrite($output, "-- Limpiando tabla: $tableName\n");
        fwrite($output, "DELETE FROM `$tableName`;\n\n");
        $dropCount++;
        continue;
    }
    
    // Saltar CREATE TABLE (comentar o ignorar)
    if (preg_match('/^CREATE TABLE/', $line)) {
        fwrite($output, "-- CREATE TABLE omitido (tabla ya existe)\n");
        $createCount++;
        // Saltar hasta el cierre de la tabla
        $inCreate = true;
        while ($inCreate && ($line = fgets($input)) !== false) {
            $lineCount++;
            if (preg_match('/^\) ENGINE/', $line)) {
                fwrite($output, "-- Fin de estructura\n\n");
                $inCreate = false;
            }
        }
        continue;
    }
    
    // Saltar líneas vacías después de DROP/CREATE
    if (trim($line) === '' && preg_match('/^(DROP|CREATE)/', trim($line))) {
        continue;
    }
    
    // Mantener comentarios de volcado de datos
    if (preg_match('/^-- Volcado de datos/', $line)) {
        fwrite($output, $line);
        continue;
    }
    
    // Mantener todos los INSERT INTO
    if (preg_match('/^INSERT INTO/', $line)) {
        fwrite($output, $line);
        $insertCount++;
        continue;
    }
    
    // Mantener líneas en blanco y otros comentarios útiles
    if (trim($line) === '' || preg_match('/^--/', $line)) {
        if (trim($line) !== '') {
            fwrite($output, $line);
        }
        continue;
    }
}

fclose($input);
fclose($output);

echo "\n=== RESUMEN ===\n";
echo "Líneas procesadas: $lineCount\n";
echo "DROP TABLE convertidos: $dropCount\n";
echo "CREATE TABLE omitidos: $createCount\n";
echo "INSERT INTO mantenidos: $insertCount\n";
echo "\nArchivo generado: $outputFile\n";
echo "Tamaño: " . round(filesize($outputFile) / 1024 / 1024, 2) . " MB\n";
echo "\n✓ Proceso completado\n";
?>
