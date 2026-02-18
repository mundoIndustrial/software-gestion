<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegistroOrdenController;
use App\Http\Controllers\RegistroOrdenQueryController;
use App\Http\Controllers\RegistroBodegaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\TablerosController;
use App\Http\Controllers\VistasController;
use App\Http\Controllers\BalanceoController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;
use App\Infrastructure\Http\Controllers\CotizacionBordadoController;
use App\Http\Controllers\DebugRegistrosController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\StorageController;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalController;
use App\Infrastructure\Http\Controllers\AsistenciaPersonalWebController;
use App\Http\Controllers\TestTelasPrendaController;
use App\Http\Controllers\PDFPrendaController;
use App\Http\Controllers\PDFCotizacionCombiadaController;
use App\Http\Controllers\PDFLogoController;

// Ruta temporal para verificar datos de la base de datos
Route::get('/verificar-datos-bd', function () {
    echo '<h1>=== VERIFICACIÓN DE DATOS EN BASE DE DATOS ===</h1>';
    
    try {
        // 1. Verificar tabla pedidos
        $tables = DB::select('SHOW TABLES LIKE "pedidos"');
        if (empty($tables)) {
            echo '<p> La tabla pedidos no existe</p>';
        } else {
            echo '<p> Tabla pedidos encontrada</p>';
        }
        
        // 2. Verificar columnas
        $columns = Schema::getColumnListing('pedidos');
        $columnasRelevantes = ['cliente', 'numero_pedido', 'rechazado_por_cartera_en'];
        echo '<h2>Columnas:</h2>';
        foreach ($columnasRelevantes as $columna) {
            if (in_array($columna, $columns)) {
                echo "<p> Columna {$columna} encontrada</p>";
            } else {
                echo "<p> Columna {$columna} NO encontrada</p>";
            }
        }
        
        // 3. Conteo total
        $total = DB::table('pedidos')->count();
        echo "<p>📊 Total de registros: {$total}</p>";
        
        // 4. Clientes
        $clientesConDatos = DB::table('pedidos')->whereNotNull('cliente')->where('cliente', '!=', '')->count();
        $clientesUnicos = DB::table('pedidos')->whereNotNull('cliente')->where('cliente', '!=', '')->distinct('cliente')->count();
        echo "<p>👥 Clientes con datos: {$clientesConDatos}</p>";
        echo "<p>👥 Clientes únicos: {$clientesUnicos}</p>";
        
        // 5. Números
        $numerosConDatos = DB::table('pedidos')->whereNotNull('numero_pedido')->where('numero_pedido', '!=', '')->count();
        $numerosUnicos = DB::table('pedidos')->whereNotNull('numero_pedido')->where('numero_pedido', '!=', '')->distinct('numero_pedido')->count();
        echo "<p> Números con datos: {$numerosConDatos}</p>";
        echo "<p> Números únicos: {$numerosUnicos}</p>";
        
        // 6. Fechas
        $fechasConDatos = DB::table('pedidos')->whereNotNull('rechazado_por_cartera_en')->where('rechazado_por_cartera_en', '!=', '')->count();
        $fechasUnicas = DB::table('pedidos')->whereNotNull('rechazado_por_cartera_en')->where('rechazado_por_cartera_en', '!=', '')->distinct('rechazado_por_cartera_en')->count();
        echo "<p>📅 Fechas con datos: {$fechasConDatos}</p>";
        echo "<p>📅 Fechas únicas: {$fechasUnicas}</p>";
        
        // 7. Prueba de búsqueda
        echo '<h2>Prueba de búsqueda (ejemplo: "hornos"):</h2>';
        $busquedaEjemplo = 'hornos';
        $resultadosClientes = DB::table('pedidos')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busquedaEjemplo) . '%'])
            ->distinct('cliente')
            ->orderBy('cliente')
            ->limit(3)
            ->pluck('cliente');
        
        if (!empty($resultadosClientes)) {
            echo "<p> Búsqueda '{$busquedaEjemplo}' encontró " . count($resultadosClientes) . " resultados:</p>";
            echo '<ul>';
            foreach ($resultadosClientes as $resultado) {
                echo "<li>{$resultado}</li>";
            }
            echo '</ul>';
        } else {
            echo "<p> Búsqueda '{$busquedaEjemplo}' no encontró resultados</p>";
        }
        
        // 8. Ejemplos de datos
        echo '<h2>Ejemplos de datos:</h2>';
        
        // Ejemplos de clientes
        $ejemplosClientes = DB::table('pedidos')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->distinct('cliente')
            ->orderBy('cliente')
            ->limit(5)
            ->pluck('cliente');
        
        echo '<h3>Clientes:</h3>';
        echo '<ul>';
        foreach ($ejemplosClientes as $cliente) {
            echo "<li>{$cliente}</li>";
        }
        echo '</ul>';
        
        // Ejemplos de números
        $ejemplosNumeros = DB::table('pedidos')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->distinct('numero_pedido')
            ->orderBy('numero_pedido')
            ->limit(5)
            ->pluck('numero_pedido');
        
        echo '<h3>Números de pedido:</h3>';
        echo '<ul>';
        foreach ($ejemplosNumeros as $numero) {
            echo "<li>{$numero}</li>";
        }
        echo '</ul>';
        
        // Ejemplos de fechas
        $ejemplosFechas = DB::table('pedidos')
            ->whereNotNull('rechazado_por_cartera_en')
            ->where('rechazado_por_cartera_en', '!=', '')
            ->distinct('rechazado_por_cartera_en')
            ->orderBy('rechazado_por_cartera_en', 'desc')
            ->limit(5)
            ->pluck('rechazado_por_cartera_en');
        
        echo '<h3>Fechas de rechazo:</h3>';
        echo '<ul>';
        foreach ($ejemplosFechas as $fecha) {
            $carbon = \Carbon\Carbon::parse($fecha);
            echo "<li>" . $carbon->format('d/m/Y') . "</li>";
        }
        echo '</ul>';
        
        echo '<h2>=== VERIFICACIÓN COMPLETADA ===</h2>';
        echo '<p> Todos los datos necesarios para el autocompletar están disponibles</p>';
        echo '<p>🚀 El sistema de sugerencias debería funcionar correctamente</p>';
        
    } catch (\Exception $e) {
        echo '<p> Error: ' . $e->getMessage() . '</p>';
    }
})->name('verificar.datos.bd');

// Ruta para ver todas las tablas de la base de datos
Route::get('/verificar-tablas-bd', function () {
    echo '<h1>=== TABLAS DISPONIBLES EN BASE DE DATOS ===</h1>';
    
    try {
        // Obtener todas las tablas
        $tables = DB::select('SHOW TABLES');
        
        echo '<h2>Tablas encontradas:</h2>';
        echo '<ul>';
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li><strong>{$tableName}</strong></li>";
        }
        echo '</ul>';
        
        // Buscar tablas que podrían contener datos de pedidos
        echo '<h2>Posibles tablas de pedidos:</h2>';
        $possibleTables = [];
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            if (stripos($tableName, 'pedido') !== false || 
                stripos($tableName, 'order') !== false ||
                stripos($tableName, 'sale') !== false ||
                stripos($tableName, 'venta') !== false) {
                $possibleTables[] = $tableName;
            }
        }
        
        if (!empty($possibleTables)) {
            echo '<ul>';
            foreach ($possibleTables as $tableName) {
                echo "<li><strong>{$tableName}</strong> (posible tabla de pedidos)</li>";
                
                // Verificar columnas de esta tabla
                try {
                    $columns = Schema::getColumnListing($tableName);
                    $relevantColumns = ['cliente', 'numero_pedido', 'rechazado_por_cartera_en', 'customer', 'order_number', 'order_id', 'pedido_id', 'numero', 'cliente_nombre'];
                    
                    echo '<ul>';
                    foreach ($relevantColumns as $column) {
                        if (in_array($column, $columns)) {
                            echo "<li> Columna '{$column}' encontrada</li>";
                        }
                    }
                    echo '</ul>';
                    
                    // Mostrar conteo de registros
                    $count = DB::table($tableName)->count();
                    echo "<li>📊 Registros: {$count}</li>";
                    
                } catch (\Exception $e) {
                    echo "<li> Error al verificar columnas: {$e->getMessage()}</li>";
                }
                
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No se encontraron tablas que parezcan contener datos de pedidos.</p>';
        }
        
        echo '<h2>Recomendación:</h2>';
        echo '<p>Busca en la lista anterior una tabla que contenga las columnas necesarias para el autocompletar.</p>';
        echo '<p>Una vez identificada la tabla correcta, actualiza las rutas API para usar esa tabla.</p>';
        
    } catch (\Exception $e) {
        echo '<p> Error: ' . $e->getMessage() . '</p>';
    }
})->name('verificar.tablas.bd');

