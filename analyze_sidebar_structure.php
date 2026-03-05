<?php

$file = file_get_contents('resources/views/layouts/sidebar.blade.php');
$lines = explode("\n", $file);

$stack = [];
$lineNumbers = [];
$errors = [];

foreach ($lines as $lineNum => $line) {
    $lineNum++;
    $trimmed = trim($line);
    
    if (strpos($trimmed, '@if') === 0) {
        array_push($stack, ['@if', $lineNum, $trimmed]);
        echo "PUSH @if en línea $lineNum: $trimmed\n";
    }
    elseif (strpos($trimmed, '@elseif') === 0) {
        if (empty($stack)) {
            $errors[] = "@elseif sin @if en línea $lineNum";
        } else {
            $last = array_pop($stack);
            if ($last[0] !== '@if') {
                $errors[] = "@elseif después de @{$last[0]} en línea $lineNum";
            }
            echo "SWAP @elseif en línea $lineNum: $trimmed (reemplaza @{$last[0]} de línea {$last[1]})\n";
        }
        array_push($stack, ['@elseif', $lineNum, $trimmed]);
    }
    elseif (strpos($trimmed, '@else') === 0) {
        if (empty($stack)) {
            $errors[] = "@else sin @if en línea $lineNum";
        } else {
            $last = array_pop($stack);
            if ($last[0] !== '@if' && $last[0] !== '@elseif') {
                $errors[] = "@else después de @{$last[0]} en línea $lineNum";
            }
            echo "SWAP @else en línea $lineNum: $trimmed (reemplaza @{$last[0]} de línea {$last[1]})\n";
        }
        array_push($stack, ['@else', $lineNum, $trimmed]);
    }
    elseif (strpos($trimmed, '@endif') === 0) {
        if (empty($stack)) {
            $errors[] = "@endif sin @if en línea $lineNum";
        } else {
            $last = array_pop($stack);
            echo "POP @{$last[0]} en línea $lineNum: $trimmed (cierra @{$last[0]} de línea {$last[1]})\n";
        }
    }
}

echo "\n=== ANÁLISIS FINAL ===\n";
if (!empty($stack)) {
    echo "BLOQUES SIN CERRAR:\n";
    foreach ($stack as $item) {
        echo "  @{$item[0]} en línea {$item[1]}: {$item[2]}\n";
    }
}

if (!empty($errors)) {
    echo "\nERRORES:\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
}

if (empty($stack) && empty($errors)) {
    echo "✅ Sintaxis correcta\n";
}
