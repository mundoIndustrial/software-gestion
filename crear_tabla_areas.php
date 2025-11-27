<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREAR TABLA AREAS E INSERTAR DATOS ===\n\n";

try {
    // Crear tabla areas
    echo "1️⃣  Creando tabla 'areas'...\n";
    DB::statement("
        CREATE TABLE IF NOT EXISTS areas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(255) NOT NULL UNIQUE,
            descripcion TEXT,
            estado ENUM('activo', 'inactivo') DEFAULT 'activo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'areas' creada correctamente\n\n";
    
    // Insertar áreas
    echo "2️⃣  Insertando áreas...\n";
    
    $areas = [
        ['nombre' => 'Corte', 'descripcion' => 'Área de corte de telas y prendas', 'estado' => 'activo'],
        ['nombre' => 'Costura', 'descripcion' => 'Área de costura de prendas', 'estado' => 'activo'],
        ['nombre' => 'Bordado', 'descripcion' => 'Área de bordado de diseños', 'estado' => 'activo'],
        ['nombre' => 'Estampado', 'descripcion' => 'Área de estampado de gráficos', 'estado' => 'activo'],
        ['nombre' => 'Reflectivo', 'descripcion' => 'Aplicación de telas reflectivas', 'estado' => 'activo'],
        ['nombre' => 'Lavandería', 'descripcion' => 'Lavado y acabado de prendas', 'estado' => 'activo'],
        ['nombre' => 'Arreglos', 'descripcion' => 'Reparaciones y ajustes', 'estado' => 'activo'],
        ['nombre' => 'Marras', 'descripcion' => 'Control y corrección de defectos', 'estado' => 'activo'],
        ['nombre' => 'Control de Calidad', 'descripcion' => 'Inspección de calidad final', 'estado' => 'activo'],
        ['nombre' => 'Bodega', 'descripcion' => 'Almacenamiento e inventario', 'estado' => 'activo'],
    ];
    
    foreach ($areas as $area) {
        DB::table('areas')->insertOrIgnore($area);
        echo "  ✓ {$area['nombre']}\n";
    }
    
    echo "\n✅ Todas las áreas insertadas correctamente\n\n";
    
    // Verificar datos insertados
    echo "=== DATOS INSERTADOS ===\n\n";
    $areasList = DB::table('areas')->get();
    foreach ($areasList as $area) {
        echo "ID: {$area->id} | {$area->nombre}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