// ========================================
// RUTAS DE SUGERENCIAS DE CARTERA - PEDIDOS (pendiente_cartera)
// ========================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('cartera/pedidos')->group(function () {
        Route::post('/clientes/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener clientes únicos de pedidos con estado pendiente_cartera
                $clientes = \DB::table('pedidos_produccion')
                    ->select('cliente')
                    ->where('estado', '=', 'pendiente_cartera')
                    ->whereNotNull('cliente')
                    ->where('cliente', '!=', '')
                    ->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busqueda) . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('cliente')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($clientes, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return strcasecmp($a, $b);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $clientes
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.pedidos.clientes.sugerencias');
        
        Route::post('/numeros/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener números únicos de pedidos con estado pendiente_cartera
                $numeros = \DB::table('pedidos_produccion')
                    ->select('numero_pedido')
                    ->where('estado', '=', 'pendiente_cartera')
                    ->whereNotNull('numero_pedido')
                    ->where('numero_pedido', '!=', '')
                    ->whereRaw('CAST(numero_pedido AS CHAR) LIKE ?', ['%' . $busqueda . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('numero_pedido')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($numeros, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return $a - $b;
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $numeros
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.pedidos.numeros.sugerencias');
        
        Route::post('/fechas/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Usar created_at para fechas de pedidos pendientes
                $query = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->select('created_at')
                    ->where('estado', '=', 'pendiente_cartera')
                    ->whereNotNull('created_at');
                
                // Debug: contar total de registros
                $totalCount = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->where('estado', '=', 'pendiente_cartera')
                    ->whereNotNull('created_at')
                    ->count();
                
                // Solo agregar condición de búsqueda si hay texto
                if (!empty($busqueda) && $busqueda !== null) {
                    $query->whereRaw('DATE_FORMAT(created_at, "%d/%m/%Y") LIKE ?', ['%' . strtolower($busqueda) . '%']);
                }
                
                $fechas = $query->distinct()
                    ->limit(10)
                    ->pluck('created_at')
                    ->toArray();
                
                // Formatear fechas a dd/mm/yyyy
                $fechasFormateadas = array_map(function($fecha) {
                    $date = new \DateTime($fecha);
                    return $date->format('d/m/Y');
                }, $fechas);
                
                // Ordenar por relevancia y luego por fecha (más reciente primero)
                usort($fechasFormateadas, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    // Coincidencia exacta al principio
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    // Coincidencia exacta
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    // Ordenar por fecha (más reciente primero)
                    $dateA = \DateTime::createFromFormat('d/m/Y', $a);
                    $dateB = \DateTime::createFromFormat('d/m/Y', $b);
                    if ($dateA && $dateB) {
                        return $dateB <=> $dateA;
                    }
                    
                    return strcmp($b, $a);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $fechasFormateadas,
                    'debug' => [
                        'test' => 'WEB_MODE_ACTIVE',
                        'busqueda' => $busqueda,
                        'fechas_originales_count' => count($fechas),
                        'fechas_formateadas_count' => count($fechasFormateadas),
                        'total_count' => $totalCount
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage(),
                    'debug' => [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);
            }
        })->name('cartera.pedidos.fechas.sugerencias');
    });
});

// ========================================
// RUTAS DE SUGERENCIAS DE CARTERA
// ========================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('cartera/rechazados')->group(function () {
                
        Route::post('/clientes/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // DEBUG: Verificar qué clientes existen con RECHAZADO_CARTERA
                $todosLosRechazados = \DB::table('pedidos_produccion')
                    ->select('cliente', 'estado')
                    ->where('estado', '=', 'RECHAZADO_CARTERA')
                    ->whereNotNull('cliente')
                    ->where('cliente', '!=', '')
                    ->distinct()
                    ->get();
                
                $debugRechazados = $todosLosRechazados->toArray();
                
                // DEBUG: Verificar si MINCIVIL existe en la tabla
                $mincivilExists = \DB::table('pedidos_produccion')
                    ->where('cliente', 'LIKE', '%MINCIVIL%')
                    ->get();
                
                $debugMincivil = $mincivilExists->toArray();
                
                // DEBUG: Verificar si MINCIVIL tiene RECHAZADO_CARTERA
                $mincivilRechazados = \DB::table('pedidos_produccion')
                    ->where('cliente', 'LIKE', '%MINCIVIL%')
                    ->where('estado', '=', 'RECHAZADO_CARTERA')
                    ->get();
                
                $debugMincivilRechazados = $mincivilRechazados->toArray();
                
                // Obtener clientes únicos de pedidos con estado RECHAZADO_CARTERA
                $clientes = \DB::table('pedidos_produccion')
                    ->select('cliente')
                    ->where('estado', '=', 'RECHAZADO_CARTERA')
                    ->whereNotNull('cliente')
                    ->where('cliente', '!=', '')
                    ->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busqueda) . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('cliente')
                    ->toArray();
                
                $debugClientesFiltrados = $clientes;
                
                // Ordenar por relevancia: primero coincidencias exactas al principio
                usort($clientes, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    // Coincidencia exacta al principio
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    // Coincidencia exacta
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    // Orden alfabético
                    return strcasecmp($a, $b);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $clientes,
                    'debug' => [
                        'busqueda' => $busqueda,
                        'todos_rechazados' => $debugRechazados,
                        'mincivil_exists' => $debugMincivil,
                        'mincivil_rechazados' => $debugMincivilRechazados,
                        'clientes_filtrados' => $debugClientesFiltrados
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.rechazados.clientes.sugerencias');
        
        Route::post('/numeros/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener números únicos de pedidos con estado RECHAZADO_CARTERA
                $numeros = \DB::table('pedidos_produccion')
                    ->select('numero_pedido')
                    ->where('estado', '=', 'RECHAZADO_CARTERA')
                    ->whereNotNull('numero_pedido')
                    ->where('numero_pedido', '!=', '')
                    ->whereRaw('CAST(numero_pedido AS CHAR) LIKE ?', ['%' . $busqueda . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('numero_pedido')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($numeros, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    // Coincidencia exacta al principio
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    // Coincidencia exacta
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    // Orden numérico
                    return $a - $b;
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $numeros
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.rechazados.numeros.sugerencias');
        
        Route::post('/fechas/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Versión simplificada sin dependencias problemáticas
                try {
                    // Log inicial para debug
                    \Log::info('DEBUG_FECHAS: Iniciando consulta', [
                        'busqueda' => $busqueda,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    
                    // DEBUG: Verificar qué valores hay realmente en la tabla
                    $rawData = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                        ->select('id', 'estado', 'rechazado_por_cartera_en', 'motivo_rechazo_cartera')
                        ->where('estado', 'RECHAZADO_CARTERA')
                        ->limit(5)
                        ->get();
                    
                    \Log::info('DEBUG_FECHAS: Datos crudos en pedidos_produccion', [
                        'raw_data_count' => count($rawData),
                        'raw_data' => $rawData->toArray()
                    ]);
                    
                    // Usar la tabla correcta: pedidos_produccion
                    $query = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                        ->select('rechazado_por_cartera_en')
                        ->whereNotNull('rechazado_por_cartera_en');
                    
                    // Debug: contar total de registros primero
                    $totalCount = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                        ->whereNotNull('rechazado_por_cartera_en')
                        ->count();
                    
                    // Debug: ver qué valores tienen los registros RECHAZADO_CARTERA
                    $rechazadosValues = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                        ->select('id', 'estado', 'rechazado_por_cartera_en')
                        ->where('estado', 'RECHAZADO_CARTERA')
                        ->get();
                    
                    \Log::info('DEBUG_FECHAS: Valores RECHAZADO_CARTERA', [
                        'rechazados_count' => count($rechazadosValues),
                        'rechazados_values' => $rechazadosValues->toArray()
                    ]);
                    
                    \Log::info('DEBUG_FECHAS: Total registros encontrados', [
                        'total_count' => $totalCount,
                        'busqueda' => $busqueda
                    ]);
                    
                    // Solo agregar condición de búsqueda si hay texto
                    if (!empty($busqueda) && $busqueda !== null) {
                        // Simplificar: solo buscar en formato DD/MM/YYYY que es más intuitivo
                        $query->whereRaw('DATE_FORMAT(rechazado_por_cartera_en, "%d/%m/%Y") LIKE ?', ['%' . strtolower($busqueda) . '%']);
                    }
                    
                    $fechas = $query->distinct()
                        ->limit(10)
                        ->pluck('rechazado_por_cartera_en')
                        ->toArray();
                    
                    \Log::info('DEBUG_FECHAS: Fechas obtenidas', [
                        'fechas_count' => count($fechas),
                        'fechas' => $fechas,
                        'busqueda' => $busqueda
                    ]);
                        
                } catch (\Exception $dbError) {
                    // Si hay error en la consulta, devolver vacío con info de error
                    return response()->json([
                        'success' => true,
                        'sugerencias' => [],
                        'debug' => [
                            'error_db' => $dbError->getMessage(),
                            'busqueda' => $busqueda,
                            'total_count' => $totalCount ?? 0
                        ]
                    ]);
                }
                
                // Formatear fechas a dd/mm/yyyy
                $fechasFormateadas = [];
                foreach ($fechas as $fecha) {
                    try {
                        $date = new \DateTime($fecha);
                        $fechasFormateadas[] = $date->format('d/m/Y');
                    } catch (\Exception $dateError) {
                        // Si hay error al formatear, usar la fecha original
                        $fechasFormateadas[] = $fecha;
                    }
                }
                
                // Ordenar por fecha (más reciente primero)
                usort($fechasFormateadas, function ($a, $b) {
                    try {
                        $dateA = \DateTime::createFromFormat('d/m/Y', $a);
                        $dateB = \DateTime::createFromFormat('d/m/Y', $b);
                        return $dateB <=> $dateA;
                    } catch (\Exception $e) {
                        // Si hay error en el formato, ordenar como string
                        return strcmp($b, $a);
                    }
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $fechasFormateadas,
                    'debug' => [
                        'busqueda' => $busqueda,
                        'fechas_originales_count' => count($fechas),
                        'fechas_formateadas_count' => count($fechasFormateadas),
                        'total_count' => $totalCount ?? 0,
                        'fechas_originales' => $fechas,
                        'fechas_formateadas' => $fechasFormateadas,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'test' => 'WEB_MODE_ACTIVE'
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage(),
                    'debug' => [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);
            }
        })->name('cartera.rechazados.fechas.sugerencias');
    });
});

// ========================================
// RUTAS DE SUGERENCIAS DE CARTERA - APROBADOS
// ========================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('cartera/aprobados')->group(function () {
        Route::post('/clientes/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener clientes únicos de pedidos con estado PENDIENTE_SUPERVISOR
                $clientes = \DB::table('pedidos_produccion')
                    ->select('cliente')
                    ->where('estado', '=', 'PENDIENTE_SUPERVISOR')
                    ->whereNotNull('cliente')
                    ->where('cliente', '!=', '')
                    ->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busqueda) . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('cliente')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($clientes, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return strcasecmp($a, $b);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $clientes
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.aprobados.clientes.sugerencias');
        
        Route::post('/numeros/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener números únicos de pedidos con estado PENDIENTE_SUPERVISOR
                $numeros = \DB::table('pedidos_produccion')
                    ->select('numero_pedido')
                    ->where('estado', '=', 'PENDIENTE_SUPERVISOR')
                    ->whereNotNull('numero_pedido')
                    ->where('numero_pedido', '!=', '')
                    ->whereRaw('CAST(numero_pedido AS CHAR) LIKE ?', ['%' . $busqueda . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('numero_pedido')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($numeros, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return $a - $b;
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $numeros
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.aprobados.numeros.sugerencias');
        
        Route::post('/fechas/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Log inicial para debug
                \Log::info('DEBUG_FECHAS_APROBADOS: Iniciando consulta', [
                    'busqueda' => $busqueda,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                // DEBUG: Verificar qué valores hay realmente en la tabla
                $rawData = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->select('id', 'estado', 'aprobado_por_cartera_en', 'aprobado_por_supervisor_en')
                    ->whereIn('estado', ['PENDIENTE_SUPERVISOR', 'pendiente_cartera'])
                    ->limit(5)
                    ->get();
                
                \Log::info('DEBUG_FECHAS_APROBADOS: Datos crudos en pedidos_produccion', [
                    'raw_data_count' => count($rawData),
                    'raw_data' => $rawData->toArray()
                ]);
                
                // Usar la tabla correcta: pedidos_produccion
                $query = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->select('aprobado_por_cartera_en')
                    ->whereNotNull('aprobado_por_cartera_en');
                
                // Debug: contar total de registros primero
                $totalCount = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->whereNotNull('aprobado_por_cartera_en')
                    ->count();
                
                \Log::info('DEBUG_FECHAS_APROBADOS: Total registros encontrados', [
                    'total_count' => $totalCount,
                    'busqueda' => $busqueda
                ]);
                
                // Solo agregar condición de búsqueda si hay texto
                if (!empty($busqueda) && $busqueda !== null) {
                    // Simplificar: solo buscar en formato DD/MM/YYYY que es más intuitivo
                    $query->whereRaw('DATE_FORMAT(aprobado_por_cartera_en, "%d/%m/%Y") LIKE ?', ['%' . strtolower($busqueda) . '%']);
                }
                
                $fechas = $query->distinct()
                    ->limit(10)
                    ->pluck('aprobado_por_cartera_en')
                    ->toArray();
                
                \Log::info('DEBUG_FECHAS_APROBADOS: Fechas obtenidas', [
                    'fechas_count' => count($fechas),
                    'fechas' => $fechas,
                    'busqueda' => $busqueda
                ]);
                
                // Formatear fechas a dd/mm/yyyy
                $fechasFormateadas = array_map(function($fecha) {
                    $date = new \DateTime($fecha);
                    return $date->format('d/m/Y');
                }, $fechas);
                
                // Ordenar por relevancia y luego por fecha (más reciente primero)
                usort($fechasFormateadas, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    // Coincidencia exacta al principio
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    // Coincidencia exacta
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    // Ordenar por fecha (más reciente primero)
                    $dateA = \DateTime::createFromFormat('d/m/Y', $a);
                    $dateB = \DateTime::createFromFormat('d/m/Y', $b);
                    if ($dateA && $dateB) {
                        return $dateB <=> $dateA;
                    }
                    
                    return strcmp($b, $a);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $fechasFormateadas,
                    'debug' => [
                        'test' => 'WEB_MODE_ACTIVE',
                        'busqueda' => $busqueda,
                        'fechas_originales_count' => count($fechas),
                        'fechas_formateadas_count' => count($fechasFormateadas),
                        'total_count' => $totalCount
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.aprobados.fechas.sugerencias');
    });
});

// ========================================
// RUTAS DE SUGERENCIAS DE CARTERA - ANULADOS
// ========================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('cartera/anulados')->group(function () {
        Route::post('/clientes/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener clientes únicos de pedidos con estado Anulada
                $clientes = \DB::table('pedidos_produccion')
                    ->select('cliente')
                    ->where('estado', '=', 'Anulada')
                    ->whereNotNull('cliente')
                    ->where('cliente', '!=', '')
                    ->whereRaw('LOWER(cliente) LIKE ?', ['%' . strtolower($busqueda) . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('cliente')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($clientes, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return strcasecmp($a, $b);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $clientes
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.anulados.clientes.sugerencias');
        
        Route::post('/numeros/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Obtener números únicos de pedidos con estado Anulada
                $numeros = \DB::table('pedidos_produccion')
                    ->select('numero_pedido')
                    ->where('estado', '=', 'Anulada')
                    ->whereNotNull('numero_pedido')
                    ->where('numero_pedido', '!=', '')
                    ->whereRaw('CAST(numero_pedido AS CHAR) LIKE ?', ['%' . $busqueda . '%'])
                    ->distinct()
                    ->limit(10)
                    ->pluck('numero_pedido')
                    ->toArray();
                
                // Ordenar por relevancia
                usort($numeros, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    return $a - $b;
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $numeros
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage()
                ], 500);
            }
        })->name('cartera.anulados.numeros.sugerencias');
        
        Route::post('/fechas/sugerencias', function (\Illuminate\Http\Request $request) {
            try {
                $busqueda = $request->input('busqueda', '');
                
                // Log inicial para debug
                \Log::info('DEBUG_FECHAS_ANULADOS: Iniciando consulta', [
                    'busqueda' => $busqueda,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                // Usar la tabla correcta: pedidos_produccion
                // Para anulados, usamos updated_at ya que no hay campo anulado_en específico
                $query = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->select('updated_at')
                    ->where('estado', '=', 'Anulada')
                    ->whereNotNull('updated_at');
                
                // Debug: contar total de registros primero
                $totalCount = \Illuminate\Support\Facades\DB::table('pedidos_produccion')
                    ->where('estado', '=', 'Anulada')
                    ->whereNotNull('updated_at')
                    ->count();
                
                \Log::info('DEBUG_FECHAS_ANULADOS: Total registros encontrados', [
                    'total_count' => $totalCount,
                    'busqueda' => $busqueda
                ]);
                
                // Solo agregar condición de búsqueda si hay texto
                if (!empty($busqueda) && $busqueda !== null) {
                    // Simplificar: solo buscar en formato DD/MM/YYYY que es más intuitivo
                    $query->whereRaw('DATE_FORMAT(updated_at, "%d/%m/%Y") LIKE ?', ['%' . strtolower($busqueda) . '%']);
                }
                
                $fechas = $query->distinct()
                    ->limit(10)
                    ->pluck('updated_at')
                    ->toArray();
                
                \Log::info('DEBUG_FECHAS_ANULADOS: Fechas obtenidas', [
                    'fechas_count' => count($fechas),
                    'fechas' => $fechas,
                    'busqueda' => $busqueda
                ]);
                
                // Formatear fechas a dd/mm/yyyy
                $fechasFormateadas = array_map(function($fecha) {
                    $date = new \DateTime($fecha);
                    return $date->format('d/m/Y');
                }, $fechas);
                
                // Ordenar por relevancia y luego por fecha (más reciente primero)
                usort($fechasFormateadas, function ($a, $b) use ($busqueda) {
                    $aLower = strtolower($a);
                    $bLower = strtolower($b);
                    $busquedaLower = strtolower($busqueda);
                    
                    // Coincidencia exacta al principio
                    if (str_starts_with($aLower, $busquedaLower) && !str_starts_with($bLower, $busquedaLower)) {
                        return -1;
                    }
                    if (str_starts_with($bLower, $busquedaLower) && !str_starts_with($aLower, $busquedaLower)) {
                        return 1;
                    }
                    
                    // Coincidencia exacta
                    if ($aLower === $busquedaLower && $bLower !== $busquedaLower) {
                        return -1;
                    }
                    if ($bLower === $busquedaLower && $aLower !== $busquedaLower) {
                        return 1;
                    }
                    
                    // Ordenar por fecha (más reciente primero)
                    $dateA = \DateTime::createFromFormat('d/m/Y', $a);
                    $dateB = \DateTime::createFromFormat('d/m/Y', $b);
                    if ($dateA && $dateB) {
                        return $dateB <=> $dateA;
                    }
                    
                    return strcmp($b, $a);
                });
                
                return response()->json([
                    'success' => true,
                    'sugerencias' => $fechasFormateadas,
                    'debug' => [
                        'test' => 'WEB_MODE_ACTIVE',
                        'busqueda' => $busqueda,
                        'fechas_originales_count' => count($fechas),
                        'fechas_formateadas_count' => count($fechasFormateadas),
                        'total_count' => $totalCount
                    ]
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al cargar sugerencias: ' . $e->getMessage(),
                    'debug' => [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);
            }
        })->name('cartera.anulados.fechas.sugerencias');
    });
});

Route::get('/', function () {
    return view('welcome');
});

// Ruta de prueba para verificar Echo/Reverb
Route::get('/test-echo', function () {
    return view('test-echo');
})->name('test.echo');

// Ruta de prueba para PDF upload
Route::get('/test-pdf-upload', function () {
    return view('test-pdf-upload');
})->name('test.pdf-upload');

// ========================================
// RUTAS DE STORAGE - Servir imágenes con fallback de extensiones
// ========================================
// Intercepta /storage/cotizaciones/{path} y sirve .webp si .png no existe
Route::get('/storage/cotizaciones/{path}', function ($path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = 'cotizaciones/' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (str_ends_with($fullPath, '.png')) {
        $pathWebp = substr($fullPath, 0, -4) . '.webp';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (str_ends_with($fullPath, '.jpg') || str_ends_with($fullPath, '.jpeg')) {
        $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    abort(404, 'Imagen no encontrada');
})->where('path', '.*')->name('storage.cotizaciones');

// Intercepta /storage/prendas/{path} y sirve archivos con fallback de extensiones
Route::get('/storage/prendas/{path}', function ($path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = 'prendas/' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (str_ends_with($fullPath, '.png')) {
        $pathWebp = substr($fullPath, 0, -4) . '.webp';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (str_ends_with($fullPath, '.jpg') || str_ends_with($fullPath, '.jpeg')) {
        $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    abort(404, 'Imagen no encontrada');
})->where('path', '.*')->name('storage.prendas');

// Intercepta /storage/pedidos/{path} y sirve archivos con fallback de extensiones
Route::get('/storage/pedidos/{path}', function ($path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = 'pedidos/' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (str_ends_with($fullPath, '.png')) {
        $pathWebp = substr($fullPath, 0, -4) . '.webp';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (str_ends_with($fullPath, '.jpg') || str_ends_with($fullPath, '.jpeg')) {
        $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return response($contents, 200)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    abort(404, 'Imagen no encontrada');
})->where('path', '.*')->name('storage.pedidos');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'supervisor-access'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Ruta pública para factura-datos (acceso para cualquier usuario autenticado)
    Route::get('/pedidos-public/{id}/factura-datos', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'obtenerDatosFactura'])
        ->where('id', '[0-9]+')
        ->name('pedidos.public.factura-datos');
    
    // Ruta pública para recibos-datos (acceso para cualquier usuario autenticado)
    Route::get('/pedidos-public/{id}/recibos-datos', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerDetalleCompleto'])
        ->where('id', '[0-9]+')
        ->name('pedidos.public.recibos-datos');
    
    // ========================================
    // RUTA PARA REFRESCAR TOKEN CSRF (Prevenir error 419)
    // ========================================
    Route::get('/refresh-csrf', function () {
        return response()->json([
            'token' => csrf_token(),
            'timestamp' => now()->toIso8601String()
        ]);
    })->name('refresh.csrf');
    
    // ========================================
    // RUTAS DE FOTOS (Accesibles para todos los roles autenticados)
    // ========================================
    Route::post('/asesores/fotos/eliminar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'eliminarFotoInmediatamente'])->name('fotos.eliminar-inmediatamente');
    
    // ========================================
    // RUTAS DE NOTIFICACIONES (Accesibles para todos los roles autenticados)
    // ========================================
    // Sistema unificado de notificaciones en tiempo real
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/mark-read-on-open', [App\Http\Controllers\NotificationController::class, 'markAsReadOnOpen'])->name('notifications.mark-read-on-open');
    
    // Contador (mantener compatibilidad)
    Route::post('/contador/notifications/marcar-leidas', [App\Http\Controllers\ContadorController::class, 'markAllNotificationsAsRead'])->name('contador.notifications.mark-all-read');
    Route::get('/contador/notifications', [App\Http\Controllers\ContadorController::class, 'getNotifications'])->name('contador.notifications');
    
    // Asesores (mantener compatibilidad)
    Route::post('/asesores/notifications/mark-all-read', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'markAllAsRead'])->name('asesores.notifications.mark-all-read');
    Route::post('/asesores/notifications/{notificationId}/mark-read', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'markNotificationAsRead'])->name('asesores.notifications.mark-read');
    Route::get('/asesores/notifications', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'getNotifications'])->name('asesores.notifications');
    
    // Supervisor Pedidos (mantener compatibilidad)
    Route::post('/supervisor-pedidos/notifications/mark-all-read', [App\Http\Controllers\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])->name('supervisor-pedidos.notifications.mark-all-read');
    
    // Insumos / Supervisor Planta (mantener compatibilidad)
    Route::post('/insumos/notifications/marcar-leidas', [App\Http\Controllers\Insumos\InsumosController::class, 'markAllNotificationsAsRead'])->name('insumos.notifications.mark-all-read');
    
    // ========================================
    // APIS DE CATÁLOGOS (Accesibles para todos los roles autenticados)
    // ========================================
    // Esto permite a supervisores y otros roles acceder a catálogos sin restricciones de roles
    Route::get('/api/public/tipos-manga', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTiposManga'])->name('api.public.tipos-manga');
    Route::post('/api/public/tipos-manga', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerTipoManga'])->name('api.public.tipos-manga.create');
    Route::get('/api/public/tipos-broche-boton', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTiposBrocheBoton'])->name('api.public.tipos-broche-boton');
    Route::get('/api/public/telas', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTelas'])->name('api.public.telas');
    Route::post('/api/public/telas', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerTela'])->name('api.public.telas.create');
    Route::get('/api/public/colores', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerColores'])->name('api.public.colores');
    Route::post('/api/public/colores', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerColor'])->name('api.public.colores.create');
});

Route::middleware(['auth', 'supervisor-access'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/dashboard/entregas-costura-data', [DashboardController::class, 'getEntregasCosturaData'])->name('dashboard.entregas-costura-data');
    Route::get('/dashboard/entregas-corte-data', [DashboardController::class, 'getEntregasCorteData'])->name('dashboard.entregas-corte-data');
    Route::get('/dashboard/kpis', [DashboardController::class, 'getKPIs'])->name('dashboard.kpis');
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/news', [DashboardController::class, 'getNews'])->name('dashboard.news');
    Route::get('/dashboard/admin-notifications', [DashboardController::class, 'getAdminNotifications'])->name('dashboard.admin-notifications');
    Route::post('/dashboard/news/mark-all-read', [DashboardController::class, 'markAllAsRead'])->name('dashboard.news.mark-all-read');
    Route::get('/dashboard/audit-stats', [DashboardController::class, 'getAuditStats'])->name('dashboard.audit-stats');
    Route::get('/entrega/{tipo}', [EntregaController::class, 'index'])->name('entrega.index')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/costura-data', [EntregaController::class, 'costuraData'])->name('entrega.costura-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/corte-data', [EntregaController::class, 'corteData'])->name('entrega.corte-data')->where('tipo', 'pedido|bodega');
    Route::post('/entrega/{tipo}', [EntregaController::class, 'store'])->name('entrega.store')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/order-data/{pedido}', [EntregaController::class, 'orderData'])->name('entrega.order-data')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/garments/{pedido}', [EntregaController::class, 'garments'])->name('entrega.garments')->where('tipo', 'pedido|bodega');
    Route::get('/entrega/{tipo}/sizes/{pedido}/{prenda}', [EntregaController::class, 'sizes'])->name('entrega.sizes')->where('tipo', 'pedido|bodega');
    Route::patch('/entrega/{tipo}/{subtipo}/{id}', [EntregaController::class, 'update'])->name('entrega.update')->where('tipo', 'pedido|bodega')->where('subtipo', 'costura|corte');
    Route::delete('/entrega/{tipo}/{subtipo}/{id}', [EntregaController::class, 'destroy'])->name('entrega.destroy')->where('tipo', 'pedido|bodega')->where('subtipo', 'costura|corte');
});

Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    // Query/Search routes (RegistroOrdenQueryController)
    Route::get('/registros', [RegistroOrdenQueryController::class, 'index'])->name('registros.index');
    
    // CRUD routes (RegistroOrdenController) - Rutas sin parámetros primero
    Route::get('/registros/next-pedido', [RegistroOrdenController::class, 'getNextPedido'])->name('registros.next-pedido');
    Route::get('/registros/filter-options', [RegistroOrdenController::class, 'getFilterOptions'])->name('registros.filter-options');
    Route::get('/registros/filter-column-options/{column}', [RegistroOrdenController::class, 'getColumnFilterOptions'])->name('registros.filter-column-options');
    Route::post('/registros/filter-orders', [RegistroOrdenController::class, 'filterOrders'])->name('registros.filter-orders');
    Route::post('/registros/search', [RegistroOrdenController::class, 'searchOrders'])->name('registros.search');
    
    // Rutas con parámetros {pedido} - IMPORTANTE: rutas más específicas PRIMERO
    Route::get('/registros/{id}/recibos-datos', [RegistroOrdenQueryController::class, 'getRecibosDatos'])->name('registros.recibos-datos');
    Route::get('/registros/{pedido}', [RegistroOrdenQueryController::class, 'show'])->name('registros.show');
    Route::get('/registros/{pedido}/images', [RegistroOrdenQueryController::class, 'getOrderImages'])->name('registros.images');
    Route::get('/registros/{pedido}/descripcion-prendas', [RegistroOrdenQueryController::class, 'getDescripcionPrendas'])->name('registros.descripcion-prendas');
    Route::get('/api/registros/{numero_pedido}/dias', [RegistroOrdenQueryController::class, 'calcularDiasAPI'])->name('api.registros.dias');
    Route::post('/api/registros/dias-batch', [RegistroOrdenQueryController::class, 'calcularDiasBatchAPI'])->name('api.registros.dias-batch');
    Route::post('/api/registros/{id}/calcular-fecha-estimada', [RegistroOrdenQueryController::class, 'calcularFechaEstimada'])->name('api.registros.calcular-fecha-estimada');
    
    //  Ruta para traer LogoPedido por ID
    Route::get('/api/logo-pedidos/{id}', [RegistroOrdenQueryController::class, 'showLogoPedidoById'])->name('api.logo-pedidos.show');
    
    Route::post('/registros', [RegistroOrdenController::class, 'store'])->name('registros.store');
    Route::post('/registros/validate-pedido', [RegistroOrdenController::class, 'validatePedido'])->name('registros.validatePedido');
    Route::post('/registros/update-pedido', [RegistroOrdenController::class, 'updatePedido'])->name('registros.updatePedido');
    Route::post('/registros/update-descripcion-prendas', [RegistroOrdenController::class, 'updateDescripcionPrendas'])->name('registros.updateDescripcionPrendas');
    Route::patch('/registros/{pedido}', [RegistroOrdenController::class, 'update'])->name('registros.update');
    Route::delete('/registros/{pedido}', [RegistroOrdenController::class, 'destroy'])->name('registros.destroy');
    Route::post('/registros/update-status', [RegistroOrdenController::class, 'updateStatus'])->name('registros.updateStatus');
    Route::get('/registros/{pedido}/entregas', [RegistroOrdenController::class, 'getEntregas'])->name('registros.entregas');
    Route::get('/api/registros-por-orden/{pedido}', [RegistroOrdenController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden');
    Route::get('/api/tabla-original/{numeroPedido}/procesos', [RegistroOrdenController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original.procesos');
    Route::post('/registros/{pedido}/edit-full', [RegistroOrdenController::class, 'editFullOrder'])->name('registros.editFull');
    Route::get('/orders/{numero_pedido}', [RegistroOrdenController::class, 'show'])->name('orders.show');

    // ========================================
    // RUTAS DE FACTURAS - Invoice Management
    // ========================================
    Route::get('/facturas/{numeroPedido}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/facturas/{numeroPedido}/preview', [App\Http\Controllers\InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/facturas/{numeroPedido}/download', [App\Http\Controllers\InvoiceController::class, 'download'])->name('invoices.download');

    Route::get('/api/bodega/{numero_pedido}/dias', [RegistroBodegaController::class, 'calcularDiasAPI'])->name('api.bodega.dias');
    Route::get('/api/ordenes/{id}/procesos', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'getProcesos'])->name('api.ordenes.procesos');
    Route::post('/api/ordenes/{numero_pedido}/novedades', [RegistroOrdenController::class, 'updateNovedades'])->name('api.ordenes.novedades');
    Route::post('/api/ordenes/{numero_pedido}/novedades/add', [RegistroOrdenController::class, 'addNovedad'])->name('api.ordenes.novedades.add');
    Route::post('/api/bodega/{pedido}/novedades', [RegistroBodegaController::class, 'updateNovedadesBodega'])->name('api.bodega.novedades');
    Route::post('/api/bodega/{pedido}/novedades/add', [RegistroBodegaController::class, 'addNovedadBodega'])->name('api.bodega.novedades.add');
    Route::put('/api/procesos/{id}/editar', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'editarProceso'])->name('api.procesos.editar');
    Route::delete('/api/procesos/{id}/eliminar', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'eliminarProceso'])->name('api.procesos.eliminar');
    Route::get('/api/tabla-original-bodega/{numeroPedido}/procesos', [RegistroBodegaController::class, 'getProcesosTablaOriginal'])->name('api.tabla-original-bodega.procesos');
    Route::get('/bodega', [RegistroBodegaController::class, 'index'])->name('bodega.index');
    Route::post('/bodega/search', [RegistroBodegaController::class, 'searchOrders'])->name('bodega.search');
    Route::get('/bodega/next-pedido', [RegistroBodegaController::class, 'getNextPedido'])->name('bodega.next-pedido');
    Route::get('/bodega/{pedido}', [RegistroBodegaController::class, 'show'])->name('bodega.show');
    Route::get('/bodega/{pedido}/prendas', [RegistroBodegaController::class, 'getPrendas'])->name('bodega.prendas');
    Route::get('/bodega/{pedido}/entregas', [RegistroBodegaController::class, 'getEntregas'])->name('bodega.entregas');
    Route::get('/api/registros-por-orden-bodega/{pedido}', [RegistroBodegaController::class, 'getRegistrosPorOrden'])->name('api.registros-por-orden-bodega');
    Route::post('/bodega/{pedido}/edit-full', [RegistroBodegaController::class, 'editFullOrder'])->name('bodega.editFull');
    Route::post('/bodega', [RegistroBodegaController::class, 'store'])->name('bodega.store');
    Route::post('/bodega/validate-pedido', [RegistroBodegaController::class, 'validatePedido'])->name('bodega.validatePedido');
    Route::post('/bodega/update-pedido', [RegistroBodegaController::class, 'updatePedido'])->name('bodega.updatePedido');
    Route::post('/bodega/update-descripcion-prendas', [RegistroBodegaController::class, 'updateDescripcionPrendas'])->name('bodega.updateDescripcionPrendas');
    Route::patch('/bodega/{pedido}', [RegistroBodegaController::class, 'update'])->name('bodega.update');
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/create-database', [ConfiguracionController::class, 'createDatabase'])->name('configuracion.createDatabase');
    Route::post('/configuracion/select-database', [ConfiguracionController::class, 'selectDatabase'])->name('configuracion.selectDatabase');
    Route::post('/configuracion/migrate-users', [ConfiguracionController::class, 'migrateUsers'])->name('configuracion.migrateUsers');
    Route::post('/configuracion/backup-database', [ConfiguracionController::class, 'backupDatabase'])->name('configuracion.backupDatabase');
    Route::get('/configuracion/download-backup', [ConfiguracionController::class, 'downloadBackup'])->name('configuracion.downloadBackup');
    Route::post('/configuracion/upload-google-drive', [ConfiguracionController::class, 'uploadToGoogleDrive'])->name('configuracion.uploadGoogleDrive');
    Route::get('/tableros', [TablerosController::class, 'index'])->name('tableros.index');
    Route::get('/tableros/fullscreen', [TablerosController::class, 'fullscreen'])->name('tableros.fullscreen');
    Route::get('/tableros/corte-fullscreen', [TablerosController::class, 'corteFullscreen'])->name('tableros.corte-fullscreen');
    Route::post('/tableros', [TablerosController::class, 'store'])->name('tableros.store');
    Route::patch('/tableros/{id}', [TablerosController::class, 'update'])->name('tableros.update');
    Route::delete('/tableros/{id}', [TablerosController::class, 'destroy'])->name('tableros.destroy');
    Route::post('/tableros/{id}/duplicate', [TablerosController::class, 'duplicate'])->name('tableros.duplicate');
    Route::post('/piso-corte', [TablerosController::class, 'storeCorte'])->name('piso-corte.store');
    Route::get('/get-tiempo-ciclo', [TablerosController::class, 'getTiempoCiclo'])->name('get-tiempo-ciclo');
    Route::post('/store-tela', [TablerosController::class, 'storeTela'])->name('store-tela');
    Route::get('/search-telas', [TablerosController::class, 'searchTelas'])->name('search-telas');
    Route::post('/store-maquina', [TablerosController::class, 'storeMaquina'])->name('store-maquina');
    Route::get('/search-maquinas', [TablerosController::class, 'searchMaquinas'])->name('search-maquinas');
    Route::get('/search-operarios', [TablerosController::class, 'searchOperarios'])->name('search-operarios');
    Route::post('/store-operario', [TablerosController::class, 'storeOperario'])->name('store-operario');
    Route::post('/find-or-create-operario', [TablerosController::class, 'findOrCreateOperario'])->name('find-or-create-operario');
    Route::post('/find-or-create-maquina', [TablerosController::class, 'findOrCreateMaquina'])->name('find-or-create-maquina');
    Route::post('/find-or-create-tela', [TablerosController::class, 'findOrCreateTela'])->name('find-or-create-tela');
    Route::post('/find-hora-id', [TablerosController::class, 'findHoraId'])->name('find-hora-id');
    Route::get('/tableros/dashboard-tables-data', [TablerosController::class, 'getDashboardTablesData'])->name('tableros.dashboard-tables-data');
    Route::get('/tableros/get-seguimiento-data', [TablerosController::class, 'getSeguimientoData'])->name('tableros.get-seguimiento-data');
    Route::get('/tableros/corte/dashboard', [TablerosController::class, 'getDashboardCorteData'])->name('tableros.corte.dashboard');
    Route::get('/tableros/unique-values', [TablerosController::class, 'getUniqueValues'])->name('tableros.unique-values');
    Route::get('/vistas', [VistasController::class, 'index'])->name('vistas.index');
    Route::get('/api/vistas/search', [VistasController::class, 'search'])->name('api.vistas.search');
    Route::post('/api/vistas/update-cell', [VistasController::class, 'updateCell'])->name('api.vistas.update-cell');
    Route::get('/vistas/control-calidad', [VistasController::class, 'controlCalidad'])->name('vistas.control-calidad');
    Route::get('/vistas/control-calidad-fullscreen', [VistasController::class, 'controlCalidadFullscreen'])->name('vistas.control-calidad-fullscreen');
    
    // Rutas de Balanceo
    Route::get('/balanceo', [BalanceoController::class, 'index'])->name('balanceo.index');
    Route::get('/balanceo/prenda/create', [BalanceoController::class, 'createPrenda'])->name('balanceo.prenda.create');
    Route::post('/balanceo/prenda', [BalanceoController::class, 'storePrenda'])->name('balanceo.prenda.store');
    Route::get('/balanceo/prenda/{id}/edit', [BalanceoController::class, 'editPrenda'])->name('balanceo.prenda.edit');
    Route::put('/balanceo/prenda/{id}', [BalanceoController::class, 'updatePrenda'])->name('balanceo.prenda.update');
    Route::delete('/balanceo/prenda/{id}', [BalanceoController::class, 'destroyPrenda'])->name('balanceo.prenda.destroy');
    Route::get('/balanceo/prenda/{id}', [BalanceoController::class, 'show'])->name('balanceo.show');
    Route::post('/balanceo/prenda/{prendaId}/balanceo', [BalanceoController::class, 'createBalanceo'])->name('balanceo.create');
    Route::patch('/balanceo/{id}', [BalanceoController::class, 'updateBalanceo'])->name('balanceo.update');
    Route::delete('/balanceo/{id}', [BalanceoController::class, 'destroyBalanceo'])->name('balanceo.destroy');
    Route::post('/balanceo/{balanceoId}/operacion', [BalanceoController::class, 'storeOperacion'])->name('balanceo.operacion.store');
    Route::patch('/balanceo/operacion/{id}', [BalanceoController::class, 'updateOperacion'])->name('balanceo.operacion.update');
    Route::delete('/balanceo/operacion/{id}', [BalanceoController::class, 'destroyOperacion'])->name('balanceo.operacion.destroy');
    Route::get('/balanceo/{id}/data', [BalanceoController::class, 'getBalanceoData'])->name('balanceo.data');
    Route::post('/balanceo/{id}/toggle-estado', [BalanceoController::class, 'toggleEstadoCompleto'])->name('balanceo.toggle-estado');
});

// ========================================
// RUTAS PARA COTIZACIONES - PRENDA (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->group(function () {
    // Cotizaciones tipo PRENDA
    Route::get('/cotizaciones-prenda/crear', [CotizacionPrendaController::class, 'create'])->name('cotizaciones-prenda.create');
    Route::post('/cotizaciones-prenda', [CotizacionPrendaController::class, 'store'])->name('cotizaciones-prenda.store');
    Route::get('/cotizaciones-prenda', [CotizacionPrendaController::class, 'lista'])->name('cotizaciones-prenda.lista');
    Route::get('/cotizaciones-prenda/{cotizacion}/editar', [CotizacionPrendaController::class, 'edit'])->name('cotizaciones-prenda.edit');
    Route::put('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'update'])->name('cotizaciones-prenda.update');
    Route::post('/cotizaciones-prenda/{cotizacion}/enviar', [CotizacionPrendaController::class, 'enviar'])->name('cotizaciones-prenda.enviar');
    Route::delete('/cotizaciones-prenda/{cotizacion}', [CotizacionPrendaController::class, 'destroy'])->name('cotizaciones-prenda.destroy');
    
    // Rutas para borrar imágenes de cotizaciones (Prenda)
    Route::post('/cotizaciones/{id}/borrar-imagen-prenda', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenPrenda'])->name('cotizaciones.borrar-imagen-prenda');
    Route::post('/cotizaciones/{id}/borrar-imagen-tela', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'borrarImagenTela'])->name('cotizaciones.borrar-imagen-tela');
});

// ========================================
// RUTAS PARA COTIZACIONES - BORDADO (DDD REFACTORIZADO)
// ========================================
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->group(function () {
    // Cotizaciones tipo BORDADO/LOGO
    Route::get('/cotizaciones-bordado/crear', [CotizacionBordadoController::class, 'create'])->name('cotizaciones-bordado.create');
    Route::post('/cotizaciones-bordado', [CotizacionBordadoController::class, 'store'])->name('cotizaciones-bordado.store');
    Route::put('/cotizaciones-bordado/{id}/borrador', [CotizacionBordadoController::class, 'updateBorrador'])->name('cotizaciones-bordado.update-borrador');
    Route::post('/cotizaciones-bordado/{id}/borrar-imagen', [CotizacionBordadoController::class, 'borrarImagen'])->name('cotizaciones-bordado.borrar-imagen');
    Route::get('/cotizaciones-bordado', [CotizacionBordadoController::class, 'lista'])->name('cotizaciones-bordado.lista');
    Route::get('/cotizaciones-bordado/{cotizacion}/editar', [CotizacionBordadoController::class, 'edit'])->name('cotizaciones-bordado.edit');
    Route::put('/cotizaciones-bordado/{cotizacion}', [CotizacionBordadoController::class, 'update'])->name('cotizaciones-bordado.update');
    Route::post('/cotizaciones-bordado/{cotizacion}/enviar', [CotizacionBordadoController::class, 'enviar'])->name('cotizaciones-bordado.enviar');
    Route::delete('/cotizaciones-bordado/{cotizacion}', [CotizacionBordadoController::class, 'destroy'])->name('cotizaciones-bordado.destroy');
    
    // RUTAS PARA TELAS DE PRENDAS EN COTIZACIÓN DE LOGO
    Route::post('/cotizaciones/{cotizacion_id}/logo/telas-prenda', [CotizacionBordadoController::class, 'guardarTelaPrenda'])->name('cotizaciones-bordado.guardar-tela-prenda');
    Route::get('/cotizaciones/{cotizacion_id}/logo/telas-prenda', [CotizacionBordadoController::class, 'obtenerTelasPrenda'])->name('cotizaciones-bordado.obtener-telas-prenda');
    Route::delete('/cotizaciones/{cotizacion_id}/logo/telas-prenda/{tela_id}', [CotizacionBordadoController::class, 'eliminarTelaPrenda'])->name('cotizaciones-bordado.eliminar-tela-prenda');
});

// ========================================
// RUTAS PARA PRUEBAS - TELAS DE PRENDAS (TEMPORAL)
// ========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/test-tela-prenda/crear', [TestTelasPrendaController::class, 'crear'])->name('test-telas.crear');
    Route::get('/test-tela-prenda/listar', [TestTelasPrendaController::class, 'listar'])->name('test-telas.listar');
    Route::get('/test-tela-prenda/limpiar', [TestTelasPrendaController::class, 'limpiar'])->name('test-telas.limpiar');
});

// ========================================
// NOTA: Funcionalidad migrada a CotizacionPrendaController y CotizacionBordadoController (DDD)
// Las rutas anteriores de CotizacionesViewController han sido eliminadas
// Usar: CotizacionPrendaController::lista() o CotizacionBordadoController::lista()

// ========================================
// RUTAS PARA APROBADOR DE COTIZACIONES
// ========================================
Route::middleware(['auth'])->group(function () {
    // Solo usuarios con rol aprobador_cotizaciones pueden ver cotizaciones pendientes
    Route::get('/cotizaciones/pendientes', function () {
        // Verificar que el usuario tenga el rol aprobador_cotizaciones
        if (!auth()->user()->hasRole('aprobador_cotizaciones')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
        
        // Obtener cotizaciones pendientes de aprobación (estado APROBADA_CONTADOR)
        $cotizaciones = \App\Models\Cotizacion::where('estado', 'APROBADA_CONTADOR')
            ->with(['aprobaciones.usuario'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Obtener total de aprobadores
        $rolAprobador = \App\Models\Role::where('name', 'aprobador_cotizaciones')->first();
        $totalAprobadores = $rolAprobador 
            ? \App\Models\User::whereJsonContains('roles_ids', $rolAprobador->id)->count()
            : 0;
        
        return view('cotizaciones.pendientes', compact('cotizaciones', 'totalAprobadores'));
    })->name('cotizaciones.pendientes');

    // Obtener datos de cotización para modal (AJAX)
    Route::get('/cotizaciones/{cotizacion}/datos', [CotizacionesViewController::class, 'getDatosForModal'])
        ->name('cotizaciones.obtener-datos');

    // Obtener costos de cotización (AJAX)
    Route::get('/cotizaciones/{cotizacion}/costos', [App\Http\Controllers\ContadorController::class, 'obtenerCostos'])
        ->name('cotizaciones.obtener-costos');

    // Obtener contador de cotizaciones pendientes para aprobador (AJAX)
    Route::get('/pendientes-count', [CotizacionesViewController::class, 'cotizacionesPendientesAprobadorCount'])
        ->name('cotizaciones.pendientes-count');

    // Acceso a modal de ver cotización desde aprobador de cotizaciones - RUTA ACCESIBLE PARA APROBADOR, CONTADOR Y ADMIN
    Route::get('/contador/cotizacion/{id}', [App\Http\Controllers\ContadorController::class, 'getCotizacionDetail'])
        ->middleware('auth')
        ->name('aprobador.cotizacion.detail');

    // Acceso a costos de cotización desde aprobador de cotizaciones - RUTA ACCESIBLE PARA APROBADOR, CONTADOR Y ADMIN
    Route::get('/contador/cotizacion/{cotizacion}/costos', [App\Http\Controllers\ContadorController::class, 'obtenerCostos'])
        ->name('aprobador.cotizacion.costos');
});

// ========================================
// RUTA DE PDF COMPARTIDA (Accesible para asesores, contador, visualizador_cotizaciones_logo y admin)
// ========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
});

// ========================================
// RUTAS PARA CONTADOR (MÓDULO INDEPENDIENTE)
// ========================================
// Admin puede acceder a contador además del rol contador
Route::middleware(['auth', 'role:contador,admin'])->prefix('contador')->name('contador.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ContadorController::class, 'index'])->name('index');
    Route::get('/todas', [App\Http\Controllers\ContadorController::class, 'todas'])->name('todas');
    Route::get('/por-revisar', [App\Http\Controllers\ContadorController::class, 'porRevisar'])->name('por-revisar');
    Route::get('/aprobadas', [App\Http\Controllers\ContadorController::class, 'aprobadas'])->name('aprobadas');
    Route::delete('/cotizacion/{id}', [App\Http\Controllers\ContadorController::class, 'deleteCotizacion'])->name('cotizacion-delete');
    // NOTA: Funcionalidad migrada a Handlers DDD
    // Route::get('/por-corregir', [App\Http\Controllers\CotizacionesViewController::class, 'porCorregir'])->name('por-corregir');
    
    // Rutas para costos de prendas
    Route::post('/costos/guardar', [App\Http\Controllers\CostoPrendaController::class, 'guardar'])->name('costos.guardar');
    Route::get('/costos/obtener/{cotizacion_id}', [App\Http\Controllers\CostoPrendaController::class, 'obtener'])->name('costos.obtener');
    
    // Rutas para notas de tallas
    Route::post('/prenda/{prendaId}/notas-tallas', [App\Http\Controllers\ContadorController::class, 'guardarNotasTallas'])->name('prenda.guardar-notas-tallas');
    
    // Ruta para texto personalizado de tallas (módulo contador)
    Route::post('/prenda/{prendaId}/texto-personalizado-tallas', [App\Http\Controllers\ContadorController::class, 'guardarTextoPersonalizadoTallas'])->name('prenda.guardar-texto-personalizado-tallas');
    
    // Rutas para PDF
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    
    // Ruta para cambiar estado de cotización
    Route::patch('/cotizacion/{id}/estado', [App\Http\Controllers\ContadorController::class, 'cambiarEstado'])->name('cotizacion.cambiar-estado');
    
    // Ruta para obtener costos de prendas
    Route::get('/cotizacion/{id}/costos', [App\Http\Controllers\ContadorController::class, 'obtenerCostos'])->name('cotizacion.costos');
    
    // Ruta para guardar tallas costos
    Route::post('/tallas-costos', [App\Http\Controllers\ContadorController::class, 'guardarTallasCostos'])->name('tallas-costos.guardar');
    
    // Ruta para obtener contador de cotizaciones pendientes
    Route::get('/cotizaciones-pendientes-count', [App\Http\Controllers\ContadorController::class, 'cotizacionesPendientesCount'])->name('cotizaciones-pendientes-count');
    
    // Ruta para perfil del contador
    Route::get('/perfil', [App\Http\Controllers\ContadorController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [App\Http\Controllers\ContadorController::class, 'updateProfile'])->name('profile.update');
});

// ========================================
// RUTAS PARA OPERARIOS (CORTADOR Y COSTURERO)
// ========================================
Route::middleware(['auth', 'operario-access'])->prefix('operario')->name('operario.')->group(function () {
    Route::get('/dashboard', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'dashboard'])->name('dashboard');
    Route::get('/mis-pedidos', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'misPedidos'])->name('mis-pedidos');
    Route::get('/pedido/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'verPedido'])->name('ver-pedido');
    Route::get('/api/pedidos', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerPedidosJson'])->name('api.pedidos');
    Route::get('/api/pedido/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerDatosRecibosOperario'])->name('api.pedido');
    Route::get('/api/novedades/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'obtenerNovedades'])->name('api.novedades');
    Route::post('/buscar', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'buscarPedido'])->name('buscar');
    Route::post('/reportar-pendiente', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'reportarPendiente'])->name('reportar-pendiente');
    Route::post('/api/completar-proceso/{numeroPedido}', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'completarProceso'])->name('api.completar-proceso');
    Route::get('/debug', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'debug'])->name('debug');
    Route::get('/debug/prendas-recibos', [App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'debugPrendasRecibos'])->name('debug.prendas-recibos');
});

// ========================================
// API PARA SISTEMA DE TIEMPO REAL (FUERA DEL GRUPO DE ASESORES)
// ========================================
Route::middleware(['auth'])->prefix('asesores')->group(function () {
    // API para Sistema de Tiempo Real (acceso para rol despacho) - SIN MIDDLEWARE DE ROLES
    Route::get('/realtime/pedidos', function () {
        // Debug: Ver información del usuario y roles
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'No authenticated user'], 403);
        }
        
        // Debug: Mostrar todos los roles del usuario
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Verificar si el usuario tiene permisos (verificación manual)
        $hasPermission = $user->hasRole('asesor') || 
                       $user->hasRole('admin') || 
                       $user->hasRole('supervisor_pedidos') || 
                       $user->hasRole('despacho') ||
                       $user->hasRole('insumos');
        
        // Log para debug
        \Log::info('[REALTIME-API] Verificación de permisos', [
            'user_id' => $user->id,
            'user_roles' => $userRoles,
            'has_permission' => $hasPermission
        ]);
        
        if (!$hasPermission) {
            return response()->json([
                'error' => 'Unauthorized',
                'debug' => [
                    'user_id' => $user->id,
                    'user_roles' => $userRoles,
                    'has_permission' => $hasPermission
                ]
            ], 403);
        }
        
        // Obtener pedidos según el rol del usuario
        $query = \App\Models\PedidoProduccion::select('id', 'numero_pedido', 'cliente', 'estado', 'area', 'novedades', 'forma_de_pago', 'created_at', 'fecha_estimada_de_entrega');
        
        // Si es asesor, solo mostrar sus pedidos
        if ($user->hasRole('asesor')) {
            $query->where('asesor_id', $user->id);
        }
        
        $pedidos = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $pedidos->toArray(),
            'debug' => [
                'user_id' => $user->id,
                'user_roles' => $userRoles,
                'pedidos_count' => $pedidos->count()
            ]
        ]);
    })->name('realtime.pedidos.listar');
});

// ========================================
// PDF - VISUALIZADOR LOGO
// ========================================
// Estas rutas NO deben vivir bajo el prefijo /asesores porque el usuario del visualizador
// no necesariamente tiene rol asesor y se bloquea con 403.
Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin,contador,aprobador_cotizaciones,asesor'])->group(function () {
    Route::get('/cotizacion/{id}/pdf/logo', [PDFLogoController::class, 'generate'])->name('visualizador.cotizacion.pdf.logo');
});

// ========================================
// RUTAS PARA ASESORES (MÓDULO INDEPENDIENTE)
// ========================================
// Admin y supervisor_pedidos pueden acceder a asesores además del rol asesor
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos,despacho'])->prefix('asesores')->name('asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'getDashboardData'])->name('dashboard-data');
    
    // Perfil
    Route::get('/perfil', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'profile'])->name('profile')->middleware('auth');
    Route::post('/perfil/update', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'updateProfile'])->name('profile.update');
    
    // Pedidos - VISTAS (AsesoresController)
    Route::get('/pedidos', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'index'])->name('pedidos.index');
    Route::get('/cotizaciones/create', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'create'])->name('pedidos.create');
    Route::get('/pedidos/next-pedido', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'getNextPedido'])->name('next-pedido');
    Route::get('/pedidos/{pedido}', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/edit', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'update'])->name('pedidos.update');
    Route::delete('/pedidos/{pedido}', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'destroy'])->name('pedidos.destroy');
    
    // Pedidos - APIs ahora usan DDD (usar /api/pedidos en lugar de /asesores/pedidos)
    // Las rutas POST, PATCH, DELETE se han migrado a /api/pedidos en routes/api.php
    Route::get('/pedidos/{id}/factura-datos', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'obtenerDatosFactura'])
        ->where('id', '[0-9]+')
        ->name('pedidos.factura-datos');
    Route::get('/prendas-pedido/{prendaPedidoId}/fotos', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'obtenerFotosPrendaPedido'])->where('prendaPedidoId', '[0-9]+')->name('prendas-pedido.fotos');
    
    // API para listado de pedidos en tiempo real
    Route::get('/pedidos/api/listar', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'apiListar'])->name('pedidos.api.listar');
    
    // Anular pedido
    Route::post('/pedidos/{id}/anular', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'anularPedido'])->where('id', '[0-9]+')->name('pedidos.anular');
    
    // Confirmar corrección de pedido
    Route::post('/pedidos/{id}/confirmar-correccion', [App\Infrastructure\Http\Controllers\Asesores\AsesoresController::class, 'confirmarCorreccion'])->where('id', '[0-9]+')->name('pedidos.confirmar-correccion');

    // ==================== OBSERVACIONES DESPACHO (JSON) ====================
    Route::post('/pedidos/observaciones-despacho/resumen', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'resumen'])
        ->name('pedidos.observaciones-despacho.resumen');

    Route::get('/pedidos/{pedido}/observaciones-despacho', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'obtener'])
        ->where('pedido', '[0-9]+')
        ->name('pedidos.observaciones-despacho.obtener');

    Route::post('/pedidos/{pedido}/observaciones-despacho/guardar', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'guardar'])
        ->where('pedido', '[0-9]+')
        ->name('pedidos.observaciones-despacho.guardar');

    Route::post('/pedidos/{pedido}/observaciones-despacho/marcar-leidas', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'marcarLeidas'])
        ->where('pedido', '[0-9]+')
        ->name('pedidos.observaciones-despacho.marcar-leidas');

    Route::post('/pedidos/{pedido}/observaciones-despacho/{observacionId}/actualizar', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'actualizar'])
        ->where('pedido', '[0-9]+')
        ->where('observacionId', '[A-Za-z0-9\-]+')
        ->name('pedidos.observaciones-despacho.actualizar');

    Route::post('/pedidos/{pedido}/observaciones-despacho/{observacionId}/eliminar', [App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController::class, 'eliminar'])
        ->where('pedido', '[0-9]+')
        ->where('observacionId', '[A-Za-z0-9\-]+')
        ->name('pedidos.observaciones-despacho.eliminar');
    
    // ========================================
    // SISTEMA DE ÓRDENES CON BORRADORES
    // ========================================
    
    // BORRADORES - Gestión de borradores
    // Route::get('/borradores', [App\Http\Controllers\Api_temp\V1\OrdenController::class, 'borradores'])->name('borradores.index');
    
    // ÓRDENES - CRUD principal
    //  Controlador comentado: OrdenController no existe - usar RegistroOrdenController en su lugar
    // Route::get('/ordenes/create', [App\Http\Controllers\OrdenController::class, 'create'])->name('ordenes.create');
    // Route::post('/ordenes/guardar', [App\Http\Controllers\OrdenController::class, 'guardarBorrador'])->name('ordenes.store.draft');
    // Route::get('/ordenes/{id}/edit', [App\Http\Controllers\OrdenController::class, 'edit'])->name('ordenes.edit');
    // Route::patch('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'update'])->name('ordenes.update');
    // Route::post('/ordenes/{id}/confirmar', [App\Http\Controllers\OrdenController::class, 'confirmar'])->name('ordenes.confirm');
    // Route::delete('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'destroy'])->name('ordenes.destroy');
    // Route::get('/ordenes', [App\Http\Controllers\OrdenController::class, 'index'])->name('ordenes.index');
    // Route::get('/ordenes/{id}', [App\Http\Controllers\OrdenController::class, 'show'])->name('ordenes.show');
    
    // Estadísticas de órdenes
    // Route::get('/ordenes/stats', [App\Http\Controllers\OrdenController::class, 'stats'])->name('ordenes.stats');
    

    // ========================================
    // COTIZACIONES - Gestión de cotizaciones y borradores (DDD Refactorizado)
    // ========================================
    // Vista HTML de cotizaciones (usando Infrastructure Controller con Handlers DDD)
    Route::get('/cotizaciones', [App\Infrastructure\Http\Controllers\Asesores\CotizacionesViewController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/filtros/valores', [App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController::class, 'valores'])->name('cotizaciones.filtros.valores');
    
    // API endpoints para cotizaciones
    Route::post('/cotizaciones', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::put('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'update'])->name('cotizaciones.update');
    
    // ========================================
    // RUTAS PDF ESPECÍFICAS - DEBE ESTAR ANTES DE LA RUTA GENÉRICA
    // ========================================
    Route::get('/cotizacion/{id}/pdf/prenda', [PDFPrendaController::class, 'generate'])->name('cotizacion.pdf.prenda');
    Route::get('/cotizacion/{id}/pdf/combinada', [PDFCotizacionCombiadaController::class, 'generate'])->name('cotizacion.pdf.combinada');
    Route::get('/cotizacion/{id}/pdf/logo', [PDFLogoController::class, 'generate'])->name('cotizacion.pdf.logo');
    
    // RUTA GENÉRICA - Debe ser ÚLTIMA para no shadowers las rutas específicas
    Route::get('/cotizacion/{id}/pdf', [App\Http\Controllers\PDFCotizacionController::class, 'generarPDF'])->name('cotizacion.pdf');
    
    // Rutas para eliminar imágenes de borradores (ANTES de rutas dinámicas)
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarPrenda'])->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarTela'])->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController::class, 'borrarLogo'])->name('cotizaciones.imagen.borrar-logo');
    
    Route::get('/cotizaciones/{id}/editar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'getForEdit'])->name('cotizaciones.get-for-edit');
    Route::get('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'show'])->name('cotizaciones.api');
    Route::post('/cotizaciones/{id}/imagenes', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');
    
    // Rutas antiguas (compatibilidad con frontend) - Aliases al nuevo controller
    Route::post('/cotizaciones/guardar', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'store'])->name('cotizaciones.guardar');
    Route::get('/cotizaciones/{id}/editar-borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'editBorrador'])->name('cotizaciones.edit-borrador');
    Route::delete('/cotizaciones/{id}/borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroyBorrador'])->name('cotizaciones.destroy-borrador');
    Route::post('/cotizaciones/{id}/anular', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'anularCotizacion'])->name('cotizaciones.anular');
    Route::delete('/cotizaciones/{id}', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
    
    // ========================================
    // PEDIDOS DE PRODUCCIÓN - Gestión de pedidos
    // ========================================
    // MASTER ROUTE: Creación de pedidos centralizada en CrearPedidoEditableController
    // Rutas antiguas han sido ELIMINADAS (retornan 404)
    // Ver: app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController
    // ========================================
    
    // Rutas API CQRS - Pedidos de Producción (retorna JSON)
    Route::get('/pedidos-produccion', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'index'])->name('pedidos-produccion.index');
    Route::get('/pedidos-produccion/{id}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'show'])->name('pedidos-produccion.show');
    
    // Obtener datos de cotización para crear pedido
    Route::get('/pedidos-produccion/obtener-datos-cotizacion/{cotizacionId}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionViewController::class, 'obtenerDatosCotizacion'])->name('pedidos-produccion.obtener-datos-cotizacion');
    
    // Obtener prenda COMPLETA desde cotización (con procesos, telas, fotos, etc.)
    Route::get('/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionViewController::class, 'obtenerPrendaCompleta'])->name('pedidos-produccion.obtener-prenda-completa')->where(['cotizacionId' => '[0-9]+', 'prendaId' => '[0-9]+']);
    
    // Obtener datos de una prenda específica con procesos para edición modal
    Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('pedidos.prenda-datos');
    
    // Obtener datos completos de un pedido para edición (GET)
    Route::get('/api/pedidos/{id}', [\App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerDatosEdicion'])->where('id', '[0-9]+')->name('api.pedidos.obtener-datos');
    
    Route::post('/api/pedidos', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'store'])->name('api.pedidos.store');
    Route::put('/api/pedidos/{id}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'update'])->name('api.pedidos.update');
    Route::put('/api/pedidos/{id}/estado', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'cambiarEstado'])->name('api.pedidos.cambiar-estado');
    Route::post('/api/pedidos/{id}/prendas', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'agregarPrenda'])->name('api.pedidos.agregar-prenda');
    Route::delete('/api/pedidos/{id}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'destroy'])->name('api.pedidos.destroy');
    Route::get('/api/pedidos/filtro/estado', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'filtrarPorEstado'])->name('api.pedidos.filtrar-estado');
    Route::get('/api/pedidos/buscar/{numero}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'buscarPorNumero'])->name('api.pedidos.buscar');
    Route::get('/api/pedidos/{id}/prendas', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerPrendas'])->name('api.pedidos.obtener-prendas');
    
    // ========================================
    // CLIENTES - Gestión de clientes
    // ========================================
    Route::get('/clientes', [App\Http\Controllers\Asesores\ClientesController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [App\Http\Controllers\Asesores\ClientesController::class, 'store'])->name('clientes.store');
    Route::patch('/clientes/{id}', [App\Http\Controllers\Asesores\ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [App\Http\Controllers\Asesores\ClientesController::class, 'destroy'])->name('clientes.destroy');
    
    // ========================================
    // REPORTES - Gestión de reportes
    // ========================================
    Route::get('/reportes', [App\Http\Controllers\Asesores\ReportesController::class, 'index'])->name('reportes.index');
    Route::post('/reportes', [App\Http\Controllers\Asesores\ReportesController::class, 'store'])->name('reportes.store');
    Route::patch('/reportes/{id}', [App\Http\Controllers\Asesores\ReportesController::class, 'update'])->name('reportes.update');
    Route::delete('/reportes/{id}', [App\Http\Controllers\Asesores\ReportesController::class, 'destroy'])->name('reportes.destroy');
    
    // Agregar Prendas (Sistema de Variantes)
    Route::get('/prendas/agregar', function () {
        return view('asesores.prendas.agregar-prendas');
    })->name('prendas.agregar');

    // ========================================
    // COTIZACIONES - Rutas protegidas (dentro del grupo asesores)
    // ========================================
    Route::delete('/cotizaciones/{id}/borrador', [App\Infrastructure\Http\Controllers\CotizacionController::class, 'destroyBorrador'])->name('cotizaciones.destroy-borrador');

    // ========================================
    // COTIZACIONES DE PRENDA - Solo Asesor
    // ========================================
    Route::get('/cotizaciones/prenda/crear', [CotizacionPrendaController::class, 'create'])->name('cotizaciones-prenda.create');
    Route::post('/cotizaciones/prenda', [CotizacionPrendaController::class, 'store'])->name('cotizaciones-prenda.store');
    Route::get('/cotizaciones/prenda/lista', [CotizacionPrendaController::class, 'lista'])->name('cotizaciones-prenda.lista');
    Route::get('/cotizaciones/prenda/{cotizacion}/editar', [CotizacionPrendaController::class, 'edit'])->name('cotizaciones-prenda.edit');
    Route::put('/cotizaciones/prenda/{cotizacion}', [CotizacionPrendaController::class, 'update'])->name('cotizaciones-prenda.update');
    Route::post('/cotizaciones/prenda/{cotizacion}/enviar', [CotizacionPrendaController::class, 'enviar'])->name('cotizaciones-prenda.enviar');
    Route::delete('/cotizaciones/prenda/{cotizacion}', [CotizacionPrendaController::class, 'destroy'])->name('cotizaciones-prenda.destroy');

    // ========================================
    // COTIZACIONES DE BORDADO - Solo Asesor
    // ========================================
    Route::get('/cotizaciones/bordado/crear', [CotizacionBordadoController::class, 'create'])->name('cotizaciones-bordado.create');
    Route::post('/cotizaciones/bordado', [CotizacionBordadoController::class, 'store'])->name('cotizaciones-bordado.store');
    Route::get('/cotizaciones/bordado/lista', [CotizacionBordadoController::class, 'lista'])->name('cotizaciones-bordado.lista');
    Route::get('/cotizaciones/bordado/{cotizacion}/editar', [CotizacionBordadoController::class, 'edit'])->name('cotizaciones-bordado.edit');
    Route::put('/cotizaciones/bordado/{cotizacion}', [CotizacionBordadoController::class, 'update'])->name('cotizaciones-bordado.update');
    Route::post('/cotizaciones/bordado/{cotizacion}/enviar', [CotizacionBordadoController::class, 'enviar'])->name('cotizaciones-bordado.enviar');
    Route::delete('/cotizaciones/bordado/{cotizacion}', [CotizacionBordadoController::class, 'destroy'])->name('cotizaciones-bordado.destroy');
    
    // Actualizar prenda completa (con novedades) en un pedido existente
    Route::post('/pedidos/{id}/actualizar-prenda', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');

    // Eliminar imagen de prenda, tela o proceso
    Route::delete('/pedidos/{pedidoId}/imagen/{tipo}/{id}', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'eliminarImagen'])->where(['pedidoId' => '[0-9]+', 'id' => '[0-9]+'])->name('pedidos.eliminar-imagen');

    // ========================================
    // DATOS DE CATÁLOGOS (tipos de broche, manga, telas, colores, etc)
    // ========================================
    Route::get('/api/tipos-broche-boton', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTiposBrocheBoton'])->name('api.tipos-broche-boton');
    Route::get('/api/tipos-manga', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTiposManga'])->name('api.tipos-manga');
    Route::post('/api/tipos-manga', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerTipoManga'])->name('api.tipos-manga.create');
    Route::get('/api/telas', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerTelas'])->name('api.telas');
    Route::post('/api/telas', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerTela'])->name('api.telas.create');
    Route::get('/api/colores', [App\Http\Controllers\Api_temp\PedidoController::class, 'obtenerColores'])->name('api.colores');
    Route::post('/api/colores', [App\Http\Controllers\Api_temp\PedidoController::class, 'crearObtenerColor'])->name('api.colores.create');
    Route::get('/api/prendas/autocomplete', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerPrendasAutocomplete'])->name('api.prendas.autocomplete');
});

// ========================================
// API ROUTES - CATÁLOGOS - Tallas, variantes, colores/telas de prendas
// ========================================
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->prefix('api')->name('api.')->group(function () {
    Route::get('/tallas-disponibles', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerTallasDisponibles'])->name('tallas.disponibles');
    Route::get('/prenda-pedido/{prendaId}/tallas', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerTallasPrenda'])->name('prenda.tallas');
    Route::get('/prenda-pedido/{prendaId}/variantes', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerVariantesPrenda'])->name('prenda.variantes');
    Route::get('/prenda-pedido/{prendaId}/colores-telas', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerColoresTelasPrenda'])->name('prenda.colores-telas');

    // ================================================
    // EDICIÓN SEGURA DE PRENDAS (Separado de creación)
    // ================================================
    Route::prefix('prendas-pedido')->group(function () {
        // Editar prenda completa (PATCH)
        Route::patch('/{id}/editar', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editPrenda'])->name('editar');
        
        // Editar solo campos simples de prenda
        Route::patch('/{id}/editar/campos', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editPrendaFields'])->name('editar-campos');
        
        // Editar solo tallas (MERGE)
        Route::patch('/{id}/editar/tallas', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editTallas'])->name('editar-tallas');
        
        //  NUEVA RUTA: Actualizar un proceso específico de la prenda
        //  FIX: Acepta POST también (con _method=PATCH en FormData para mejor compatibilidad)
        Route::match(['patch', 'post'], '/{prendaId}/procesos/{procesoId}', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'actualizarProcesoEspecifico'])->name('proceso-actualizar');
        
        // Obtener estado actual (para auditoría)
        Route::get('/{id}/estado', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'getPrendaState'])->name('estado');
        
        // Editar variante específica
        Route::patch('/{prendaId}/variantes/{varianteId}/editar', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editVariante'])->name('variante-editar');
        
        // Editar solo campos simples de variante
        Route::patch('/{prendaId}/variantes/{varianteId}/editar/campos', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editVarianteFields'])->name('variante-editar-campos');
        
        // Editar solo colores de variante (MERGE)
        Route::patch('/{prendaId}/variantes/{varianteId}/colores', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editVarianteColores'])->name('variante-colores');
        
        // Editar solo telas de variante (MERGE)
        Route::patch('/{prendaId}/variantes/{varianteId}/telas', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'editVarianteTelas'])->name('variante-telas');
        
        // Obtener estado de variante (para auditoría)
        Route::get('/{prendaId}/variantes/{varianteId}/estado', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'getVarianteState'])->name('variante-estado');
    });
});

// ========================================
// RUTAS PARA LOGO COTIZACIÓN TÉCNICAS (DDD) - Fuera del grupo de asesores
// ========================================
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->prefix('api/logo-cotizacion-tecnicas')->name('api.logo-cotizacion-tecnicas.')->group(function () {
    Route::get('tipos-disponibles', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'tiposDisponibles'])->name('tipos');
    Route::post('agregar', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'agregarTecnica'])->name('agregar');
    Route::get('cotizacion/{logoCotizacionId}', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'obtenerTecnicas'])->name('obtener');
    Route::delete('{tecnicaId}', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'eliminarTecnica'])->name('eliminar');
    Route::patch('{tecnicaId}/observaciones', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'actualizarObservaciones'])->name('actualizar-observaciones');
    Route::get('prendas', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'obtenerPrendas'])->name('prendas');
    Route::post('prendas', [App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController::class, 'guardarPrenda'])->name('guardar-prenda');
});

// ========================================
// RUTAS PARA SUPERVISOR DE ASESORES
// ========================================
Route::middleware(['auth', 'role:supervisor_asesores,admin'])->prefix('supervisor-asesores')->name('supervisor-asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\SupervisorAsesoresController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-stats', [App\Http\Controllers\SupervisorAsesoresController::class, 'dashboardStats'])->name('dashboard-stats');
    
    // Cotizaciones
    Route::get('/cotizaciones', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesIndex'])->name('cotizaciones.index');
    Route::get('/cotizaciones/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesData'])->name('cotizaciones.data');
    Route::get('/cotizaciones/filtros/valores', [App\Http\Controllers\SupervisorAsesoresController::class, 'cotizacionesFiltrosValores'])->name('cotizaciones.filtros.valores');
    
    // Pedidos
    Route::get('/pedidos', [App\Http\Controllers\SupervisorAsesoresController::class, 'pedidosIndex'])->name('pedidos.index');
    Route::get('/pedidos/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'pedidosData'])->name('pedidos.data');
    Route::post('/pedidos/{id}/confirmar-correccion', [App\Http\Controllers\SupervisorAsesoresController::class, 'confirmarCorreccion'])->name('pedidos.confirmar-correccion');
    
    // Asesores
    Route::get('/asesores', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresIndex'])->name('asesores.index');
    Route::get('/asesores/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresData'])->name('asesores.data');
    Route::get('/asesores/{id}', [App\Http\Controllers\SupervisorAsesoresController::class, 'asesoresShow'])->name('asesores.show');
    
    // Reportes
    Route::get('/reportes', [App\Http\Controllers\SupervisorAsesoresController::class, 'reportesIndex'])->name('reportes.index');
    Route::get('/reportes/data', [App\Http\Controllers\SupervisorAsesoresController::class, 'reportesData'])->name('reportes.data');
    
    // Perfil
    Route::get('/perfil', [App\Http\Controllers\SupervisorAsesoresController::class, 'profileIndex'])->name('profile.index');
    Route::get('/perfil/stats', [App\Http\Controllers\SupervisorAsesoresController::class, 'profileStats'])->name('profile.stats');
    Route::post('/perfil/password-update', [App\Http\Controllers\SupervisorAsesoresController::class, 'profilePasswordUpdate'])->name('profile.password-update');
});

// ========================================
// RUTAS PARA VISUALIZADOR DE COTIZACIONES LOGO
// ========================================
Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin,diseñador-logos,bordador'])->prefix('visualizador-logo')->name('visualizador-logo.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\VisualizadorLogoController::class, 'dashboard'])->name('dashboard');
    
    // Cotizaciones
    Route::get('/cotizaciones', [App\Http\Controllers\VisualizadorLogoController::class, 'getCotizaciones'])->name('cotizaciones');
    Route::get('/cotizaciones/{id}', [App\Http\Controllers\VisualizadorLogoController::class, 'verCotizacion'])->name('cotizaciones.ver');
    
    // Pedidos Logo
    Route::get('/pedidos-logo', [App\Http\Controllers\VisualizadorLogoController::class, 'pedidosLogo'])->name('pedidos-logo');
    Route::get('/pedidos-logo/data', [App\Infrastructure\Http\Controllers\VisualizadorLogo\PedidosLogoController::class, 'data'])->name('pedidos-logo.data');
    Route::post('/pedidos-logo/area-novedad', [App\Infrastructure\Http\Controllers\VisualizadorLogo\PedidosLogoController::class, 'guardarAreaNovedad'])->name('pedidos-logo.area-novedad');

    // Diseños adjuntos del recibo (solo diseñador-logos/admin)
    Route::post('/pedidos-logo/disenos', [App\Infrastructure\Http\Controllers\VisualizadorLogo\DisenosLogoPedidoController::class, 'store'])
        ->middleware('role:admin,diseñador-logos')
        ->name('pedidos-logo.disenos.store');

    Route::get('/pedidos-logo/disenos', [App\Infrastructure\Http\Controllers\VisualizadorLogo\DisenosLogoPedidoController::class, 'index'])
        ->middleware('role:admin,diseñador-logos,bordador,visualizador_cotizaciones_logo')
        ->name('pedidos-logo.disenos.index');

    Route::delete('/pedidos-logo/disenos/{diseno}', [App\Infrastructure\Http\Controllers\VisualizadorLogo\DisenosLogoPedidoController::class, 'destroy'])
        ->middleware('role:admin,diseñador-logos')
        ->name('pedidos-logo.disenos.destroy');
    
    // Estadísticas
    Route::get('/estadisticas', [App\Http\Controllers\VisualizadorLogoController::class, 'getEstadisticas'])->name('estadisticas');
    
    // PDF de Logo - Solo puede ver PDFs de logo
    Route::get('/cotizaciones/{id}/pdf-logo', function($id) {
        return redirect()->route('pdf.cotizacion', ['cotizacionId' => $id, 'tipo' => 'logo']);
    })->name('cotizaciones.pdf-logo');
});

// ========== DEBUG ROUTES PARA OPTIMIZACIÓN DE /registros ==========
// Solo accesible en desarrollo o para admins
Route::middleware(['auth', 'role:admin'])->prefix('debug')->name('debug.')->group(function () {
    Route::get('/registros/performance', [DebugRegistrosController::class, 'debugPerformance'])->name('registros-performance');
    Route::get('/registros/queries', [DebugRegistrosController::class, 'listAllQueries'])->name('registros-queries');
    Route::get('/registros/table-analysis', [DebugRegistrosController::class, 'analyzeTable'])->name('registros-table-analysis');
    Route::get('/registros/suggest-indices', [DebugRegistrosController::class, 'suggestIndices'])->name('registros-suggest-indices');
});

// ========================================
// RUTAS GENERALES - Inventario de Telas (Compartido)
// ========================================
Route::middleware(['auth'])->prefix('inventario-telas')->name('inventario-telas.')->group(function () {
    Route::get('/', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'index'])->name('index');
    Route::post('/store', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'store'])->name('store');
    Route::post('/ajustar-stock', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'ajustarStock'])->name('ajustar-stock');
    Route::delete('/{id}', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'destroy'])->name('destroy');
    Route::get('/historial', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'historial'])->name('historial');
});

// API Routes para Prendas (Reconocimiento)
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/tipos-prenda', [App\Http\Controllers\Api_temp\PrendaController::class, 'tiposPrenda'])->name('tipos-prenda');
    Route::post('/prenda/reconocer', [App\Http\Controllers\Api_temp\PrendaController::class, 'reconocer'])->name('prenda.reconocer');
});

