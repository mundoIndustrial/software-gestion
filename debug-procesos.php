<?php
/**
 * Script para diagnosticar el problema con procesos_prenda
 * Ejecutar: php artisan tinker < debug-procesos.php
 * O: php debug-procesos.php
 */

// Require autoload
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\DB;

echo "\n========== DIAGNOSTICO DE PROCESOS PRENDA ==========\n\n";

// 1. Verificar pedido #8
echo "1️⃣ Buscando Pedido #8:\n";
$prendas = PrendaPedido::where('numero_pedido', 8)->with('pedidoProduccion')->get();
echo "   Total prendas: " . $prendas->count() . "\n";

foreach ($prendas as $prenda) {
    echo "   - Prenda ID: {$prenda->id}, Nombre: {$prenda->nombre_prenda}\n";
    echo "     numero_pedido: {$prenda->numero_pedido}\n";
}

// 2. Verificar procesos en BD directamente
echo "\n2️⃣ Procesos en tabla procesos_prenda para numero_pedido = 8:\n";
$procesosBD = DB::table('procesos_prenda')
    ->where('numero_pedido', 8)
    ->get();
echo "   Total encontrados: " . $procesosBD->count() . "\n";
foreach ($procesosBD as $proceso) {
    echo "   - ID: {$proceso->id}\n";
    echo "     numero_pedido: {$proceso->numero_pedido}\n";
    echo "     prenda_pedido_id: {$proceso->prenda_pedido_id}\n";
    echo "     encargado: {$proceso->encargado}\n";
}

// 3. Verificar relación de modelo
echo "\n3️⃣ Probando relación procesosPrenda():\n";
$prenda = PrendaPedido::where('numero_pedido', 8)->first();
if ($prenda) {
    echo "   Prenda encontrada: {$prenda->nombre_prenda}\n";
    echo "   Valores de la prenda:\n";
    echo "     - id: {$prenda->id}\n";
    echo "     - numero_pedido: {$prenda->numero_pedido}\n";
    
    $procesos = $prenda->procesosPrenda()->get();
    echo "   Procesos cargados via relación: " . $procesos->count() . "\n";
    
    foreach ($procesos as $proceso) {
        echo "   - {$proceso->encargado}\n";
    }
}

// 4. Query SQL que genera la relación
echo "\n4️⃣ Query SQL generada por la relación:\n";
if ($prenda) {
    $query = $prenda->procesosPrenda();
    echo "   SQL: " . $query->toSql() . "\n";
    echo "   Bindings: " . json_encode($query->getBindings()) . "\n";
}

// 5. Verificar estructura tabla procesos_prenda
echo "\n5️⃣ Estructura tabla procesos_prenda:\n";
$columns = DB::select("PRAGMA table_info(procesos_prenda)");
foreach ($columns as $col) {
    echo "   - {$col->name} ({$col->type})\n";
}

// 6. Intentar query manual
echo "\n6️⃣ Query manual (numero_pedido = 8):\n";
$manualQuery = DB::table('procesos_prenda')
    ->where('numero_pedido', 8)
    ->get(['id', 'numero_pedido', 'prenda_pedido_id', 'encargado']);
echo "   Resultados: " . $manualQuery->count() . "\n";
foreach ($manualQuery as $row) {
    echo "   - {$row->encargado} (numero_pedido: {$row->numero_pedido})\n";
}

echo "\n========== FIN DIAGNOSTICO ==========\n\n";
