<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║     VERIFICACIÓN Y MIGRACIÓN DE DATOS - MUNDO INDUSTRIAL (mundo_bd3)      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Verificar conexión a la base de datos
try {
    $dbName = DB::select("SELECT DATABASE() as db")[0]->db;
    echo "✅ Conectado a base de datos: $dbName\n\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================================
// 1. VERIFICAR TABLAS EXISTENTES
// ============================================================================
echo "📋 VERIFICANDO TABLAS EXISTENTES\n";
echo str_repeat("=", 80) . "\n";

$tablasRequeridas = [
    'tabla_original' => 'Tabla fuente con pedidos históricos',
    'registros_por_orden' => 'Tabla fuente con prendas y tallas',
    'pedidos_produccion' => 'Tabla destino para pedidos',
    'prendas_pedido' => 'Tabla destino para prendas',
    'procesos_prenda' => 'Tabla destino para procesos',
    'users' => 'Tabla de usuarios',
    'clientes' => 'Tabla de clientes',
];

$tablasExistentes = [];
$tablasFaltantes = [];

foreach ($tablasRequeridas as $tabla => $descripcion) {
    if (Schema::hasTable($tabla)) {
        $count = DB::table($tabla)->count();
        echo "✅ $tabla ($count registros) - $descripcion\n";
        $tablasExistentes[$tabla] = $count;
    } else {
        echo "❌ $tabla NO EXISTE - $descripcion\n";
        $tablasFaltantes[] = $tabla;
    }
}

echo "\n";

// ============================================================================
// 2. ANÁLISIS DE DATOS A MIGRAR
// ============================================================================
if (isset($tablasExistentes['tabla_original']) && isset($tablasExistentes['registros_por_orden'])) {
    echo "📊 ANÁLISIS DE DATOS A MIGRAR\n";
    echo str_repeat("=", 80) . "\n";
    
    // Análisis de tabla_original
    $totalPedidos = DB::table('tabla_original')->count();
    $pedidosUnicos = DB::table('tabla_original')->distinct('pedido')->count('pedido');
    $asesorasUnicas = DB::table('tabla_original')->distinct('asesora')->whereNotNull('asesora')->count('asesora');
    $clientesUnicos = DB::table('tabla_original')->distinct('cliente')->whereNotNull('cliente')->count('cliente');
    
    echo "📋 TABLA_ORIGINAL:\n";
    echo "   Total registros: " . number_format($totalPedidos) . "\n";
    echo "   Pedidos únicos: " . number_format($pedidosUnicos) . "\n";
    echo "   Asesoras únicas: " . number_format($asesorasUnicas) . "\n";
    echo "   Clientes únicos: " . number_format($clientesUnicos) . "\n\n";
    
    // Análisis de registros_por_orden
    $totalRegistros = DB::table('registros_por_orden')->count();
    $pedidosConPrendas = DB::table('registros_por_orden')->distinct('pedido')->count('pedido');
    
    echo "📋 REGISTROS_POR_ORDEN:\n";
    echo "   Total registros: " . number_format($totalRegistros) . "\n";
    echo "   Pedidos con prendas: " . number_format($pedidosConPrendas) . "\n\n";
}

// ============================================================================
// 3. VERIFICAR ESTADO ACTUAL DE TABLAS DESTINO
// ============================================================================
if (isset($tablasExistentes['pedidos_produccion'])) {
    echo "📦 ESTADO ACTUAL DE TABLAS DESTINO\n";
    echo str_repeat("=", 80) . "\n";
    
    $pedidosActuales = DB::table('pedidos_produccion')->count();
    $pedidosConCotizacion = DB::table('pedidos_produccion')->whereNotNull('cotizacion_id')->count();
    $pedidosSinCotizacion = DB::table('pedidos_produccion')->whereNull('cotizacion_id')->count();
    
    echo "📋 PEDIDOS_PRODUCCION:\n";
    echo "   Total pedidos: " . number_format($pedidosActuales) . "\n";
    echo "   Con cotizacion_id: " . number_format($pedidosConCotizacion) . " (NO se tocarán)\n";
    echo "   Sin cotizacion_id: " . number_format($pedidosSinCotizacion) . " (serán reemplazados)\n\n";
    
    if (isset($tablasExistentes['prendas_pedido'])) {
        $prendasActuales = DB::table('prendas_pedido')->count();
        echo "📋 PRENDAS_PEDIDO:\n";
        echo "   Total prendas: " . number_format($prendasActuales) . "\n\n";
    }
    
    if (isset($tablasExistentes['procesos_prenda'])) {
        $procesosActuales = DB::table('procesos_prenda')->count();
        echo "📋 PROCESOS_PRENDA:\n";
        echo "   Total procesos: " . number_format($procesosActuales) . "\n\n";
    }
}

// ============================================================================
// 4. DECISIÓN DE MIGRACIÓN
// ============================================================================
echo "🎯 DECISIÓN\n";
echo str_repeat("=", 80) . "\n";

if (!empty($tablasFaltantes)) {
    echo "❌ FALTAN TABLAS REQUERIDAS:\n";
    foreach ($tablasFaltantes as $tabla) {
        echo "   - $tabla\n";
    }
    echo "\n⚠️  Debes ejecutar las migraciones de Laravel primero:\n";
    echo "   php artisan migrate\n\n";
    exit(1);
}

if (!isset($tablasExistentes['tabla_original']) || $tablasExistentes['tabla_original'] == 0) {
    echo "⚠️  La tabla 'tabla_original' está vacía. No hay datos para migrar.\n\n";
    exit(0);
}

if (!isset($tablasExistentes['registros_por_orden']) || $tablasExistentes['registros_por_orden'] == 0) {
    echo "⚠️  La tabla 'registros_por_orden' está vacía. No hay prendas para migrar.\n\n";
    exit(0);
}

echo "✅ TODAS LAS TABLAS EXISTEN Y HAY DATOS PARA MIGRAR\n\n";

// ============================================================================
// 5. EJECUTAR MIGRACIÓN
// ============================================================================
echo "🚀 EJECUTANDO MIGRACIÓN\n";
echo str_repeat("=", 80) . "\n\n";

echo "Ejecutando comando: php artisan migrate:tabla-original-completo\n\n";

// Ejecutar el comando de migración
$output = [];
$returnCode = 0;

exec('cd "' . __DIR__ . '/.." && php artisan migrate:tabla-original-completo 2>&1', $output, $returnCode);

// Mostrar salida
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n";

if ($returnCode === 0) {
    echo "✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
} else {
    echo "❌ ERROR EN LA MIGRACIÓN (código: $returnCode)\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              FIN DEL PROCESO                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

exit($returnCode);