// Rutas para variaciones de prendas
Route::middleware('auth')->get('/prenda-variaciones/{tipoPrendaId}', function($tipoPrendaId) {
    // Por ahora retornar vacío ya que el sistema maneja las variaciones automáticamente
    // El frontend espera null cuando no hay variaciones predefinidas
    return response()->json(null);
})->name('prenda-variaciones');

// Rutas de Insumos
Route::middleware(['auth', 'insumos-access'])->prefix('insumos')->name('insumos.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Insumos\InsumosController::class, 'dashboard'])->name('dashboard');
    Route::get('/materiales', [\App\Http\Controllers\Insumos\InsumosController::class, 'materiales'])->name('materiales.index');
    Route::post('/materiales/{pedido}/guardar', [\App\Http\Controllers\Insumos\InsumosController::class, 'guardarMateriales'])->name('materiales.guardar');
    Route::post('/materiales/{pedido}/eliminar', [\App\Http\Controllers\Insumos\InsumosController::class, 'eliminarMaterial'])->name('materiales.eliminar');
    Route::post('/materiales/{numeroPedido}/guardar-ancho-metraje', [\App\Http\Controllers\Insumos\InsumosController::class, 'guardarAnchoMetraje'])->name('materiales.guardar-ancho-metraje');
    Route::get('/materiales/{numeroPedido}/obtener-ancho-metraje', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerAnchoMetraje'])->name('materiales.obtener-ancho-metraje');
    Route::get('/materiales/{numeroPedido}/obtener-prendas', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerPrendas'])->name('materiales.obtener-prendas');
    Route::get('/materiales/{numeroPedido}/obtener-ancho-metraje-prenda/{prendaId}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerAnchoMetrajePrenda'])->name('materiales.obtener-ancho-metraje-prenda');
    Route::post('/materiales/{numeroPedido}/guardar-ancho-metraje-prenda', [\App\Http\Controllers\Insumos\InsumosController::class, 'guardarAnchoMetrajePrenda'])->name('materiales.guardar-ancho-metraje-prenda');
    Route::get('/api/materiales/{pedido}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerMateriales'])->name('api.materiales');
    Route::get('/api/filtros/{column}', [\App\Http\Controllers\Insumos\InsumosController::class, 'obtenerValoresFiltro'])->name('api.filtros');
    Route::post('/materiales/{numeroPedido}/cambiar-estado', [\App\Http\Controllers\Insumos\InsumosController::class, 'cambiarEstado'])->name('materiales.cambiar-estado');
    Route::get('/test', function () {
        return view('insumos.test');
    })->name('test');
    
    // Cálculo de Metrajes
    Route::get('/metrajes', function () {
        return view('insumos.metrajes.index');
    })->name('metrajes.index');
});

