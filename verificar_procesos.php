<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== VERIFICACIÓN DE PROCESOS 'CREACIÓN ORDEN' ===\n\n";

// 1. Contar procesos "Creación Orden"
$countCreacionOrden = DB::table('procesos_prenda')
    ->where('proceso', 'Creación Orden')
    ->count();

echo "✓ Total procesos 'Creación Orden': $countCreacionOrden\n";

// 2. Contar procesos "Creación Orden" con encargado
$countConEncargado = DB::table('procesos_prenda')
    ->where('proceso', 'Creación Orden')
    ->whereNotNull('encargado')
    ->where('encargado', '!=', '')
    ->count();

echo "✓ Procesos 'Creación Orden' con encargado: $countConEncargado\n";

// 3. Contar procesos "Creación Orden" SIN encargado
$countSinEncargado = DB::table('procesos_prenda')
    ->where('proceso', 'Creación Orden')
    ->where(function($q) {
        $q->whereNull('encargado')
          ->orWhere('encargado', '=', '');
    })
    ->count();

echo "✓ Procesos 'Creación Orden' SIN encargado: $countSinEncargado\n";

// 4. Ver ejemplos de procesos "Creación Orden"
echo "\n=== EJEMPLOS DE PROCESOS 'CREACIÓN ORDEN' ===\n";
$ejemplos = DB::table('procesos_prenda')
    ->where('proceso', 'Creación Orden')
    ->limit(10)
    ->select('numero_pedido', 'proceso', 'encargado', 'fecha_inicio', 'id')
    ->get();

foreach ($ejemplos as $proc) {
    echo "\n  Pedido: {$proc->numero_pedido}";
    echo "\n  Encargado: " . ($proc->encargado ?? '[VACÍO]');
    echo "\n  Fecha: {$proc->fecha_inicio}";
    echo "\n  ---";
}

// 5. Ver pedidos sin "Creación Orden"
echo "\n\n=== PEDIDOS SIN PROCESO 'CREACIÓN ORDEN' ===\n";
$pedidosSinCreacion = DB::table('pedidos_produccion as pp')
    ->leftJoin('procesos_prenda as proc', function($join) {
        $join->on('pp.numero_pedido', '=', 'proc.numero_pedido')
             ->where('proc.proceso', '=', 'Creación Orden');
    })
    ->whereNull('proc.id')
    ->count();

echo "Total pedidos sin 'Creación Orden': $pedidosSinCreacion\n";

// 6. Ver encargados más frecuentes
echo "\n=== ENCARGADOS MÁS FRECUENTES EN 'CREACIÓN ORDEN' ===\n";
$encargadosFreq = DB::table('procesos_prenda')
    ->where('proceso', 'Creación Orden')
    ->whereNotNull('encargado')
    ->where('encargado', '!=', '')
    ->groupBy('encargado')
    ->select('encargado', DB::raw('COUNT(*) as total'))
    ->orderBy('total', 'DESC')
    ->limit(10)
    ->get();

foreach ($encargadosFreq as $enc) {
    echo "\n  {$enc->encargado}: {$enc->total} procesos";
}

// 7. Verificar si el mapa funciona correctamente
echo "\n\n=== PRUEBA DE MAPA DE ENCARGADOS ===\n";
$numerosPrueba = [1, 2, 3, 45312, 45313];
$encargados = DB::table('procesos_prenda')
    ->whereIn('numero_pedido', $numerosPrueba)
    ->where('proceso', 'Creación Orden')
    ->select('numero_pedido', 'encargado')
    ->get();

echo "Pedidos consultados: " . implode(', ', $numerosPrueba) . "\n";
foreach ($encargados as $enc) {
    echo "  Pedido {$enc->numero_pedido}: " . ($enc->encargado ?? '[SIN ENCARGADO]') . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n\n";
?>
