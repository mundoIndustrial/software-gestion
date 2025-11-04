<?php

/**
 * Script de prueba para verificar la conexión con Firebase Storage
 * Ejecutar: php test-firebase.php
 */

require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;
use GuzzleHttp\Client;

echo "🔥 Probando conexión con Firebase Storage...\n\n";

try {
    // Deshabilitar verificación SSL
    stream_context_set_default([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    
    // Cargar variables de entorno
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    $credentialsPath = __DIR__ . '/storage/app/firebase/credentials.json';
    
    // Verificar que el archivo de credenciales existe
    if (!file_exists($credentialsPath)) {
        echo "❌ ERROR: No se encuentra el archivo de credenciales en:\n";
        echo "   $credentialsPath\n\n";
        exit(1);
    }
    
    echo "✅ Archivo de credenciales encontrado\n";
    
    // Crear instancia de Firebase
    $factory = (new Factory)->withServiceAccount($credentialsPath);
    $storage = $factory->createStorage();
    
    echo "✅ Conexión con Firebase establecida\n";
    
    // Obtener información del bucket
    $bucket = $storage->getBucket();
    $bucketInfo = $bucket->info();
    
    echo "\n📦 Información del Bucket:\n";
    echo "   Nombre: " . ($bucketInfo['name'] ?? 'N/A') . "\n";
    echo "   Ubicación: " . ($bucketInfo['location'] ?? 'N/A') . "\n";
    echo "   Clase de almacenamiento: " . ($bucketInfo['storageClass'] ?? 'N/A') . "\n";
    echo "   Creado: " . ($bucketInfo['timeCreated'] ?? 'N/A') . "\n";
    
    // Listar archivos en la carpeta 'prendas'
    echo "\n📁 Archivos en carpeta 'prendas/':\n";
    $objects = $bucket->objects(['prefix' => 'prendas/']);
    
    $count = 0;
    foreach ($objects as $object) {
        $count++;
        $size = $object->info()['size'] ?? 0;
        $sizeKB = round($size / 1024, 2);
        echo "   - " . $object->name() . " ({$sizeKB} KB)\n";
        
        if ($count >= 5) {
            echo "   ... (mostrando solo los primeros 5)\n";
            break;
        }
    }
    
    if ($count === 0) {
        echo "   (No hay archivos aún)\n";
    }
    
    echo "\n✅ ¡Firebase Storage está funcionando correctamente!\n";
    echo "\n🎉 Ahora puedes:\n";
    echo "   1. Ir a /balanceo/prenda/create\n";
    echo "   2. Crear una prenda con imagen\n";
    echo "   3. La imagen se subirá automáticamente a Firebase\n";
    echo "   4. Ver las imágenes en: https://console.firebase.google.com/project/mundo-software-images/storage\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n📝 Verifica:\n";
    echo "   1. El archivo credentials.json está en storage/app/firebase/\n";
    echo "   2. Las variables de entorno en .env están configuradas\n";
    echo "   3. Las extensiones PHP necesarias están habilitadas (gd, sodium)\n\n";
    exit(1);
}
