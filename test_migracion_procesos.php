<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== TEST: MIGRACIÓN DE PROCESOS Y CÁLCULO DE DÍAS ===\n\n";

// Obtener un pedido de ejemplo con cotización
$pedidoConCotizacion = DB::table('pedidos_produccion')
    ->whereNotNull('cotizacion_id')
    ->first();

if ($pedidoConCotizacion) {
    echo "✓ Pedido con cotización encontrado:\n";
    echo "  Número: " . $pedidoConCotizacion->numero_pedido . "\n";
    echo "  Cotización ID: " . $pedidoConCotizacion->cotizacion_id . "\n\n";
}

// Obtener un pedido sin cotización
$pedidoSinCotizacion = DB::table('pedidos_produccion')
    ->whereNull('cotizacion_id')
    ->first();

if ($pedidoSinCotizacion) {
    echo "✓ Pedido sin cotización encontrado:\n";
    echo "  Número: " . $pedidoSinCotizacion->numero_pedido . "\n";
    echo "  Cotización ID: " . $pedidoSinCotizacion->cotizacion_id . "\n\n";
}

// Obtener datos de tabla_original para un ejemplo
$pedidoOriginal = DB::table('tabla_original')
    ->limit(1)
    ->first();

if ($pedidoOriginal) {
    echo "✓ Datos de tabla_original (ejemplo):\n";
    echo "  Pedido: " . $pedidoOriginal->pedido . "\n";
    echo "  Creación de Orden: " . $pedidoOriginal->creacion_de_orden . " | Días: " . $pedidoOriginal->dias_orden . "\n";
    echo "  Insumos y Telas: " . $pedidoOriginal->insumos_y_telas . " | Días: " . $pedidoOriginal->dias_insumos . "\n";
    echo "  Corte: " . $pedidoOriginal->corte . " | Días: " . $pedidoOriginal->dias_corte . "\n";
    echo "  Bordado: " . $pedidoOriginal->bordado . " | Días: " . $pedidoOriginal->dias_bordado . "\n";
    echo "  Estampado: " . $pedidoOriginal->estampado . " | Días: " . $pedidoOriginal->dias_estampado . "\n";
    echo "  Costura: " . $pedidoOriginal->costura . " | Días: " . $pedidoOriginal->dias_costura . "\n";
    echo "  Control de Calidad: " . $pedidoOriginal->control_de_calidad . " | Días: " . $pedidoOriginal->dias_c_c . "\n";
    echo "  Entrega: " . $pedidoOriginal->entrega . "\n\n";
}

// Verificar procesos migrados
$totalProcesos = DB::table('procesos_prenda')->count();
echo "Total de procesos en BD: $totalProcesos\n";

// Verificar si hay procesos con dias_duracion
$procesosConDias = DB::table('procesos_prenda')
    ->whereNotNull('dias_duracion')
    ->count();

echo "Procesos con dias_duracion: $procesosConDias\n\n";

echo "=== FIN TEST ===\n";
?>
