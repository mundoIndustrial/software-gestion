<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR ESTRUCTURA DE TABLA AREAS ===\n\n";

try {
    // Primero, verificar si la tabla existe
    $tables = DB::select("SHOW TABLES LIKE 'areas'");
    
    if (empty($tables)) {
        echo "❌ La tabla 'areas' NO EXISTE\n\n";
        echo "Necesitas crear primero la tabla con:\n";
        echo "CREATE TABLE areas (\n";
        echo "    id INT PRIMARY KEY AUTO_INCREMENT,\n";
        echo "    nombre VARCHAR(255) NOT NULL UNIQUE,\n";
        echo "    descripcion TEXT,\n";
        echo "    estado ENUM('activo', 'inactivo') DEFAULT 'activo',\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
        echo "};\n\n";
    } else {
        echo "✅ Tabla 'areas' existe\n\n";
        
        // Obtener estructura
        $columns = DB::select("DESCRIBE areas");
        echo "Columnas:\n";
        foreach ($columns as $col) {
            echo "  - {$col->Field}: {$col->Type}";
            if ($col->Null === 'NO') echo " [NOT NULL]";
            if ($col->Key === 'PRI') echo " [PRIMARY KEY]";
            if ($col->Key === 'UNI') echo " [UNIQUE]";
            echo "\n";
        }
        
        echo "\n=== DATOS ACTUALES ===\n\n";
        $areas = DB::table('areas')->get();
        if ($areas->count() > 0) {
            foreach ($areas as $area) {
                echo "ID: {$area->id} | Nombre: {$area->nombre}\n";
            }
        } else {
            echo "No hay áreas registradas\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
