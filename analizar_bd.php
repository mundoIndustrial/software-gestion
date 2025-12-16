<?php
/**
 * Script completo de análisis de base de datos
 * Verifica estructura, relaciones y datos de cotizaciones
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        ANÁLISIS COMPLETO DE BASE DE DATOS                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ==========================================
// 1. ESTRUCTURA DE TABLAS
// ==========================================
echo "📊 1. VERIFICANDO ESTRUCTURA DE TABLAS\n";
echo "─────────────────────────────────────────────────────────────\n";

$tablasRelevantes = [
    'cotizaciones',
    'tipo_cotizacion',
    'tipos_cotizaciones',
    'prendas_cot',
    'logo_cot',
    'reflectivo_cot',
    'clientes'
];

$tablasEncontradas = [];
foreach ($tablasRelevantes as $tabla) {
    $existe = Schema::hasTable($tabla);
    echo ($existe ? "✅" : "❌") . " Tabla '{$tabla}'\n";
    if ($existe) {
        $tablasEncontradas[$tabla] = true;
        $columnas = Schema::getColumnListing($tabla);
        echo "   Columnas: " . implode(", ", array_slice($columnas, 0, 5)) . (count($columnas) > 5 ? ", ..." : "") . "\n";
    }
}

// ==========================================
// 2. ESTRUCTURA DE COTIZACIONES
// ==========================================
echo "\n📋 2. ESTRUCTURA DE TABLA 'cotizaciones'\n";
echo "─────────────────────────────────────────────────────────────\n";

if (Schema::hasTable('cotizaciones')) {
    $columnas = Schema::getColumnListing('cotizaciones');
    echo implode("\n", $columnas) . "\n";
}

// ==========================================
// 3. TIPOS DE COTIZACIÓN
// ==========================================
echo "\n🏷️  3. TIPOS DE COTIZACIÓN\n";
echo "─────────────────────────────────────────────────────────────\n";

if (Schema::hasTable('tipos_cotizaciones')) {
    echo "Consultando tabla 'tipos_cotizaciones':\n";
    $tipos = DB::table('tipos_cotizaciones')->get();
    foreach ($tipos as $tipo) {
        echo "  ID: {$tipo->id} | Código: {$tipo->codigo} | Nombre: {$tipo->nombre}\n";
    }
} else if (Schema::hasTable('tipo_cotizacion')) {
    echo "Consultando tabla 'tipo_cotizacion':\n";
    $tipos = DB::table('tipo_cotizacion')->get();
    foreach ($tipos as $tipo) {
        echo "  ID: {$tipo->id} | Código: {$tipo->codigo} | Nombre: {$tipo->nombre}\n";
    }
} else {
    echo "⚠️  No se encontró tabla de tipos de cotización\n";
    echo "Verificando valores únicos de 'tipo_cotizacion_id' en cotizaciones:\n";
    $tiposUsados = DB::table('cotizaciones')
        ->distinct()
        ->pluck('tipo_cotizacion_id')
        ->filter()
        ->sort();
    echo "  IDs encontrados: " . implode(", ", $tiposUsados->toArray()) . "\n";
}

// ==========================================
// 4. ÚLTIMAS COTIZACIONES GUARDADAS
// ==========================================
echo "\n📝 4. ÚLTIMAS 10 COTIZACIONES GUARDADAS\n";
echo "─────────────────────────────────────────────────────────────\n";

$cotizaciones = DB::table('cotizaciones')
    ->select([
        'id',
        'numero_cotizacion',
        'tipo_cotizacion_id',
        'estado',
        'es_borrador',
        'asesor_id',
        'cliente_id',
        'created_at'
    ])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($cotizaciones as $cot) {
    $borrador = ($cot->es_borrador === 1 || $cot->es_borrador === '1') ? "🔖 BORRADOR" : "✉️  ENVIADA";
    echo "{$borrador} | ID: {$cot->id} | Número: " . ($cot->numero_cotizacion ?? "SIN NÚMERO") . " | Tipo: {$cot->tipo_cotizacion_id} | {$cot->created_at}\n";
}

// ==========================================
// 5. CONTEO POR ESTADO
// ==========================================
echo "\n📊 5. CONTEO POR ESTADO\n";
echo "─────────────────────────────────────────────────────────────\n";

$conteos = DB::table('cotizaciones')
    ->groupBy('estado', 'es_borrador')
    ->select('estado', 'es_borrador', DB::raw('COUNT(*) as total'))
    ->get();

foreach ($conteos as $conteo) {
    echo "Estado: {$conteo->estado} | es_borrador: {$conteo->es_borrador} | Total: {$conteo->total}\n";
}

// ==========================================
// 6. BORRADORES RECIENTES
// ==========================================
echo "\n🔖 6. BORRADORES GUARDADOS (es_borrador = 1)\n";
echo "─────────────────────────────────────────────────────────────\n";

$borradores = DB::table('cotizaciones')
    ->where('es_borrador', 1)
    ->orWhere('es_borrador', '1')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($borradores->isEmpty()) {
    echo "❌ No se encontraron borradores\n";
} else {
    foreach ($borradores as $borrador) {
        $cliente = DB::table('clientes')->find($borrador->cliente_id);
        $clienteNombre = $cliente->nombre ?? 'DESCONOCIDO';
        echo "ID: {$borrador->id} | Cliente: {$clienteNombre} | Tipo: {$borrador->tipo_cotizacion_id} | {$borrador->created_at}\n";
    }
}

// ==========================================
// 7. RELACIONES EN MODELOS
// ==========================================
echo "\n🔗 7. RELACIONES DETECTADAS\n";
echo "─────────────────────────────────────────────────────────────\n";

// Verificar si existen las tablas relacionadas
$relacionesEsperadas = [
    'prendas_cot' => 'Prendas',
    'logo_cot' => 'Logo/Bordado',
    'reflectivo_cot' => 'Reflectivo',
    'prenda_fotos_cot' => 'Fotos de Prendas',
    'prenda_variantes_cot' => 'Variantes'
];

foreach ($relacionesEsperadas as $tabla => $descripcion) {
    if (Schema::hasTable($tabla)) {
        $count = DB::table($tabla)->count();
        echo "✅ {$descripcion} ({$tabla}): {$count} registros\n";
    } else {
        echo "❌ {$descripcion} ({$tabla}): NO EXISTE\n";
    }
}

// ==========================================
// 8. ANÁLISIS DEL ÚLTIMO BORRADOR
// ==========================================
echo "\n🔬 8. ANÁLISIS DETALLADO DEL ÚLTIMO BORRADOR\n";
echo "─────────────────────────────────────────────────────────────\n";

$ultimoBorrador = DB::table('cotizaciones')
    ->where('es_borrador', 1)
    ->orderBy('created_at', 'desc')
    ->first();

if ($ultimoBorrador) {
    echo "ID: {$ultimoBorrador->id}\n";
    echo "Número Cotización: " . ($ultimoBorrador->numero_cotizacion ?? 'SIN ASIGNAR') . "\n";
    echo "Cliente ID: {$ultimoBorrador->cliente_id}\n";
    
    $cliente = DB::table('clientes')->find($ultimoBorrador->cliente_id);
    echo "Cliente Nombre: " . ($cliente->nombre ?? 'DESCONOCIDO') . "\n";
    
    echo "Tipo Cotización ID: {$ultimoBorrador->tipo_cotizacion_id}\n";
    echo "Estado: {$ultimoBorrador->estado}\n";
    echo "es_borrador: {$ultimoBorrador->es_borrador}\n";
    echo "Asesor ID: {$ultimoBorrador->asesor_id}\n";
    
    // Verificar datos JSON
    if (!empty($ultimoBorrador->productos)) {
        $productos = json_decode($ultimoBorrador->productos, true);
        echo "Productos guardados: " . count($productos ?? []) . "\n";
    }
    if (!empty($ultimoBorrador->tecnicas)) {
        $tecnicas = json_decode($ultimoBorrador->tecnicas, true);
        echo "Técnicas guardadas: " . count($tecnicas ?? []) . "\n";
    }
    
    echo "Creado: {$ultimoBorrador->created_at}\n";
    echo "Actualizado: {$ultimoBorrador->updated_at}\n";
} else {
    echo "❌ No hay borradores en la base de datos\n";
}

// ==========================================
// 9. PROBLEMAS IDENTIFICADOS
// ==========================================
echo "\n⚠️  9. PROBLEMAS IDENTIFICADOS\n";
echo "─────────────────────────────────────────────────────────────\n";

$problemas = [];

// Problema 1: Tabla de tipos de cotización
if (!Schema::hasTable('tipos_cotizaciones') && !Schema::hasTable('tipo_cotizacion')) {
    $problemas[] = "❌ No existe tabla para tipos de cotización (tipos_cotizaciones o tipo_cotizacion)";
}

// Problema 2: Borradores no visibles
if ($borradores->isNotEmpty() && Schema::hasTable('cotizaciones')) {
    $problemas[] = "⚠️  Existen " . $borradores->count() . " borradores pero podrían no ser visibles en la UI";
}

// Problema 3: Relación tipoCotizacion
$problemaTipo = DB::table('cotizaciones')
    ->whereNotNull('tipo_cotizacion_id')
    ->where('tipo_cotizacion_id', '!=', 0)
    ->count();
if ($problemaTipo > 0 && (!Schema::hasTable('tipos_cotizaciones') && !Schema::hasTable('tipo_cotizacion'))) {
    $problemas[] = "❌ {$problemaTipo} cotizaciones apuntan a tipos que no existen en la BD";
}

if (empty($problemas)) {
    echo "✅ No se identificaron problemas críticos\n";
} else {
    foreach ($problemas as $problema) {
        echo $problema . "\n";
    }
}

// ==========================================
// 10. RECOMENDACIONES
// ==========================================
echo "\n💡 10. RECOMENDACIONES\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "1. Verificar si la tabla de tipos existe en otra BD o con otro nombre\n";
echo "2. Cargar primero la relación tipoCotizacion en el controlador\n";
echo "3. Asegurar que tipo_cotizacion_id tenga un valor válido\n";
echo "4. Verificar que el filtro de borradores funcione correctamente en la vista\n";
echo "5. Usar query builder para debug: DB::enableQueryLog() y DB::getQueryLog()\n";

echo "\n✅ Análisis completado\n\n";
