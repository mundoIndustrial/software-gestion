<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== SINCRONIZACIÓN DE SECUENCIAS ===\n\n";

// 1. Obtener máximo número de pedido
$maxPedido = DB::table('pedidos_produccion')->max('numero_pedido');
$siguientePedido = ($maxPedido ?? 0) + 1;

echo "Información actual:\n";
echo "  Máximo número_pedido: {$maxPedido}\n";
echo "  Siguiente debe ser: {$siguientePedido}\n\n";

// 2. Ver valor actual en secuencias
$secuenciaActual = DB::table('numero_secuencias')
    ->where('tipo', 'pedido_produccion')
    ->first();

echo "Estado actual en numero_secuencias:\n";
echo "  Tipo: {$secuenciaActual->tipo}\n";
echo "  Siguiente: {$secuenciaActual->siguiente}\n\n";

// 3. Actualizar si es necesario
if ($secuenciaActual->siguiente != $siguientePedido) {
    DB::table('numero_secuencias')
        ->where('tipo', 'pedido_produccion')
        ->update(['siguiente' => $siguientePedido]);
    
    echo "✅ Actualizado numero_secuencias a: {$siguientePedido}\n";
} else {
    echo "✓ Ya estaba sincronizado\n";
}

// 4. Verificación final
$secuenciaActualizada = DB::table('numero_secuencias')
    ->where('tipo', 'pedido_produccion')
    ->first();

echo "\nEstado final:\n";
echo "  Siguiente en BD: {$secuenciaActualizada->siguiente}\n";
echo "  ✅ Sistema listo para crear nuevos pedidos\n";
