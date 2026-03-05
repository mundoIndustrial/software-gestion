<?php

$file = file_get_contents('resources/views/layouts/sidebar.blade.php');

// Contar @if y @endif
$ifCount = substr_count($file, '@if');
$endifCount = substr_count($file, '@endif');

echo "Análisis de @if/@endif en sidebar.blade.php:\n";
echo "@if count: $ifCount\n";
echo "@endif count: $endifCount\n";
echo "Diferencia: " . ($ifCount - $endifCount) . "\n\n";

// Encontrar líneas con @if/@endif
$lines = explode("\n", $file);
foreach ($lines as $lineNum => $line) {
    $lineNum++;
    if (strpos($line, '@if') !== false) {
        echo "Línea $lineNum: " . trim($line) . "\n";
    }
    if (strpos($line, '@endif') !== false) {
        echo "Línea $lineNum: " . trim($line) . "\n";
    }
}