// ========================================
// RUTAS PÚBLICAS DE SUPERVISOR-PEDIDOS (accesibles para asesores, supervisores y admins)
// ========================================
Route::middleware(['auth', 'role:asesor,supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Obtener datos en JSON (accesible para asesores, supervisores y admins)
    Route::get('/{id}/datos', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDatos'])->name('datos');
    
    // Obtener datos de factura para mostrar en modal (accesible para asesores, supervisores y admins)
    Route::get('/{id}/factura-datos', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDatosFactura'])->name('factura-datos');
    
    // Obtener datos para comparación (pedido vs cotización) (accesible para asesores, supervisores y admins)
    Route::get('/{id}/comparar', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDatosComparacion'])->name('comparar');
});

// ========================================
// RUTAS PARA SUPERVISOR DE PEDIDOS
// ========================================
Route::middleware(['auth', 'role:supervisor_pedidos,admin'])->prefix('supervisor-pedidos')->name('supervisor-pedidos.')->group(function () {
    // Listar órdenes
    Route::get('/', [App\Http\Controllers\SupervisorPedidosController::class, 'index'])->name('index');
    
    // Perfil del supervisor
    Route::get('/perfil/editar', [App\Http\Controllers\SupervisorPedidosController::class, 'profile'])->name('profile');
    Route::post('/perfil/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'updateProfile'])->name('update-profile');
    
    // Pendientes Bordados-Estampado
    Route::get('/pendientes-bordado-estampado', [App\Http\Controllers\SupervisorPedidosController::class, 'pendientesBordadoEstampado'])->name('pendientes-bordado-estampado');
    
    // Detalles y aprobación de procesos
    Route::get('/procesos/{id}/detalles', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerDetallesProceso'])->name('procesos.detalles');
    Route::post('/procesos/{id}/aprobar', [App\Http\Controllers\SupervisorPedidosController::class, 'aprobarProceso'])->name('procesos.aprobar');

    // Fecha de llegada de recibo (autosave)
    Route::post('/recibos/{id}/fecha-llegada', [App\Http\Controllers\SupervisorPedidosController::class, 'guardarFechaLlegadaRecibo'])->name('recibos.fecha-llegada');
    
    // Notificaciones
    Route::get('/notificaciones', [App\Http\Controllers\SupervisorPedidosController::class, 'getNotifications'])->name('notifications');
    Route::post('/notificaciones/marcar-todas-leidas', [App\Http\Controllers\SupervisorPedidosController::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
    Route::post('/notificaciones/{notificationId}/marcar-leida', [App\Http\Controllers\SupervisorPedidosController::class, 'markNotificationAsRead'])->name('mark-read');
    
    // Obtener opciones de filtro (debe ir antes de /{id})
    Route::get('/filtro-opciones/{campo}', [App\Http\Controllers\SupervisorPedidosController::class, 'obtenerOpcionesFiltro'])->name('filtro-opciones');
    
    // Ruta para obtener contador de órdenes pendientes de aprobación (DEBE IR ANTES DE /{id})
    Route::get('/ordenes-pendientes-count', [App\Http\Controllers\SupervisorPedidosController::class, 'ordenesPendientesCount'])->name('ordenes-pendientes-count');
    
    // Ver detalle de orden
    Route::get('/{id}', [App\Http\Controllers\SupervisorPedidosController::class, 'show'])->name('show');
    
    // Descargar PDF
    Route::get('/{id}/pdf', [App\Http\Controllers\SupervisorPedidosController::class, 'descargarPDF'])->name('pdf');
    
    // Anular orden
    Route::post('/{id}/anular', [App\Http\Controllers\SupervisorPedidosController::class, 'anular'])->name('anular');
    
    // Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente)
    Route::post('/{id}/aprobar', [App\Http\Controllers\SupervisorPedidosController::class, 'aprobar'])->name('aprobar');
    
    // Cambiar estado
    Route::patch('/{id}/estado', [App\Http\Controllers\SupervisorPedidosController::class, 'cambiarEstado'])->name('cambiar-estado');
    
    // Editar pedido
    Route::get('/{id}/editar', [App\Http\Controllers\SupervisorPedidosController::class, 'edit'])->name('editar');
    
    // Actualizar pedido
    Route::put('/{id}/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'update'])->name('actualizar');
    Route::post('/{id}/actualizar', [App\Http\Controllers\SupervisorPedidosController::class, 'update'])->name('actualizar.post');
    
    // Obtener datos de una prenda específica para edición modal (supervisor)
    Route::get('/{pedidoId}/prenda/{prendaId}/datos', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'obtenerDatosPrendaEdicion'])->where('pedidoId', '[0-9]+')->where('prendaId', '[0-9]+')->name('prenda-datos');
    
    // Actualizar prenda completa (con novedades) - Ruta adicional para edición de prendas desde el modal
    Route::post('/{id}/actualizar-prenda', [App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarPrendaCompleta'])->where('id', '[0-9]+')->name('pedidos.actualizar-prenda-completa');
    
    // Actualizar proceso específico de una prenda (para supervisor)
    Route::match(['patch', 'post'], '/{prendaId}/procesos/{procesoId}', [App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class, 'actualizarProcesoEspecifico'])->where(['prendaId' => '[0-9]+', 'procesoId' => '[0-9]+'])->name('procesos-actualizar');
    
    // Eliminar imagen de prenda
    Route::delete('/imagen/{tipo}/{id}', [App\Http\Controllers\SupervisorPedidosController::class, 'deleteImage'])->name('imagen.eliminar');
});

// ========================================
// RUTAS PARA MÓDULO BORDADO
// ========================================
Route::middleware(['auth', 'role:bordado,admin'])->prefix('bordado')->name('bordado.')->group(function () {
    // Listar pedidos del módulo Bordado
    Route::get('/', function () {
        return view('bordado.index');
    })->name('index');

    // Ruta legada de cotizaciones (redireccionar a lista)
    Route::get('/cotizaciones', function () {
        return redirect()->route('bordado.cotizaciones.lista');
    })->name('cotizaciones');

    // Cotizaciones - Submenú
    Route::prefix('cotizaciones')->name('cotizaciones.')->group(function () {
        // Lista de cotizaciones
        Route::get('/lista', function () {
            return view('bordado.cotizaciones.lista');
        })->name('lista');

        // Medidas
        Route::get('/medidas', function () {
            return view('bordado.cotizaciones.medidas');
        })->name('medidas');
    });
});

// ========================================
// RUTAS API PÚBLICAS PARA FESTIVOS
// ========================================
Route::prefix('api')->name('api.')->group(function () {
    // Rutas públicas para festivos (sin autenticación requerida)
    Route::get('/festivos', [App\Http\Controllers\Api_temp\FestivosController::class, 'index'])->name('festivos.index');
    Route::get('/festivos/detailed', [App\Http\Controllers\Api_temp\FestivosController::class, 'detailed'])->name('festivos.detailed');
    Route::get('/festivos/check', [App\Http\Controllers\Api_temp\FestivosController::class, 'check'])->name('festivos.check');
    Route::get('/festivos/range', [App\Http\Controllers\Api_temp\FestivosController::class, 'range'])->name('festivos.range');
});

// ========================================
// RUTAS PARA ESTADOS DE COTIZACIONES
// ========================================
Route::middleware(['auth', 'verified'])->name('cotizaciones.estado.')->group(function () {
    // Asesor: Enviar cotización a contador
    Route::post('/cotizaciones/{cotizacion}/enviar', [App\Http\Controllers\CotizacionEstadoController::class, 'enviar'])->name('enviar');
    
    // Contador: Aprobar cotización
    Route::post('/cotizaciones/{cotizacion}/aprobar-contador', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarContador'])->name('aprobar-contador');
    
    // Contador: Aprobar cotización para pedido (APROBADA_COTIZACIONES -> APROBADO_PARA_PEDIDO)
    Route::post('/cotizaciones/{cotizacion}/aprobar-para-pedido', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarParaPedido'])->name('aprobar-para-pedido');
    
    // Aprobador de Cotizaciones: Aprobar cotización
    Route::post('/cotizaciones/{cotizacion}/aprobar-aprobador', [App\Http\Controllers\CotizacionEstadoController::class, 'aprobarAprobador'])->name('aprobar-aprobador');
    
    // Aprobador de Cotizaciones: Rechazar y enviar a corrección
    Route::post('/cotizaciones/{cotizacion}/rechazar', [App\Http\Controllers\CotizacionEstadoController::class, 'rechazar'])->name('rechazar');
    
    // Ver historial de cambios
    Route::get('/cotizaciones/{cotizacion}/historial', [App\Http\Controllers\CotizacionEstadoController::class, 'historial'])->name('historial');
    
    // Ver seguimiento de cotización
    Route::get('/cotizaciones/{cotizacion}/seguimiento', [App\Http\Controllers\CotizacionEstadoController::class, 'seguimiento'])->name('seguimiento');
});

// ========================================
// RUTAS PARA ESTADOS DE PEDIDOS
// ========================================
Route::middleware(['auth', 'verified'])->name('pedidos.estado.')->group(function () {
    // Supervisor de Pedidos: Aprobar pedido
    Route::post('/pedidos/{pedido}/aprobar-supervisor', [App\Http\Controllers\PedidoEstadoController::class, 'aprobarSupervisor'])->name('aprobar-supervisor');
    
    // Ver historial de cambios
    Route::get('/pedidos/{pedido}/historial', [App\Http\Controllers\PedidoEstadoController::class, 'historial'])->name('historial');
    
    // Ver seguimiento de pedido
    Route::get('/pedidos/{pedido}/seguimiento', [App\Http\Controllers\PedidoEstadoController::class, 'seguimiento'])->name('seguimiento');
});

// ========================================
// RUTA PARA SERVIR IMÁGENES DE STORAGE
// ========================================

Route::get('/storage-serve/{path}', function($path) {
    $path = str_replace('..', '', $path);
    return redirect('/storage/' . ltrim($path, '/'));
})
    ->where('path', '.*')
    ->name('storage.serve');
// ========================================
// RUTAS DEL MÓDULO ASISTENCIA PERSONAL
// ========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/asistencia-personal', [AsistenciaPersonalWebController::class, 'index'])
        ->name('asistencia-personal.index');
    Route::get('/asistencia-personal/crear', [AsistenciaPersonalWebController::class, 'create'])
        ->name('asistencia-personal.create');
    Route::post('/asistencia-personal', [AsistenciaPersonalWebController::class, 'store'])
        ->name('asistencia-personal.store');
    Route::get('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'show'])
        ->name('asistencia-personal.show');
    Route::get('/asistencia-personal/{id}/editar', [AsistenciaPersonalWebController::class, 'edit'])
        ->name('asistencia-personal.edit');
    Route::patch('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'update'])
        ->name('asistencia-personal.update');
    Route::delete('/asistencia-personal/{id}', [AsistenciaPersonalWebController::class, 'destroy'])
        ->name('asistencia-personal.destroy');
});

// ========================================
// ========================================
// API ROUTES - ASISTENCIA PERSONAL
// ========================================
Route::middleware(['auth'])->prefix('asistencia-personal')->name('asistencia-personal.')->group(function () {
    Route::post('/procesar-pdf', [AsistenciaPersonalController::class, 'procesarPDF'])
        ->name('procesar-pdf');
    Route::post('/validar-registros', [AsistenciaPersonalController::class, 'validarRegistros'])
        ->name('validar-registros');
    Route::post('/guardar-registros', [AsistenciaPersonalController::class, 'guardarRegistros'])
        ->name('guardar-registros');
    Route::post('/calcular-horas', [AsistenciaPersonalController::class, 'calcularHoras'])
        ->name('calcular-horas');
    Route::get('/reportes/{id}/detalles', [AsistenciaPersonalController::class, 'getReportDetails'])
        ->name('reportes.detalles');
    Route::get('/reportes/{id}/ausencias', [AsistenciaPersonalController::class, 'getAbsenciasDelDia'])
        ->name('reportes.ausencias');
    Route::post('/guardar-asistencia-detallada', [App\Http\Controllers\Api_temp\AsistenciaDetalladaController::class, 'guardarCambios'])
        ->name('guardar-asistencia-detallada');
    Route::post('/guardar-hora-extra-agregada', [AsistenciaPersonalController::class, 'guardarHoraExtraAgregada'])
        ->name('guardar-hora-extra-agregada');
    Route::post('/guardar-marcas-editadas', [AsistenciaPersonalController::class, 'guardarMarcasEditadas'])
        ->name('guardar-marcas-editadas');
    Route::post('/agregar-marca-faltante', [AsistenciaPersonalController::class, 'agregarMarcaFaltante'])
        ->name('agregar-marca-faltante');
    Route::post('/guardar-marcas-multiples', [AsistenciaPersonalController::class, 'guardarMarcasMultiples'])
        ->name('guardar-marcas-multiples');
    // Ruta de prueba temporal
    Route::get('/obtener-todas-las-personas-test', function() {
        return response()->json([
            'success' => true,
            'test' => 'OK',
            'message' => 'La ruta test funciona'
        ]);
    })->middleware(['auth']);
    Route::get('/test-simple', function() {
        return response()->json(['ok' => true]);
    });
    Route::post('/obtener-horas-extras-agregadas-batch', [AsistenciaPersonalController::class, 'obtenerHorasExtrasAgregadasBatch'])
        ->name('obtener-horas-extras-agregadas-batch');
    Route::get('/obtener-horas-extras-agregadas/{codigo_persona}', [AsistenciaPersonalController::class, 'obtenerHorasExtrasAgregadas'])
        ->name('obtener-horas-extras-agregadas');
});

// ========================================
// API PÚBLICA - DATOS DE PEDIDOS (SIN AUTH)
// ========================================
Route::prefix('api')->group(function () {
    Route::get('operario/pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('api.operario.pedido-data');
});

// ========================================
// API ROUTES - VALOR HORA EXTRA
// ========================================
Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
    Route::get('valor-hora-extra/{codigoPersona}', [App\Http\Controllers\Api_temp\ValorHoraExtraController::class, 'obtener'])
        ->name('valor-hora-extra.obtener');
    Route::post('valor-hora-extra/guardar', [App\Http\Controllers\Api_temp\ValorHoraExtraController::class, 'guardar'])
        ->name('valor-hora-extra.guardar');
});

// ========================================
// RUTAS WEB - PEDIDOS EDITABLES (Arquitectura Web Tradicional)
// ========================================
Route::middleware(['auth', 'role:asesor,admin,supervisor_pedidos'])->prefix('asesores/pedidos-editable')->name('asesores.pedidos-editable.')->group(function () {
    // Ruta fallback que redirige a crear-desde-cotizacion
    Route::get('crear', function() {
        return redirect()->route('asesores.pedidos-editable.crear-desde-cotizacion');
    });
    
    // Mostrar formulario para crear desde COTIZACIÓN (pre-carga cotizaciones)
    Route::get('crear-desde-cotizacion', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearDesdeCotizacion'])
        ->name('crear-desde-cotizacion');
    
    // Mostrar formulario para crear PEDIDO NUEVO (vacío)
    Route::get('crear-nuevo', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearNuevo'])
        ->name('crear-nuevo');
    
    // Gestión de ítems (retorna JSON)
    Route::post('items/agregar', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'agregarItem'])
        ->name('agregar-item');
    Route::post('items/eliminar', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'eliminarItem'])
        ->name('eliminar-item');
    Route::get('items', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'obtenerItems'])
        ->name('obtener-items');
    
    // Validación y creación
    Route::post('validar', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'validarPedido'])
        ->name('validar');
    Route::post('crear', [App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController::class, 'crearPedido'])
        ->name('crear');
});

// ========================================
// API RUTAS - GUARDAR PEDIDO DESDE JSON
// ========================================
// Route::middleware(['auth', 'role:asesor,supervisor_pedidos,admin'])->prefix('api/pedidos')->name('api.pedidos.')->group(function () {
//     Route::post('/guardar-desde-json', [App\Infrastructure\Http\Controllers\Asesores\GuardarPedidoJSONController::class, 'guardar'])
//         ->name('guardar-json');
//     Route::post('/validar-json', [App\Infrastructure\Http\Controllers\Asesores\GuardarPedidoJSONController::class, 'validar'])
//         ->name('validar-json');
// });

// ========================================
// RUTAS PARA CARTERA - PEDIDOS
// ========================================
Route::middleware(['auth', 'role:cartera,admin'])->prefix('cartera')->name('cartera.')->group(function () {
    // Gestión de pedidos por aprobar (pendientes)
    Route::get('/pedidos', function () {
        return view('cartera-pedidos.cartera-pedidos-supervisor');
    })->name('pedidos');
    
    // Gestión de pedidos aprobados por cartera
    Route::get('/aprobados', function () {
        return view('cartera-pedidos.cartera-aprobados');
    })->name('aprobados');
    
    // Gestión de pedidos rechazados por cartera
    Route::get('/rechazados', function () {
        return view('cartera-pedidos.cartera-rechazados');
    })->name('rechazados');
    
    // Gestión de pedidos anulados
    Route::get('/anulados', function () {
        return view('cartera-pedidos.cartera-anulados');
    })->name('anulados');
});

// ========================================
// API CARTERA - PEDIDOS
// ========================================
Route::middleware(['auth', 'role:cartera,admin'])->prefix('api/cartera')->name('api.cartera.')->group(function () {
    // GET pedidos por estado (cartera) - principal para pendientes
    Route::get('/pedidos', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerPedidos'])->name('list');
    
    // GET pedidos aprobados (PENDIENTE_SUPERVISOR)
    Route::get('/aprobados', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerAprobados'])->name('aprobados');
    
    // GET pedidos rechazados (RECHAZADO_CARTERA)
    Route::get('/rechazados', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerRechazados'])->name('rechazados');
    
    // GET pedidos anulados (Anulada)
    Route::get('/anulados', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerAnulados'])->name('anulados');
    
    // GET opciones de filtro (clientes y fechas únicos)
    Route::get('/opciones-filtro', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerOpcionesFiltro'])->name('opciones-filtro');
    
    // POST aprobar pedido
    Route::post('/pedidos/{id}/aprobar', [App\Http\Controllers\CarteraPedidosController::class, 'aprobarPedido'])->name('aprobar');
    
    // POST rechazar pedido
    Route::post('/pedidos/{id}/rechazar', [App\Http\Controllers\CarteraPedidosController::class, 'rechazarPedido'])->name('rechazar');
    
    // GET datos de factura para ver en modal
    Route::get('/pedidos/{id}/factura-datos', [App\Http\Controllers\CarteraPedidosController::class, 'obtenerDatosFactura'])->name('factura-datos');
});

// ========================================
// RUTAS DE AUTENTICACIÓN
// ========================================
require __DIR__.'/auth.php';

// ========================================
// RUTAS DE ASESORES (MÓDULO INDEPENDIENTE)
// ========================================
// Las rutas de asesores ya están definidas arriba en este archivo
// El archivo asesores.php se mantiene como referencia pero no se carga aquí para evitar duplicados
// require __DIR__.'/asesores.php';  // DESHABILITADO: Las rutas están en web.php línea 431

// ========================================
// RUTAS DE DESPACHO (MÓDULO NUEVO)
// ========================================
require __DIR__.'/despacho.php';

// ========================================
// RUTAS DE BODEGA (MÓDULO NUEVO)
// ========================================
require __DIR__.'/bodega.php';

// ========================================
// RUTAS DE EPP (GESTIÓN COMPLETA)
// ========================================
Route::prefix('epp')->name('epp.')->group(function () {
    Route::get('/', [App\Infrastructure\Http\Controllers\Epp\EppController::class, 'vistaGestion'])
        ->name('gestion');
    
    // Ruta de prueba
    Route::get('/test', [App\Infrastructure\Http\Controllers\Epp\EppController::class, 'test'])
        ->name('test');
});

// ========================================

// ========================================
// RUTAS WEB PARA ACTIVACIÓN DE RECIBOS (JSON)
// ========================================
use Illuminate\Http\Request;

Route::middleware(['auth'])->post('procesos/{procesoId}/activar-recibo', function(Request $request, $procesoId) {
    try {
        // Cambiar estado directamente en la tabla
        $proceso = \DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $procesoId)
            ->first();
            
        if (!$proceso) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
            ], 404);
        }
        
        $activar = $request->input('activar');
        
        if ($activar) {
            \DB::table('pedidos_procesos_prenda_detalles')
                ->where('id', $procesoId)
                ->update([
                    'estado' => 'APROBADO',
                    'fecha_aprobacion' => now(),
                    'aprobado_por' => auth()->id()
                ]);
        } else {
            \DB::table('pedidos_procesos_prenda_detalles')
                ->where('id', $procesoId)
                ->update([
                    'estado' => 'PENDIENTE',
                    'fecha_aprobacion' => null,
                    'aprobado_por' => null
                ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => $activar ? 'Recibo activado correctamente' : 'Recibo desactivado correctamente'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar estado: ' . $e->getMessage()
        ], 500);
    }
})->name('procesos.activar-recibo-simple');
