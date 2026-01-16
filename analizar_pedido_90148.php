<?php
/**
 * Análisis: ¿Qué pasó con el Pedido 90148?
 * 
 * Este script analiza:
 * 1. Si el pedido fue creado
 * 2. Si tiene prendas asociadas
 * 3. Si tiene variantes
 * 4. Si tiene procesos e imágenes
 * 5. Dónde se perdieron los datos
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("═", 80) . "\n";
echo "🔍 ANÁLISIS DEL PEDIDO 90148\n";
echo str_repeat("═", 80) . "\n\n";

$numeroPedido = 90148;

// ════════════════════════════════════════════════════════════════════
// PASO 1: Verificar si existe el pedido
// ════════════════════════════════════════════════════════════════════
echo "📋 PASO 1: Verificar si existe el pedido\n";
echo str_repeat("─", 80) . "\n\n";

$pedido = DB::table('pedidos_produccion')
    ->where('numero_pedido', $numeroPedido)
    ->first();

if ($pedido) {
    echo "✅ Pedido ENCONTRADO en la BD\n";
    echo "   • ID: {$pedido->id}\n";
    echo "   • Número: {$pedido->numero_pedido}\n";
    echo "   • Cliente: {$pedido->cliente}\n";
    echo "   • Estado: {$pedido->estado}\n";
    echo "   • Cantidad Total: {$pedido->cantidad_total}\n";
    echo "   • Forma de Pago: {$pedido->forma_de_pago}\n";
    echo "   • Fecha Creación: {$pedido->fecha_de_creacion_de_orden}\n";
    $pedidoId = $pedido->id;
} else {
    echo "❌ Pedido NO ENCONTRADO en la BD\n";
    exit(1);
}

echo "\n";

// ════════════════════════════════════════════════════════════════════
// PASO 2: Verificar PRENDAS
// ════════════════════════════════════════════════════════════════════
echo "🧥 PASO 2: Verificar PRENDAS en el pedido\n";
echo str_repeat("─", 80) . "\n\n";

$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->get();

if ($prendas->count() > 0) {
    echo "✅ Prendas ENCONTRADAS: " . $prendas->count() . "\n\n";
    
    foreach ($prendas as $index => $prenda) {
        echo "   Prenda #" . ($index + 1) . " (ID: {$prenda->id})\n";
        echo "   • Nombre: {$prenda->nombre_prenda}\n";
        echo "   • Descripción: {$prenda->descripcion}\n";
        echo "   • Cantidad Talla: {$prenda->cantidad_talla}\n";
        echo "   • De Bodega: {$prenda->de_bodega}\n";
        echo "   • Género: {$prenda->genero}\n";
        echo "\n";
    }
} else {
    echo "❌ ¡NO HAY PRENDAS! Este es el problema principal\n";
    echo "   Las prendas NO se guardaron en la BD\n\n";
}

echo "\n";

// ════════════════════════════════════════════════════════════════════
// PASO 3: Verificar VARIANTES (prenda_pedido_variantes)
// ════════════════════════════════════════════════════════════════════
echo "📏 PASO 3: Verificar VARIANTES en el pedido\n";
echo str_repeat("─", 80) . "\n\n";

$variantes = DB::table('prenda_pedido_variantes')
    ->whereIn('prenda_pedido_id', $prendas->pluck('id')->toArray())
    ->get();

if ($variantes->count() > 0) {
    echo "✅ Variantes ENCONTRADAS: " . $variantes->count() . "\n\n";
    
    foreach ($variantes as $variante) {
        echo "   Variante ID {$variante->id}:\n";
        echo "   • Prenda ID: {$variante->prenda_pedido_id}\n";
        echo "   • Talla: {$variante->talla}\n";
        echo "   • Cantidad: {$variante->cantidad}\n";
        echo "   • Color ID: {$variante->color_id}\n";
        echo "   • Tela ID: {$variante->tela_id}\n";
        echo "   • Manga ID: {$variante->tipo_manga_id}\n";
        echo "   • Broche ID: {$variante->tipo_broche_boton_id}\n";
        echo "\n";
    }
} else {
    echo "❌ ¡NO HAY VARIANTES!\n";
    if ($prendas->count() === 0) {
        echo "   Razón: No hay prendas, por lo tanto no hay variantes\n";
    }
    echo "\n";
}

echo "\n";

// ════════════════════════════════════════════════════════════════════
// PASO 4: Verificar PROCESOS
// ════════════════════════════════════════════════════════════════════
echo "⚙️  PASO 4: Verificar PROCESOS en el pedido\n";
echo str_repeat("─", 80) . "\n\n";

$procesos = DB::table('pedidos_procesos_prenda_detalles')
    ->whereIn('prenda_pedido_id', $prendas->pluck('id')->toArray())
    ->get();

if ($procesos->count() > 0) {
    echo "✅ Procesos ENCONTRADOS: " . $procesos->count() . "\n\n";
    
    foreach ($procesos as $proceso) {
        echo "   Proceso ID {$proceso->id}:\n";
        echo "   • Prenda ID: {$proceso->prenda_pedido_id}\n";
        echo "   • Tipo Proceso ID: {$proceso->tipo_proceso_id}\n";
        echo "   • Ubicaciones: {$proceso->ubicaciones}\n";
        echo "   • Observaciones: {$proceso->observaciones}\n";
        echo "   • Estado: {$proceso->estado}\n";
        echo "\n";
    }
} else {
    echo "❌ ¡NO HAY PROCESOS!\n";
    echo "\n";
}

echo "\n";

// ════════════════════════════════════════════════════════════════════
// PASO 5: Verificar IMÁGENES
// ════════════════════════════════════════════════════════════════════
echo "🖼️  PASO 5: Verificar IMÁGENES en el pedido\n";
echo str_repeat("─", 80) . "\n\n";

$imagenes = DB::table('pedidos_procesos_imagenes')
    ->whereIn('proceso_id', $procesos->pluck('id')->toArray())
    ->get();

if ($imagenes->count() > 0) {
    echo "✅ Imágenes ENCONTRADAS: " . $imagenes->count() . "\n\n";
    foreach ($imagenes as $imagen) {
        echo "   Imagen ID {$imagen->id}: {$imagen->ruta_original}\n";
    }
} else {
    echo "❌ ¡NO HAY IMÁGENES!\n";
}

echo "\n";

// ════════════════════════════════════════════════════════════════════
// ANÁLISIS FINAL
// ════════════════════════════════════════════════════════════════════
echo str_repeat("═", 80) . "\n";
echo "🔎 ANÁLISIS FINAL\n";
echo str_repeat("═", 80) . "\n\n";

if ($prendas->count() === 0) {
    echo "❌ PROBLEMA IDENTIFICADO:\n\n";
    echo "El pedido 90148 fue creado correctamente, pero:\n";
    echo "   • NO se guardaron las PRENDAS en la tabla 'prendas_pedido'\n";
    echo "   • Consecuentemente, no hay variantes\n";
    echo "   • Consecuentemente, no hay procesos\n";
    echo "   • Consecuentemente, no hay imágenes\n\n";
    
    echo "📍 CAUSA PROBABLE:\n";
    echo "   El método guardarPrendasEnPedido() en PedidoPrendaService\n";
    echo "   NO está creando los registros en 'prendas_pedido'\n\n";
    
    echo "🔧 SOLUCIÓN:\n";
    echo "   Revisar el método guardarPrendaEnPedido() para verificar:\n";
    echo "   1. Si se está llamando correctamente desde guardarPrendasEnPedido()\n";
    echo "   2. Si la instancia de PedidoPrendaService se crea correctamente\n";
    echo "   3. Si hay excepciones silenciosas en el try-catch\n";
    echo "   4. Si la transacción se está completando\n";
} else {
    echo "✅ PRENDAS fueron guardadas correctamente\n";
    
    if ($variantes->count() === 0) {
        echo "⚠️  Pero NO hay variantes asociadas\n";
    } else {
        echo "✅ Variantes fueron guardadas correctamente\n";
    }
    
    if ($procesos->count() === 0) {
        echo "⚠️  Pero NO hay procesos asociados\n";
    } else {
        echo "✅ Procesos fueron guardados correctamente\n";
    }
}

echo "\n" . str_repeat("═", 80) . "\n\n";

// ════════════════════════════════════════════════════════════════════
// LOGS EN LA BD
// ════════════════════════════════════════════════════════════════════
echo "📜 VERIFICAR LOGS PARA ESTE PEDIDO:\n";
echo str_repeat("─", 80) . "\n";
echo "Ejecutar en terminal:\n";
echo "  tail -f storage/logs/laravel.log | grep -i '90148\\|crearPedido'\n\n";

// ════════════════════════════════════════════════════════════════════
// VERIFICAR DATOS ENVIADOS
// ════════════════════════════════════════════════════════════════════
echo "📤 DATOS QUE DEBIERON HABER SIDO GUARDADOS:\n";
echo str_repeat("─", 80) . "\n";
echo "Prenda:\n";
echo "  • Nombre: 'er'\n";
echo "  • Descripción: 'werwerwer'\n";
echo "  • Género: 'dama'\n";
echo "  • Cantidad Talla: {\"dama-M\": 30, \"dama-L\": 30}\n";
echo "  • Variaciones: manga, bolsillos, broche, reflectivo\n";
echo "  • Procesos: reflectivo con imágenes\n\n";

echo "📊 Estado de la BD para Pedido 90148:\n";
echo "  • Prendas: " . ($prendas->count() > 0 ? "✅ " . $prendas->count() : "❌ 0") . "\n";
echo "  • Variantes: " . ($variantes->count() > 0 ? "✅ " . $variantes->count() : "❌ 0") . "\n";
echo "  • Procesos: " . ($procesos->count() > 0 ? "✅ " . $procesos->count() : "❌ 0") . "\n";
echo "  • Imágenes: " . ($imagenes->count() > 0 ? "✅ " . $imagenes->count() : "❌ 0") . "\n";

echo "\n" . str_repeat("═", 80) . "\n";
echo "✅ ANÁLISIS COMPLETADO\n";
echo str_repeat("═", 80) . "\n\n";
