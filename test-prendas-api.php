<?php
// Script de prueba para verificar la API de prendas

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "TEST: Sistema de Prendas Autocomplete\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Test 1: Insertar prendas de prueba
echo "📝 Test 1: Insertando prendas de prueba...\n";
$prendas_test = ['POLO', 'CAMISA', 'PANTALÓN', 'GORRO', 'CALCETA'];

foreach ($prendas_test as $prenda) {
    try {
        DB::table('prendas_cotizaciones_tipos')->insertOrIgnore([
            'nombre' => $prenda,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "  ✅ Insertado: $prenda\n";
    } catch (\Exception $e) {
        echo "  ⚠️ Error insertando $prenda: " . $e->getMessage() . "\n";
    }
}

// Test 2: Contar registros
echo "\n📊 Test 2: Contando registros...\n";
$count = DB::table('prendas_cotizaciones_tipos')->count();
echo "  Total de prendas guardadas: $count\n";

// Test 3: Obtener todas las prendas
echo "\n📋 Test 3: Listando todas las prendas...\n";
$prendas = DB::table('prendas_cotizaciones_tipos')
    ->select('nombre')
    ->distinct()
    ->orderBy('nombre')
    ->pluck('nombre')
    ->toArray();

echo "  Prendas disponibles:\n";
foreach ($prendas as $p) {
    echo "    - $p\n";
}

// Test 4: Verificar que duplicados no se creen
echo "\n🔍 Test 4: Verificando constraint UNIQUE...\n";
try {
    DB::table('prendas_cotizaciones_tipos')->insert([
        'nombre' => 'POLO',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "  ⚠️ Advertencia: Se permitió insertar duplicado (UNIQUE fallido)\n";
} catch (\Exception $e) {
    echo "  ✅ UNIQUE constraint funcionando correctamente\n";
    echo "  Mensaje: " . $e->getMessage() . "\n";
}

// Test 5: Verificar endpoints
echo "\n🔗 Test 5: Verificando rutas en web.php...\n";
$web_routes = file_get_contents('routes/web.php');
if (strpos($web_routes, '/api/logo-cotizacion-tecnicas/prendas') !== false) {
    echo "  ✅ Rutas encontradas en web.php\n";
} else {
    echo "  ❌ Rutas NO encontradas en web.php\n";
}

// Test 6: Verificar métodos del controller
echo "\n📄 Test 6: Verificando métodos del controller...\n";
$controller = file_get_contents('app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php');
if (strpos($controller, 'obtenerPrendas') !== false) {
    echo "  ✅ Método obtenerPrendas() existe\n";
} else {
    echo "  ❌ Método obtenerPrendas() NO existe\n";
}

if (strpos($controller, 'guardarPrenda') !== false) {
    echo "  ✅ Método guardarPrenda() existe\n";
} else {
    echo "  ❌ Método guardarPrenda() NO existe\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ Tests completados\n";
echo "═══════════════════════════════════════════════════════════\n";
