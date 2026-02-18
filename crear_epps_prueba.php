<?php

// Script para crear EPPs de prueba
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    echo "=== CREANDO EPPs DE PRUEBA ===\n";
    
    // Verificar si ya hay EPPs
    $existentes = DB::table('epps')->count();
    echo "EPPs existentes: {$existentes}\n";
    
    if ($existentes > 0) {
        echo "Ya hay EPPs en la base de datos. Mostrando primeros 5:\n";
        $epps = DB::table('epps')->limit(5)->get();
        foreach ($epps as $epp) {
            echo "- ID: {$epp->id}, Nombre: {$epp->nombre_completo}, Activo: {$epp->activo}\n";
        }
        exit(0);
    }
    
    // Crear categorías si no existen
    $categoriasExistentes = DB::table('epp_categorias')->count();
    echo "Categorías existentes: {$categoriasExistentes}\n";
    
    if ($categoriasExistentes == 0) {
        echo "Creando categorías de EPP...\n";
        $categorias = [
            ['codigo' => 'CASCOS', 'nombre' => 'Cascos de Seguridad', 'descripcion' => 'Protección para la cabeza', 'activo' => 1],
            ['codigo' => 'GUANTES', 'nombre' => 'Guantes de Protección', 'descripcion' => 'Protección para las manos', 'activo' => 1],
            ['codigo' => 'BOTAS', 'nombre' => 'Botas de Seguridad', 'descripcion' => 'Protección para los pies', 'activo' => 1],
            ['codigo' => 'GAFAS', 'nombre' => 'Gafas de Protección', 'descripcion' => 'Protección ocular', 'activo' => 1],
            ['codigo' => 'CHALECOS', 'nombre' => 'Chalecos de Seguridad', 'descripcion' => 'Protección torso', 'activo' => 1],
        ];
        
        foreach ($categorias as $cat) {
            $id = DB::table('epp_categorias')->insertGetId([
                'codigo' => $cat['codigo'],
                'nombre' => $cat['nombre'],
                'descripcion' => $cat['descripcion'],
                'activo' => $cat['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "Categoría creada: {$cat['nombre']} (ID: {$id})\n";
        }
    }
    
    // Obtener categorías para asignar
    $categoriasDB = DB::table('epp_categorias')->where('activo', 1)->get();
    $categoriaIds = $categoriasDB->pluck('id')->toArray();
    
    // Crear EPPs de prueba
    echo "\nCreando EPPs de prueba...\n";
    $epps = [
        [
            'nombre_completo' => 'Casco de Seguridad Industrial',
            'marca' => '3M',
            'tipo' => 'PRODUCTO',
            'talla' => 'Única',
            'color' => 'Amarillo',
            'descripcion' => 'Casco de seguridad con barbuquejo para protección industrial',
            'activo' => 1,
            'categoria_id' => $categoriaIds[0] ?? null,
        ],
        [
            'nombre_completo' => 'Guantes de Nitrilo',
            'marca' => 'Ansell',
            'tipo' => 'PRODUCTO',
            'talla' => 'L',
            'color' => 'Azul',
            'descripcion' => 'Guantes desechables de nitrilo para protección química',
            'activo' => 1,
            'categoria_id' => $categoriaIds[1] ?? null,
        ],
        [
            'nombre_completo' => 'Botas de Seguridad con Punta de Acero',
            'marca' => 'Timberland',
            'tipo' => 'PRODUCTO',
            'talla' => '42',
            'color' => 'Negro',
            'descripcion' => 'Botas antideslizantes con punta de acero certificadas',
            'activo' => 1,
            'categoria_id' => $categoriaIds[2] ?? null,
        ],
        [
            'nombre_completo' => 'Gafas de Seguridad Antimpacto',
            'marca' => 'Oakley',
            'tipo' => 'PRODUCTO',
            'talla' => 'Única',
            'color' => 'Transparente',
            'descripcion' => 'Gafas de protección contra impactos y rayos UV',
            'activo' => 1,
            'categoria_id' => $categoriaIds[3] ?? null,
        ],
        [
            'nombre_completo' => 'Chaleco Reflectivo de Alta Visibilidad',
            'marca' => 'SafetyPro',
            'tipo' => 'PRODUCTO',
            'talla' => 'M',
            'color' => 'Naranja',
            'descripcion' => 'Chaleco reflectivo certificado para trabajo en alturas',
            'activo' => 1,
            'categoria_id' => $categoriaIds[4] ?? null,
        ],
        [
            'nombre_completo' => 'Servicio de Mantenimiento de EPP',
            'marca' => null,
            'tipo' => 'SERVICIO',
            'talla' => null,
            'color' => null,
            'descripcion' => 'Inspección y mantenimiento de equipos de protección personal',
            'activo' => 1,
            'categoria_id' => null,
        ],
    ];
    
    foreach ($epps as $epp) {
        $id = DB::table('epps')->insertGetId([
            'nombre_completo' => $epp['nombre_completo'],
            'marca' => $epp['marca'],
            'tipo' => $epp['tipo'],
            'talla' => $epp['talla'],
            'color' => $epp['color'],
            'descripcion' => $epp['descripcion'],
            'activo' => $epp['activo'],
            'categoria_id' => $epp['categoria_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "EPP creado: {$epp['nombre_completo']} (ID: {$id})\n";
    }
    
    // Verificar resultado
    $total = DB::table('epps')->count();
    $activos = DB::table('epps')->where('activo', 1)->count();
    
    echo "\n=== RESUMEN ===\n";
    echo "Total EPPs creados: {$total}\n";
    echo "EPPs activos: {$activos}\n";
    
    echo "\nEPPs creados:\n";
    $eppsCreados = DB::table('epps')->get();
    foreach ($eppsCreados as $epp) {
        echo "- ID: {$epp->id}, Nombre: {$epp->nombre_completo}, Tipo: {$epp->tipo}, Activo: {$epp->activo}\n";
    }
    
    echo "\n=== ¡LISTO! ===\n";
    echo "Ahora puedes acceder a http://localhost:8000/epp para ver los EPPs\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    Log::error('Error creando EPPs de prueba', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
