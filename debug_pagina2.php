<?php
/**
 * Script de debugging para investigar el problema de paginación en despacho/pendientes
 * 
 * PROBLEMA: Página 2 muestra "No hay pedidos pendientes" aunque hay 13 resultados totales
 * 
 * PASOS A VERIFICAR:
 * 1. Verificar que los datos de bodega sin procesos están siendo recuperados
 * 2. Verificar que los datos de EPP están siendo recuperados
 * 3. Verificar que la paginación está funcionando correctamente
 */

// Requiere acceso a Laravel/Database
// Este script está diseñado para ser corrido en el contexto de Laravel

require_once __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;

echo "\n========== DEBUG: PAGINACIÓN DESPACHO ==========\n\n";

// Verificar datos de bodega sin procesos
echo "1. BODEGA SIN PROCESOS:\n";
echo "----\n";

$bodegaQuery = PedidoProduccion::query()
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->where('pedidos_produccion.estado', '!=', 'Anulada')
    ->where('pedidos_produccion.estado', '!=', 'Entregado')
    ->where('prendas_pedido.de_bodega', 1)
    ->whereNull('prendas_pedido.deleted_at')
    ->whereNull('pedidos_procesos_prenda_detalles.id')
    ->select('pedidos_produccion.*')
    ->distinct();

$bodegaCount = $bodegaQuery->count();
echo "Total de pedidos en bodega sin procesos: $bodegaCount\n";

// Listar algunos
$bodegaPedidos = $bodegaQuery->limit(20)->pluck('numero_pedido', 'id');
echo "IDs de pedidos: " . json_encode($bodegaPedidos->toArray()) . "\n\n";

// Verificar datos de EPP
echo "2. PENDIENTES DE EPP:\n";
echo "----\n";

$eppQuery = PedidoProduccion::query()
    ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->where('pedidos_produccion.estado', '!=', 'Anulada')
    ->where('pedidos_produccion.estado', '!=', 'Entregado')
    ->where('bodega_detalles_talla.area', 'EPP')
    ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
    ->select('pedidos_produccion.*')
    ->distinct();

$eppCount = $eppQuery->count();
echo "Total de pedidos pendientes de EPP: $eppCount\n";

// Listar algunos
$eppPedidos = $eppQuery->limit(20)->pluck('numero_pedido', 'id');
echo "IDs de pedidos: " . json_encode($eppPedidos->toArray()) . "\n\n";

// Verificar si hay pedidos duplicados (que aparezcan en ambas listas)
echo "3. PEDIDOS DUPLICADOS:\n";
echo "----\n";

$bodegaIds = $bodegaQuery->pluck('id')->toArray();
$eppIds = $eppQuery->pluck('id')->toArray();
$duplicados = array_intersect($bodegaIds, $eppIds);

echo "Cantidad de duplicados: " . count($duplicados) . "\n";
if (count($duplicados) > 0) {
    echo "IDs duplicados: " . json_encode($duplicados) . "\n";
}

echo "\n4. TOTAL COMBINADO:\n";
echo "----\n";

$totalCombinado = count(array_unique(array_merge($bodegaIds, $eppIds)));
echo "Total de pedidos únicos: $totalCombinado\n";

// Simular el slicing para página 2
echo "\n5. SIMULACIÓN DE PAGINACIÓN:\n";
echo "----\n";

$perPage = 10;
$page = 1;
$offset = ($page - 1) * $perPage;

echo "Página 1: offset=$offset, per_page=$perPage\n";
echo "Debería devolver elementos 1-10\n";

$page = 2;
$offset = ($page - 1) * $perPage;

echo "Página 2: offset=$offset, per_page=$perPage\n";
echo "Debería devolver elementos 11-13 (3 elementos)\n";

if ($totalCombinado <= $offset) {
    echo "\n⚠️ PROBLEMA DETECTADO: El offset está fuera del rango de datos!\n";
    echo "Total de datos: $totalCombinado\n";
    echo "Offset: $offset\n";
}

echo "\n========== FIN DEBUG ==========\n";
