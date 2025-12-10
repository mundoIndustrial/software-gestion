<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔄 Reseteando migración de prendas_cot...\n\n";

// 1. Eliminar la tabla si existe
if (Schema::hasTable('prendas_cot')) {
    Schema::dropIfExists('prendas_cot');
    echo "✅ Tabla prendas_cot eliminada\n";
}

// 2. Eliminar el registro de migración
DB::table('migrations')
    ->where('migration', '2025_12_10_create_prendas_cot_table')
    ->delete();
echo "✅ Registro de migración eliminado\n";

// 3. Eliminar el registro de la migración de campos adicionales
DB::table('migrations')
    ->where('migration', '2025_12_10_add_missing_fields_to_prendas_cot')
    ->delete();
echo "✅ Registro de migración de campos adicionales eliminado\n";

echo "\n✅ Listo para ejecutar las migraciones nuevamente\n";
