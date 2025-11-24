<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN DE TABLA sessions ===\n\n";

if (Schema::hasTable('sessions')) {
    echo "✅ Tabla 'sessions' existe\n";
} else {
    echo "❌ Tabla 'sessions' NO existe\n";
    echo "\nCreando tabla sessions...\n";
    
    // Crear la tabla manualmente
    Schema::create('sessions', function ($table) {
        $table->string('id')->primary();
        $table->foreignId('user_id')->nullable()->index();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->longText('payload');
        $table->integer('last_activity')->index();
    });
    
    echo "✅ Tabla 'sessions' creada exitosamente\n";
}

echo "\n✅ Verificación completada\n";
