<?php
// Script para renombrar carpeta API a Api

$oldPath = __DIR__ . '/app/Http/Controllers/API';
$newPath = __DIR__ . '/app/Http/Controllers/Api';

// Verificar que la carpeta API existe
if (!is_dir($oldPath)) {
    echo "❌ Error: La carpeta API no existe en: $oldPath\n";
    exit(1);
}

// Verificar que Api no existe aún
if (is_dir($newPath)) {
    echo "❌ Error: La carpeta Api ya existe en: $newPath\n";
    exit(1);
}

// Usar una carpeta temporal
$tempPath = __DIR__ . '/app/Http/Controllers/Api_Temp_' . time();

// Paso 1: Renombrar API a temporal
if (!rename($oldPath, $tempPath)) {
    echo "❌ Error: No se pudo renombrar API a temporal\n";
    exit(1);
}
echo "✓ Renombrado API a temporal\n";

// Paso 2: Renombrar temporal a Api
if (!rename($tempPath, $newPath)) {
    echo "❌ Error: No se pudo renombrar temporal a Api\n";
    // Revertir
    rename($tempPath, $oldPath);
    exit(1);
}
echo "✓ Renombrado temporal a Api\n";

// Verificar
if (is_dir($newPath) && !is_dir($oldPath)) {
    echo " Éxito: Carpeta renombrada de API a Api\n";
    exit(0);
} else {
    echo "❌ Error: El cambio no se verificó correctamente\n";
    exit(1);
}
?>
