<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== RESET COMPLETO DE BASE DE DATOS ===\n\n";

// Obtener el nombre de la base de datos
$database = config('database.connections.mysql.database');
echo "📊 Base de datos: $database\n\n";

// Desactivar verificación de claves foráneas
DB::statement('SET FOREIGN_KEY_CHECKS=0');

// Obtener todas las tablas
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_' . $database;

echo "🗑️  Eliminando todas las tablas...\n";
foreach ($tables as $table) {
    $tableName = $table->$tableKey;
    DB::statement("DROP TABLE IF EXISTS `$tableName`");
    echo "✅ Eliminada: $tableName\n";
}

// Reactivar verificación de claves foráneas
DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Limpiar tabla de migraciones
echo "\n🗑️  Limpiando tabla de migraciones...\n";
DB::table('migrations')->truncate();
echo "✅ Tabla migrations limpiada\n";

echo "\n✅ Base de datos completamente reseteada\n";
echo "\n⏭️  Ahora ejecuta: php artisan migrate\n";
