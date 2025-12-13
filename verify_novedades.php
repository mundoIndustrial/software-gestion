<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN: Campo novedades en procesos_prenda ===\n\n";

if (Schema::hasColumn('procesos_prenda', 'novedades')) {
    echo "✅ Campo 'novedades' agregado correctamente\n";
    
    $cols = DB::select("SHOW COLUMNS FROM procesos_prenda WHERE Field = 'novedades'");
    if (count($cols) > 0) {
        $col = $cols[0];
        echo "\n📋 DETALLE DEL CAMPO:\n";
        echo "  • Nombre: {$col->Field}\n";
        echo "  • Tipo: {$col->Type}\n";
        echo "  • NULL: {$col->Null}\n";
        echo "  • Default: {$col->Default}\n";
    }
} else {
    echo "❌ Campo 'novedades' NO existe\n";
}

echo "\n✅ Verificación completada\n";
