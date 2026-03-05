<?php

$file = file_get_contents('resources/views/layouts/sidebar.blade.php');
$lines = explode("\n", $file);

echo "ANÁLISIS COMPLETO DE ESTRUCTURA BLADE\n";
echo "====================================\n\n";

$stack = [];
$currentSection = '';
$indentationLevel = 0;

foreach ($lines as $lineNum => $line) {
    $lineNum++;
    $trimmed = trim($line);
    
    // Detectar @if/@elseif/@else/@endif
    if (strpos($trimmed, '@if') === 0) {
        $indentation = str_repeat('  ', count($stack));
        echo "{$indentation}Línea $lineNum: @if - $trimmed\n";
        array_push($stack, ['@if', $lineNum, $trimmed]);
    }
    elseif (strpos($trimmed, '@elseif') === 0) {
        $indentation = str_repeat('  ', max(0, count($stack) - 1));
        echo "{$indentation}Línea $lineNum: @elseif - $trimmed\n";
        if (!empty($stack)) {
            $last = array_pop($stack);
            array_push($stack, ['@elseif', $lineNum, $trimmed]);
        }
    }
    elseif (strpos($trimmed, '@else') === 0) {
        $indentation = str_repeat('  ', max(0, count($stack) - 1));
        echo "{$indentation}Línea $lineNum: @else - $trimmed\n";
        if (!empty($stack)) {
            $last = array_pop($stack);
            array_push($stack, ['@else', $lineNum, $trimmed]);
        }
    }
    elseif (strpos($trimmed, '@endif') === 0) {
        $indentation = str_repeat('  ', max(0, count($stack) - 1));
        echo "{$indentation}Línea $lineNum: @endif - $trimmed\n";
        if (!empty($stack)) {
            $last = array_pop($stack);
            echo "{$indentation}  (cierra @{$last[0]} de línea {$last[1]})\n";
        } else {
            echo "{$indentation}  ❌ ERROR: @endif sin bloque abierto\n";
        }
    }
}

echo "\n=== ANÁLISIS FINAL ===\n";
if (!empty($stack)) {
    echo "❌ BLOQUES SIN CERRAR:\n";
    foreach ($stack as $item) {
        echo "  @{$item[0]} en línea {$item[1]}: {$item[2]}\n";
    }
} else {
    echo "✅ Todos los bloques están cerrados correctamente\n";
}

// Contar totales
$ifCount = substr_count($file, '@if');
$elseifCount = substr_count($file, '@elseif');
$elseCount = substr_count($file, '@else');
$endifCount = substr_count($file, '@endif');

echo "\n=== CONTADORES ===\n";
echo "@if: $ifCount\n";
echo "@elseif: $elseifCount\n";
echo "@else: $elseCount\n";
echo "@endif: $endifCount\n";
echo "Total bloques condicionales: " . ($ifCount) . "\n";
echo "Total cierres: " . ($endifCount) . "\n";

if ($ifCount != $endifCount) {
    echo "❌ DIFERENCIA: " . ($ifCount - $endifCount) . " bloques sin cerrar\n";
} else {
    echo "✅ Número de bloques y cierres coincide\n";
}
