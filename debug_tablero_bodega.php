<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TablaOriginalBodega;

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🔍 DEBUG: ANÁLISIS COMPLETO DEL TABLERO DE BODEGA\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1️⃣ DATOS BASICOS DE LA TABLA
echo "1️⃣  INFORMACIÓN DE LA TABLA BODEGA\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$countTotal = TablaOriginalBodega::count();
echo "Total de registros: $countTotal\n";

$countByEstado = TablaOriginalBodega::selectRaw('estado, COUNT(*) as cantidad')
    ->groupBy('estado')
    ->get();

echo "\nDistribución por ESTADO:\n";
foreach($countByEstado as $row) {
    echo "  • {$row->estado}: {$row->cantidad}\n";
}

$countByArea = TablaOriginalBodega::selectRaw('area, COUNT(*) as cantidad')
    ->groupBy('area')
    ->get();

echo "\nDistribución por ÁREA:\n";
foreach($countByArea as $row) {
    echo "  • {$row->area}: {$row->cantidad}\n";
}

// 2️⃣ PRIMEROS 5 REGISTROS CON TODOS LOS CAMPOS
echo "\n\n2️⃣  PRIMEROS 5 REGISTROS COMPLETOS\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$ordenes = TablaOriginalBodega::limit(5)->get();

foreach($ordenes as $i => $orden) {
    echo "\n📦 ORDEN #{($i+1)} - Pedido: {$orden->pedido}\n";
    echo "─────────────────────────────────────────────────────────────────────\n";
    
    $campos = [
        'pedido' => 'Pedido',
        'estado' => 'Estado',
        'area' => 'Área',
        'cliente' => 'Cliente',
        'descripcion' => 'Descripción',
        'cantidad' => 'Cantidad',
        'novedades' => 'Novedades',
        'asesora' => 'Asesora',
        'forma_de_pago' => 'Forma de Pago',
        'fecha_de_creacion_de_orden' => 'Fecha Creación',
        'dia_de_entrega' => 'Día de Entrega',
        'encargado_orden' => 'Encargado',
    ];
    
    foreach($campos as $campo => $label) {
        $valor = $orden->$campo ?? 'NULL';
        if(is_array($valor)) $valor = json_encode($valor);
        echo "  {$label}: $valor\n";
    }
}

// 3️⃣ ESTRUCTURA DE COLUMNAS
echo "\n\n3️⃣  ESTRUCTURA DE COLUMNAS EN BASE DE DATOS\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$columnas = DB::select("
    SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tabla_original_bodega'
    ORDER BY ORDINAL_POSITION
");

foreach($columnas as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
    echo "  • {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: $nullable)\n";
}

// 4️⃣ VALORES UNICOS EN CAMPOS CLAVE
echo "\n\n4️⃣  VALORES ÚNICOS EN CAMPOS CLAVE\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$estados = DB::table('tabla_original_bodega')->distinct()->pluck('estado')->toArray();
echo "\nESTADOS únicos:\n";
foreach($estados as $e) {
    echo "  • $e\n";
}

$areas = DB::table('tabla_original_bodega')->distinct()->pluck('area')->toArray();
echo "\nÁREAS únicas:\n";
foreach($areas as $a) {
    echo "  • $a\n";
}

$asesor = DB::table('tabla_original_bodega')->distinct()->pluck('asesora')->take(10)->toArray();
echo "\nASESORAS (primeras 10):\n";
foreach($asesor as $a) {
    echo "  • $a\n";
}

// 5️⃣ JSON QUE DEVOLVERÍA LA API
echo "\n\n5️⃣  ESTRUCTURA JSON QUE DEVOLVERÍA LA API\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$primeraOrden = TablaOriginalBodega::first();
if($primeraOrden) {
    echo json_encode($primeraOrden->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 6️⃣ RESUMEN DE LO QUE MUESTRA EL TABLERO
echo "\n\n6️⃣  RESUMEN - LO QUE MUESTRA EL TABLERO\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$resumen = DB::table('tabla_original_bodega')
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN estado = "Entregado" THEN 1 ELSE 0 END) as entregados,
        SUM(CASE WHEN estado = "En Ejecución" THEN 1 ELSE 0 END) as en_ejecucion,
        SUM(CASE WHEN estado = "No iniciado" THEN 1 ELSE 0 END) as no_iniciados,
        SUM(CASE WHEN estado = "Anulada" THEN 1 ELSE 0 END) as anuladas,
        COUNT(DISTINCT area) as areas_diferentes,
        COUNT(DISTINCT cliente) as clientes_diferentes
    ')
    ->first();

echo "Total de órdenes: {$resumen->total}\n";
echo "  ✓ Entregados: {$resumen->entregados}\n";
echo "  ⏳ En Ejecución: {$resumen->en_ejecucion}\n";
echo "  ⊘ No iniciados: {$resumen->no_iniciados}\n";
echo "  ✗ Anuladas: {$resumen->anuladas}\n";
echo "\nÁreas diferentes: {$resumen->areas_diferentes}\n";
echo "Clientes diferentes: {$resumen->clientes_diferentes}\n";

// 7️⃣ EJEMPLO DE FILA COMPLETA FORMATEADA
echo "\n\n7️⃣  EJEMPLO DE FILA FORMATEADA (Como la ve el usuario)\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$ejemplo = TablaOriginalBodega::orderBy('pedido', 'desc')->first();
if($ejemplo) {
    echo "PEDIDO: {$ejemplo->pedido}\n";
    echo "ESTADO: {$ejemplo->estado}\n";
    echo "ÁREA: {$ejemplo->area}\n";
    echo "CLIENTE: {$ejemplo->cliente}\n";
    echo "DESCRIPCIÓN: " . substr($ejemplo->descripcion ?? '', 0, 100) . "...\n";
    echo "CANTIDAD: {$ejemplo->cantidad}\n";
    echo "FECHA CREACIÓN: {$ejemplo->fecha_de_creacion_de_orden}\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "✅ DEBUG COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════════════\n";
