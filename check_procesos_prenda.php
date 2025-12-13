<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN TABLA procesos_prenda ===\n\n";

if (Schema::hasTable('procesos_prenda')) {
    echo "✅ Tabla 'procesos_prenda' existe\n\n";
    
    echo "📋 COLUMNAS:\n";
    $cols = DB::select("SHOW COLUMNS FROM procesos_prenda");
    foreach ($cols as $c) {
        echo "  • {$c->Field} ({$c->Type})";
        if ($c->Null === 'NO') echo " [NOT NULL]";
        echo "\n";
    }
    
    echo "\n🔍 ¿Tiene 'novedades'? ";
    if (Schema::hasColumn('procesos_prenda', 'novedades')) {
        echo "✅ SÍ\n";
    } else {
        echo "❌ NO - NECESITA MIGRACIÓN\n";
    }
    
    echo "\n📊 REGISTROS: " . DB::table('procesos_prenda')->count() . "\n";
    
    echo "\n📝 MUESTRA:\n";
    $sample = DB::table('procesos_prenda')->first();
    if ($sample) {
        foreach ((array)$sample as $k => $v) {
            echo "  • $k: " . substr((string)$v, 0, 40) . "\n";
        }
    }
} else {
    echo "❌ Tabla 'procesos_prenda' NO existe\n";
}
