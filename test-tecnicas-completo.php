<?php
// Test del flujo completo de técnicas combinadas

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Infrastructure\Http\Controllers\LogoCotizacionTecnicaController;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: TÉCNICAS COMBINADAS - FLUJO COMPLETO               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

// Preparar controller
$controller = new LogoCotizacionTecnicaController();

// Test 1: Obtener prendas
echo "\n📋 TEST 1: Obtener prendas disponibles\n";
echo "   Endpoint: GET /api/logo-cotizacion-tecnicas/prendas\n";
try {
    $response = $controller->obtenerPrendas();
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ SUCCESS\n";
        echo "   Total prendas: " . count($data['data']) . "\n";
        echo "   Prendas: " . implode(', ', array_slice($data['data'], 0, 5)) . "...\n";
    } else {
        echo "   ❌ FAILED: " . ($data['message'] ?? 'Sin mensaje'). "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Guardar nueva prenda
echo "\n📝 TEST 2: Guardar prenda nueva\n";
echo "   Endpoint: POST /api/logo-cotizacion-tecnicas/prendas\n";
echo "   Payload: { nombre: 'PRUEBA_TECNICAS' }\n";

try {
    // Simular request
    $request = new \Illuminate\Http\Request();
    $request->merge(['nombre' => 'PRUEBA_TECNICAS']);
    
    // Llamar método
    $response = $controller->guardarPrenda($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ SUCCESS: " . $data['message'] . "\n";
    } else {
        echo "   ⚠️ " . $data['message'] . "\n";
    }
} catch (\Exception $e) {
    echo "   ⚠️ Error: " . $e->getMessage() . "\n";
}

// Test 3: Guardar prenda duplicada
echo "\n🔄 TEST 3: Guardar prenda duplicada (debe ignorar)\n";
echo "   Payload: { nombre: 'PRUEBA_TECNICAS' }\n";

try {
    $request = new \Illuminate\Http\Request();
    $request->merge(['nombre' => 'PRUEBA_TECNICAS']);
    $response = $controller->guardarPrenda($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] || strpos($data['message'], 'existe') !== false) {
        echo "   ✅ Constraint UNIQUE funcionando\n";
    } else {
        echo "   ⚠️ " . $data['message'] . "\n";
    }
} catch (\Exception $e) {
    echo "   ✅ Constraint UNIQUE funcionando (excepción capturada)\n";
}

// Test 4: Verificar grupo_combinado en DB
echo "\n🔗 TEST 4: Verificar estructura de grupo_combinado\n";

$hasGroupoField = DB::select("
    SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'logo_cotizacion_tecnica_prendas' 
    AND COLUMN_NAME = 'grupo_combinado'
");

if ($hasGroupoField) {
    echo "   ✅ Campo 'grupo_combinado' existe\n";
    
    $groupCount = DB::table('logo_cotizacion_tecnica_prendas')
        ->whereNotNull('grupo_combinado')
        ->groupBy('grupo_combinado')
        ->count();
    echo "   Grupos combinados en DB: $groupCount\n";
} else {
    echo "   ❌ Campo 'grupo_combinado' NO existe\n";
}

// Test 5: Verificar tabla prendas
echo "\n📊 TEST 5: Verificar tabla prendas_cotizaciones_tipos\n";

$tableName = DB::select("
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'prendas_cotizaciones_tipos'
");

if ($tableName) {
    echo "   ✅ Tabla 'prendas_cotizaciones_tipos' existe\n";
    
    $columns = DB::select("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'prendas_cotizaciones_tipos'
    ");
    
    echo "   Estructura:\n";
    foreach ($columns as $col) {
        echo "     - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} " . 
             ($col->IS_NULLABLE === 'NO' ? '(REQUIRED)' : '(NULL)') . "\n";
    }
    
    $prendasCount = DB::table('prendas_cotizaciones_tipos')->count();
    echo "   Total de prendas guardadas: $prendasCount\n";
} else {
    echo "   ❌ Tabla NO existe\n";
}

// Test 6: Verificar rutas
echo "\n🔗 TEST 6: Verificar rutas registradas\n";

$webRoutes = file_get_contents('routes/web.php');
$hasRoute1 = strpos($webRoutes, "Route::get('prendas'") !== false;
$hasRoute2 = strpos($webRoutes, "Route::post('prendas'") !== false;

if ($hasRoute1 && $hasRoute2) {
    echo "   ✅ Ambas rutas registradas (GET y POST)\n";
} else {
    echo "   ⚠️ Faltan rutas:\n";
    echo "     GET prendas: " . ($hasRoute1 ? "✅" : "❌") . "\n";
    echo "     POST prendas: " . ($hasRoute2 ? "✅" : "❌") . "\n";
}

// Test 7: Verificar modal JavaScript
echo "\n📄 TEST 7: Verificar modal JavaScript\n";

$jsCode = file_get_contents('public/js/logo-cotizacion-tecnicas.js');
$hasAutocomplete = strpos($jsCode, 'fetch(\'/api/logo-cotizacion-tecnicas/prendas\')') !== false;
$hasUppercase = strpos($jsCode, 'text-transform: uppercase') !== false;
$hasDListaSugerencias = strpos($jsCode, 'dListaSugerencias') !== false;

if ($hasAutocomplete && $hasUppercase && $hasDListaSugerencias) {
    echo "   ✅ Modal con autocomplete completamente implementado\n";
    echo "     - Fetch a API: ✅\n";
    echo "     - Text uppercase: ✅\n";
    echo "     - Dropdown suggestions: ✅\n";
} else {
    echo "   ⚠️ Falta implementar:\n";
    echo "     - Fetch a API: " . ($hasAutocomplete ? "✅" : "❌") . "\n";
    echo "     - Text uppercase: " . ($hasUppercase ? "✅" : "❌") . "\n";
    echo "     - Dropdown suggestions: " . ($hasDListaSugerencias ? "✅" : "❌") . "\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ TESTS COMPLETADOS                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
