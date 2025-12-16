<?php
/**
 * Script para reparar líneas INSERT rotas en el archivo SQL
 */

$inputFile = 'backup_mundo_bd_2025-12-15_17-45-31_LIMPIO.sql';
$outputFile = 'backup_mundo_bd_2025-12-15_17-45-31_REPARADO.sql';

echo "Reparando archivo SQL...\n";

$input = fopen($inputFile, 'r');
$output = fopen($outputFile, 'w');

if (!$input || !$output) {
    die("Error al abrir archivos\n");
}

$lineCount = 0;
$fixesCount = 0;
$buffer = '';
$inInsert = false;

while (($line = fgets($input)) !== false) {
    $lineCount++;
    $line = trim($line);
    
    // Detectar inicio de INSERT
    if (preg_match('/^INSERT INTO/', $line)) {
        $inInsert = true;
        $buffer = $line;
        
        // Si la línea termina con ');' está completa
        if (preg_match('/\);$/', $line)) {
            fwrite($output, $line . "\n");
            $buffer = '';
            $inInsert = false;
        }
        continue;
    }
    
    // Si estamos en un INSERT, acumular líneas
    if ($inInsert) {
        $buffer .= ' ' . $line;
        
        // Si encontramos el cierre, el INSERT está completo
        if (preg_match('/\);$/', $line)) {
            fwrite($output, $buffer . "\n");
            $buffer = '';
            $inInsert = false;
        }
        continue;
    }
    
    // Líneas normales (no INSERT)
    fwrite($output, $line . "\n");
}

// Cerrar cualquier buffer pendiente
if ($buffer) {
    echo "Advertencia: Buffer pendiente al final:\n$buffer\n";
    fwrite($output, $buffer . "\n");
}

fclose($input);
fclose($output);

echo "\n=== REPARACIÓN COMPLETADA ===\n";
echo "Líneas procesadas: $lineCount\n";
echo "Archivo reparado: $outputFile\n";
echo "Tamaño: " . round(filesize($outputFile) / 1024 / 1024, 2) . " MB\n";
echo "\n✓ Proceso completado\n";
?>
