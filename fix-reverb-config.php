<?php

echo "🔧 Verificando configuración de Reverb\n";
echo "========================================\n\n";

// Leer el archivo .env
$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    echo "❌ Error: No se encontró el archivo .env\n";
    exit(1);
}

$envContent = file_get_contents($envPath);
$lines = explode("\n", $envContent);

echo "📋 Configuración actual de Reverb:\n";
echo "-----------------------------------\n";

$reverbConfig = [];
$viteReverbConfig = [];

foreach ($lines as $line) {
    $line = trim($line);
    
    // Buscar configuración de REVERB
    if (strpos($line, 'REVERB_') === 0 && strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $reverbConfig[$key] = $value;
        echo "   $key = $value\n";
    }
    
    // Buscar configuración de VITE_REVERB
    if (strpos($line, 'VITE_REVERB_') === 0 && strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $viteReverbConfig[$key] = $value;
        echo "   $key = $value\n";
    }
}

echo "\n";

// Verificar si hay inconsistencias
$hasIssues = false;

if (!isset($reverbConfig['REVERB_APP_KEY']) || !isset($viteReverbConfig['VITE_REVERB_APP_KEY'])) {
    echo "⚠️ Faltan configuraciones de REVERB_APP_KEY\n";
    $hasIssues = true;
}

if (isset($reverbConfig['REVERB_APP_KEY']) && isset($viteReverbConfig['VITE_REVERB_APP_KEY'])) {
    if ($reverbConfig['REVERB_APP_KEY'] !== $viteReverbConfig['VITE_REVERB_APP_KEY']) {
        echo "❌ PROBLEMA DETECTADO: Las claves no coinciden!\n";
        echo "   REVERB_APP_KEY = {$reverbConfig['REVERB_APP_KEY']}\n";
        echo "   VITE_REVERB_APP_KEY = {$viteReverbConfig['VITE_REVERB_APP_KEY']}\n";
        $hasIssues = true;
    }
}

if (!$hasIssues) {
    echo "✅ La configuración parece correcta\n";
} else {
    echo "\n";
    echo "🔧 SOLUCIÓN:\n";
    echo "------------\n";
    echo "Las variables REVERB_APP_KEY y VITE_REVERB_APP_KEY deben tener el mismo valor.\n";
    echo "\n";
    echo "Edita tu archivo .env y asegúrate de que ambas tengan el mismo valor:\n";
    echo "\n";
    echo "REVERB_APP_KEY=mundo-industrial-key\n";
    echo "VITE_REVERB_APP_KEY=mundo-industrial-key\n";
    echo "\n";
    echo "Luego ejecuta:\n";
    echo "1. npm run build (o npm run dev)\n";
    echo "2. php artisan config:clear\n";
    echo "3. Reinicia el servidor Reverb\n";
}

echo "\n";
echo "========================================\n";
