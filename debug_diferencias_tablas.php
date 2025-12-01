<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TablaOriginalBodega;
use App\Models\PedidoProduccion;

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🔍 COMPARACIÓN: tabla_original_bodega vs pedidos_produccion\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1️⃣ INFORMACIÓN DE AMBAS TABLAS
echo "1️⃣  TABLA ORIGINAL BODEGA\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$bodegaCount = TablaOriginalBodega::count();
echo "Total registros: $bodegaCount\n";
$bodegaPrimera = TablaOriginalBodega::first();

if($bodegaPrimera) {
    echo "Campos principales:\n";
    echo "  • pedido (PK): {$bodegaPrimera->pedido}\n";
    echo "  • cliente: {$bodegaPrimera->cliente}\n";
    echo "  • estado: {$bodegaPrimera->estado}\n";
    echo "  • area: {$bodegaPrimera->area}\n";
    echo "  • fecha_de_creacion_de_orden: {$bodegaPrimera->fecha_de_creacion_de_orden}\n";
}

echo "\n2️⃣  TABLA PEDIDOS PRODUCCIÓN\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$produccionCount = PedidoProduccion::count();
echo "Total registros: $produccionCount\n";
$produccionPrimera = PedidoProduccion::first();

if($produccionPrimera) {
    echo "Campos principales:\n";
    echo "  • id (PK): {$produccionPrimera->id}\n";
    echo "  • numero_pedido: {$produccionPrimera->numero_pedido}\n";
    echo "  • cliente: {$produccionPrimera->cliente}\n";
    echo "  • estado: {$produccionPrimera->estado}\n";
    echo "  • area: {$produccionPrimera->area}\n";
    echo "  • fecha_de_creacion_de_orden: {$produccionPrimera->fecha_de_creacion_de_orden}\n";
}

echo "\n3️⃣  ¿CUÁL DEBERÍAS USAR EN LA VISTA DE BODEGA?\n";
echo "─────────────────────────────────────────────────────────────────────\n";

echo "❌ ACTUAL: Usando PedidoProduccion (pedidos_produccion)\n";
echo "   Campos: id, numero_pedido, cliente, estado, area, etc.\n\n";

echo "✅ CORRECTO: Debe usar TablaOriginalBodega (tabla_original_bodega)\n";
echo "   Campos: pedido, cliente, estado, area, descripcion, cantidad, etc.\n\n";

echo "4️⃣  COMPARACIÓN DE CAMPOS\n";
echo "─────────────────────────────────────────────────────────────────────\n";

$bodegaColumnas = DB::select("
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tabla_original_bodega'
    ORDER BY ORDINAL_POSITION
");

$produccionColumnas = DB::select("
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'
    ORDER BY ORDINAL_POSITION
");

$bodegaCols = array_map(fn($c) => $c->COLUMN_NAME, $bodegaColumnas);
$produccionCols = array_map(fn($c) => $c->COLUMN_NAME, $produccionColumnas);

echo "Campos SOLO en tabla_original_bodega:\n";
foreach(array_diff($bodegaCols, $produccionCols) as $col) {
    echo "  • $col\n";
}

echo "\nCampos SOLO en pedidos_produccion:\n";
foreach(array_diff($produccionCols, $bodegaCols) as $col) {
    echo "  • $col\n";
}

echo "\nCampos COMUNES:\n";
foreach(array_intersect($bodegaCols, $produccionCols) as $col) {
    echo "  • $col\n";
}

echo "\n5️⃣  PROBLEMA EN LA VISTA\n";
echo "─────────────────────────────────────────────────────────────────────\n";

echo "En resources/views/insumos/materiales/index.blade.php:\n\n";
echo "❌ INCORRECTO:\n";
echo "   \$orden->numero_pedido (campo de PedidoProduccion)\n";
echo "   \$orden->cliente\n";
echo "   \$orden->estado\n";
echo "   \$orden->area\n\n";

echo "✅ CORRECTO:\n";
echo "   \$orden->pedido (NO numero_pedido) - Campo de TablaOriginalBodega\n";
echo "   \$orden->cliente\n";
echo "   \$orden->estado\n";
echo "   \$orden->area\n";

echo "\n6️⃣  RECOMENDACIÓN\n";
echo "─────────────────────────────────────────────────────────────────────\n";

echo "El controlador InsumosController::materiales() debe:\n";
echo "  1. Cambiar de PedidoProduccion a TablaOriginalBodega\n";
echo "  2. Actualizar todas las referencias de 'numero_pedido' a 'pedido'\n";
echo "  3. Revisar filtros y búsqueda según campos correctos\n";

echo "\n═══════════════════════════════════════════════════════════════════════\n";
