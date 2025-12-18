<?php
// Archivo para verificar/crear secuencia universal
require 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Configurar BD
$db = new DB;
$db->addConnection([
    'driver' => 'mysql',
    'host' => env('DB_HOST', 'localhost'),
    'database' => env('DB_DATABASE', 'mundo_bd'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', '29522628'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);
$db->setAsGlobal();
$db->bootEloquent();

try {
    // Verificar tabla existe
    $tableExists = DB::connection()->getSchemaBuilder()->hasTable('numero_secuencias');
    
    if (!$tableExists) {
        echo "❌ Tabla 'numero_secuencias' NO EXISTE!\n";
        exit(1);
    }
    
    // Verificar secuencia universal
    $seq = DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_universal')
        ->first();
    
    if ($seq) {
        echo "✅ Secuencia universal EXISTE\n";
        echo "   Tipo: {$seq->tipo}\n";
        echo "   Siguiente: {$seq->siguiente}\n";
    } else {
        echo "⚠️  Secuencia universal NO EXISTE\n";
        echo "Insertando...\n";
        
        DB::table('numero_secuencias')->insert([
            'tipo' => 'cotizaciones_universal',
            'siguiente' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Secuencia universal CREADA (inicio en 1)\n";
    }
    
    // Mostrar todas las secuencias
    echo "\n📋 Todas las secuencias:\n";
    $secuencias = DB::table('numero_secuencias')->get();
    foreach ($secuencias as $s) {
        echo "   - {$s->tipo}: {$s->siguiente}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
