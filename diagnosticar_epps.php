<?php

// Script de diagnóstico para EPPs
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNÓSTICO COMPLETO DE EPPs ===\n\n";

try {
    // 1. Verificar conexión a la base de datos
    echo "1. Verificando conexión a la base de datos...\n";
    try {
        $version = DB::select('SELECT VERSION() as version')[0]->version;
        echo "   ✅ Conexión OK - MySQL: {$version}\n\n";
    } catch (Exception $e) {
        echo "   ❌ Error de conexión: " . $e->getMessage() . "\n\n";
        exit(1);
    }

    // 2. Verificar si la tabla epps existe
    echo "2. Verificando tabla epps...\n";
    try {
        $tableExists = DB::select("SHOW TABLES LIKE 'epps'");
        if (count($tableExists) > 0) {
            echo "   ✅ Tabla epps existe\n";
        } else {
            echo "   ❌ Tabla epps NO existe\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "   ❌ Error verificando tabla: " . $e->getMessage() . "\n";
        exit(1);
    }

    // 3. Verificar estructura de la tabla
    echo "\n3. Estructura de la tabla epps:\n";
    try {
        $columns = DB::select("SHOW COLUMNS FROM epps");
        foreach ($columns as $col) {
            echo "   - {$col->Field}: {$col->Type} (Null: {$col->Null}, Default: {$col->Default})\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error obteniendo estructura: " . $e->getMessage() . "\n";
    }

    // 4. Contar registros
    echo "\n4. Conteo de registros:\n";
    try {
        $total = DB::table('epps')->count();
        $activos = DB::table('epps')->where('activo', 1)->count();
        $inactivos = DB::table('epps')->where('activo', 0)->count();
        
        echo "   - Total: {$total}\n";
        echo "   - Activos: {$activos}\n";
        echo "   - Inactivos: {$inactivos}\n";
    } catch (Exception $e) {
        echo "   ❌ Error contando registros: " . $e->getMessage() . "\n";
    }

    // 5. Verificar primeros registros
    echo "\n5. Primeros 5 registros:\n";
    try {
        $epps = DB::table('epps')->limit(5)->get();
        foreach ($epps as $epp) {
            echo "   ID: {$epp->id}, Nombre: {$epp->nombre_completo}, Activo: {$epp->activo}\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error obteniendo registros: " . $e->getMessage() . "\n";
    }

    // 6. Verificar tabla epp_categorias
    echo "\n6. Verificando tabla epp_categorias...\n";
    try {
        $catExists = DB::select("SHOW TABLES LIKE 'epp_categorias'");
        if (count($catExists) > 0) {
            echo "   ✅ Tabla epp_categorias existe\n";
            $catCount = DB::table('epp_categorias')->count();
            echo "   - Categorías totales: {$catCount}\n";
            
            if ($catCount > 0) {
                $cats = DB::table('epp_categorias')->limit(3)->get();
                foreach ($cats as $cat) {
                    echo "   - ID: {$cat->id}, Nombre: {$cat->nombre}, Activo: {$cat->activo}\n";
                }
            }
        } else {
            echo "   ❌ Tabla epp_categorias NO existe\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error verificando categorías: " . $e->getMessage() . "\n";
    }

    // 7. Probar consulta simple como la del controlador
    echo "\n7. Probando consulta del controlador (indexSimple)...\n";
    try {
        $query = DB::table('epps')
            ->where('activo', 1)
            ->orderBy('nombre_completo')
            ->limit(20);
        
        $epps = $query->get();
        $total = $query->count();
        
        echo "   ✅ Consulta exitosa\n";
        echo "   - Total encontrados: {$total}\n";
        echo "   - Devueltos: " . $epps->count() . "\n";
        
        if ($epps->count() > 0) {
            echo "   - Primer resultado: " . $epps->first()->nombre_completo . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error en consulta: " . $e->getMessage() . "\n";
        echo "   - File: " . $e->getFile() . "\n";
        echo "   - Line: " . $e->getLine() . "\n";
    }

    // 8. Probar consulta con paginación
    echo "\n8. Probando consulta con paginación...\n";
    try {
        $page = 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $total = DB::table('epps')->where('activo', 1)->count();
        $epps = DB::table('epps')
            ->where('activo', 1)
            ->offset($offset)
            ->limit($perPage)
            ->orderBy('nombre_completo')
            ->get();
        
        echo "   ✅ Paginación exitosa\n";
        echo "   - Página: {$page}, Por página: {$perPage}\n";
        echo "   - Total: {$total}, Devueltos: " . $epps->count() . "\n";
    } catch (Exception $e) {
        echo "   ❌ Error en paginación: " . $e->getMessage() . "\n";
    }

    // 9. Verificar si hay índices en la tabla
    echo "\n9. Índices de la tabla epps:\n";
    try {
        $indexes = DB::select("SHOW INDEX FROM epps");
        $indexNames = [];
        foreach ($indexes as $index) {
            if (!in_array($index->Key_name, $indexNames)) {
                $indexNames[] = $index->Key_name;
                echo "   - {$index->Key_name}: {$index->Column_name}\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Error obteniendo índices: " . $e->getMessage() . "\n";
    }

    // 10. Probar consulta JSON (simular respuesta API)
    echo "\n10. Simulando respuesta JSON del controlador...\n";
    try {
        $page = 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $query = DB::table('epps')->where('activo', 1);
        $total = $query->count();
        $epps = $query->offset($offset)->limit($perPage)->orderBy('nombre_completo')->get();
        
        $response = [
            'success' => true,
            'data' => $epps->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
        
        echo "   ✅ Respuesta JSON generada correctamente\n";
        echo "   - Tamaño JSON: " . strlen(json_encode($response)) . " bytes\n";
        echo "   - Registros en respuesta: " . count($response['data']) . "\n";
    } catch (Exception $e) {
        echo "   ❌ Error generando JSON: " . $e->getMessage() . "\n";
    }

    echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
    echo "Si todo está bien aquí, el problema está en el controlador o en el frontend.\n";

} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
