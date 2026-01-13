<?php

/**
 * Script para eliminar el método duplicado crearLogoPedidoDesdeAnullCotizacion
 * del controlador PedidosProduccionController.php
 */

$file = __DIR__ . '/app/Http/Controllers/Asesores/PedidosProduccionController.php';

if (!file_exists($file)) {
    die("ERROR: Archivo no encontrado: $file\n");
}

echo "Leyendo archivo...\n";
$content = file_get_contents($file);
$lines = explode("\n", $content);

echo "Total de líneas: " . count($lines) . "\n";

// Buscar las dos declaraciones del método
$firstMethodLine = null;
$secondMethodLine = null;

foreach ($lines as $index => $line) {
    if (strpos($line, 'private function crearLogoPedidoDesdeAnullCotizacion') !== false) {
        if ($firstMethodLine === null) {
            $firstMethodLine = $index;
            echo "Primera declaración encontrada en línea: " . ($index + 1) . "\n";
        } else {
            $secondMethodLine = $index;
            echo "Segunda declaración encontrada en línea: " . ($index + 1) . "\n";
            break;
        }
    }
}

if ($firstMethodLine === null || $secondMethodLine === null) {
    die("ERROR: No se encontraron ambas declaraciones del método\n");
}

// Encontrar el final del primer método (buscando el cierre antes del segundo método)
$endFirstMethod = null;
for ($i = $secondMethodLine - 1; $i > $firstMethodLine; $i--) {
    $trimmed = trim($lines[$i]);
    if ($trimmed === '}') {
        // Verificar si es el cierre de un método (debe tener una línea vacía o comentario después)
        $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
        if ($nextLine === '' || strpos($nextLine, '/**') === 0 || strpos($nextLine, '//') === 0) {
            $endFirstMethod = $i;
            echo "Final del primer método encontrado en línea: " . ($i + 1) . "\n";
            break;
        }
    }
}

if ($endFirstMethod === null) {
    die("ERROR: No se pudo encontrar el final del primer método\n");
}

// Crear backup
$backupFile = $file . '.backup_' . date('YmdHis');
copy($file, $backupFile);
echo "Backup creado: $backupFile\n";

// Eliminar las líneas del primer método (desde $firstMethodLine hasta $endFirstMethod)
$linesToRemove = $endFirstMethod - $firstMethodLine + 1;
echo "Eliminando $linesToRemove líneas (desde línea " . ($firstMethodLine + 1) . " hasta línea " . ($endFirstMethod + 1) . ")\n";

// Construir nuevo contenido
$newLines = array_merge(
    array_slice($lines, 0, $firstMethodLine),
    array_slice($lines, $endFirstMethod + 1)
);

$newContent = implode("\n", $newLines);

// Guardar archivo
file_put_contents($file, $newContent);

echo "\n✅ COMPLETADO!\n";
echo "Líneas originales: " . count($lines) . "\n";
echo "Líneas nuevas: " . count($newLines) . "\n";
echo "Líneas eliminadas: " . ($linesToRemove) . "\n";
echo "\nEl método duplicado ha sido eliminado exitosamente.\n";
echo "Si algo sale mal, puedes restaurar desde: $backupFile\n";
