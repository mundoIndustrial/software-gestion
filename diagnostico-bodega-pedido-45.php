<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "====================================\n";
echo "DIAGNÓSTICO PEDIDO 45 - BODEGA\n";
echo "====================================\n\n";

// 1. Verificar PedidoProduccion
$pedido = DB::table('pedidos_produccion')
    ->where('numero_pedido', '45')
    ->first();

if ($pedido) {
    echo "✅ Pedido Producción encontrado:\n";
    echo "   ID: {$pedido->id}\n";
    echo "   Numero: {$pedido->numero_pedido}\n\n";
} else {
    echo "❌ No se encontró pedido con numero_pedido = '45'\n";
    exit;
}

// 2. Ver todos los registros en bodega_detalles_talla para este pedido
echo "====================================\n";
echo "REGISTROS EN bodega_detalles_talla\n";
echo "====================================\n\n";

$detalles = DB::table('bodega_detalles_talla')
    ->where('numero_pedido', '45')
    ->orderBy('prenda_id')
    ->orderBy('talla')
    ->get();

if ($detalles->isEmpty()) {
    echo "❌ No hay registros en bodega_detalles_talla para pedido 45\n";
} else {
    echo "Total registros: " . $detalles->count() . "\n\n";
    
    foreach ($detalles as $d) {
        echo "-----------------------------------\n";
        echo "ID: {$d->id}\n";
        echo "pedido_produccion_id: " . ($d->pedido_produccion_id ?? 'NULL') . "\n";
        echo "prenda_id: " . ($d->prenda_id ?? 'NULL') . "\n";
        echo "numero_pedido: {$d->numero_pedido}\n";
        echo "talla: {$d->talla}\n";
        echo "talla_color_id: " . ($d->talla_color_id ?? 'NULL') . "\n";
        echo "prenda_nombre: {$d->prenda_nombre}\n";
        echo "cantidad: {$d->cantidad}\n";
        echo "pendientes: " . ($d->pendientes ?? '(vacío)') . "\n";
        echo "area: " . ($d->area ?? 'NULL') . "\n";
        echo "\n";
    }
}

// 3. Buscar registros con talla M
echo "\n====================================\n";
echo "REGISTROS CON TALLA 'M'\n";
echo "====================================\n\n";

$tallasM = DB::table('bodega_detalles_talla')
    ->where('numero_pedido', '45')
    ->where('talla', 'M')
    ->get();

if ($tallasM->isEmpty()) {
    echo "❌ No hay registros con talla 'M'\n";
} else {
    foreach ($tallasM as $m) {
        echo "ID: {$m->id} | prenda_id: " . ($m->prenda_id ?? 'NULL') . " | talla_color_id: " . ($m->talla_color_id ?? 'NULL') . " | pedido_prod_id: " . ($m->pedido_produccion_id ?? 'NULL') . " | prenda: {$m->prenda_nombre}\n";
    }
}

// 4. Intentar buscar como lo hace el backend
echo "\n====================================\n";
echo "SIMULACIÓN BÚSQUEDA BACKEND\n";
echo "====================================\n\n";

echo "Buscando con:\n";
echo "- pedido_produccion_id = {$pedido->id}\n";
echo "- numero_pedido = '45'\n";
echo "- talla = 'M'\n";
echo "- talla_color_id IS NULL\n";
echo "- prenda_id = 43\n\n";

$resultado = DB::table('bodega_detalles_talla')
    ->where('pedido_produccion_id', $pedido->id)
    ->where('numero_pedido', '45')
    ->where('talla', 'M')
    ->whereNull('talla_color_id')
    ->where('prenda_id', 43)
    ->first();

if ($resultado) {
    echo "✅ REGISTRO ENCONTRADO:\n";
    echo "ID: {$resultado->id}\n";
    echo "Prenda: {$resultado->prenda_nombre}\n";
} else {
    echo "❌ REGISTRO NO ENCONTRADO\n\n";
    
    // Probar sin whereNull
    echo "Probando SIN filtro whereNull(talla_color_id):\n";
    $resultado2 = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedido->id)
        ->where('numero_pedido', '45')
        ->where('talla', 'M')
        ->where('prenda_id', 43)
        ->get();
    
    if ($resultado2->isEmpty()) {
        echo "❌ Tampoco se encontró\n";
    } else {
        echo "✅ Se encontraron " . $resultado2->count() . " registro(s):\n";
        foreach ($resultado2 as $r) {
            echo "   ID: {$r->id} | talla_color_id: " . ($r->talla_color_id ?? 'NULL') . " | prenda: {$r->prenda_nombre}\n";
        }
    }
}

echo "\n====================================\n";
echo "FIN DIAGNÓSTICO\n";
echo "====================================\n";
