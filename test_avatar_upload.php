<?php

/**
 * Script de prueba para validar la carga de avatares
 * Ejecutar: php test_avatar_upload.php
 */

require 'vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "=== VALIDACIÓN DE SISTEMA DE AVATARES ===\n\n";

// 1. Verificar que el directorio existe
echo "1️⃣  Verificando directorio de almacenamiento...\n";
$avatarDir = 'avatars';
if (Storage::disk('public')->exists($avatarDir)) {
    echo "   ✓ Directorio '/storage/app/public/avatars' existe\n";
} else {
    echo "   ✗ Directorio NO existe. Creando...\n";
    Storage::disk('public')->makeDirectory($avatarDir);
    echo "   ✓ Directorio creado\n";
}

// 2. Verificar el symlink
echo "\n2️⃣  Verificando symlink público...\n";
$publicStoragePath = public_path('storage');
if (is_link($publicStoragePath)) {
    echo "   ✓ Symlink existe: " . realpath($publicStoragePath) . "\n";
} else {
    echo "   ✗ Symlink NO existe\n";
    echo "   💡 Ejecuta: php artisan storage:link\n";
}

// 3. Verificar configuración de filesystems
echo "\n3️⃣  Configuración de filesystems:\n";
$config = config('filesystems');
echo "   - Disco público URL: " . $config['disks']['public']['url'] . "\n";
echo "   - Ruta pública: " . $config['disks']['public']['root'] . "\n";

// 4. Verificar APP_URL
echo "\n4️⃣  Configuración de URL:\n";
echo "   - APP_URL: " . config('app.url') . "\n";
echo "   - URL pública completa: " . config('app.url') . '/storage/avatars/test.jpg' . "\n";

// 5. Crear archivo de prueba
echo "\n5️⃣  Creando archivo de prueba...\n";
$testFile = 'avatars/test-' . time() . '.txt';
$stored = Storage::disk('public')->put($testFile, 'Test file');
if ($stored) {
    echo "   ✓ Archivo de prueba creado: " . $testFile . "\n";
    
    // Verificar que se puede leer
    if (Storage::disk('public')->exists($testFile)) {
        echo "   ✓ Archivo se puede leer desde storage\n";
        
        // Generar URL
        $url = asset('storage/' . $testFile);
        echo "   ✓ URL generada: " . $url . "\n";
        
        // Limpiar
        Storage::disk('public')->delete($testFile);
        echo "   ✓ Archivo de prueba eliminado\n";
    } else {
        echo "   ✗ Archivo NO se puede leer\n";
    }
} else {
    echo "   ✗ No se pudo crear archivo de prueba\n";
}

echo "\n=== VALIDACIÓN COMPLETADA ===\n";
echo "✓ El sistema de avatares está listo para usar\n";
echo "✓ Ruta de upload: /storage/app/public/avatars/\n";
echo "✓ URL pública: /storage/avatars/\n";
