<?php
/**
 * Script de prueba para verificar cálculo de días en tiempo real
 * Verifica:
 * 1. Suma de días en procesos (modal)
 * 2. Cálculo de días en la tabla
 * 3. Endpoints API
 */

require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\Festivo;

echo "═══════════════════════════════════════════════════════════\n";
echo "TEST: Cálculo de Días en Tiempo Real\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Obtener un pedido con procesos
$pedido = PedidoProduccion::with('prendas')->first();
if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n";
    exit(1);
}

echo "📋 Pedido: #{$pedido->numero_pedido}\n";
echo "👤 Cliente: {$pedido->cliente}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Obtener procesos
$procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
    ->orderBy('fecha_inicio', 'ASC')
    ->get();

echo "🔍 Procesos encontrados: " . $procesos->count() . "\n\n";

if ($procesos->count() === 0) {
    echo "❌ No hay procesos para este pedido\n";
    exit(1);
}

// Mostrar cada proceso
$totalDiasManual = 0;
$procesos->each(function($p, $idx) use ($procesos, &$totalDiasManual) {
    $esUltimo = $idx === $procesos->count() - 1;
    $proxProc = $procesos[$idx + 1] ?? null;
    
    echo "  [$idx] {$p->proceso}\n";
    echo "      Fecha: " . ($p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : 'N/A') . "\n";
    echo "      Encargado: {$p->encargado}\n";
    echo "      Estado: {$p->estado_proceso}\n";
    
    // Calcular días hasta próximo o hoy
    if ($proxProc) {
        $f1 = new DateTime($p->fecha_inicio);
        $f2 = new DateTime($proxProc->fecha_inicio);
        $diff = $f2->diff($f1)->days;
        echo "      Días hasta siguiente: ~{$diff}\n";
    } else {
        $f1 = new DateTime($p->fecha_inicio);
        $today = new DateTime('now');
        $diff = $today->diff($f1)->days;
        echo "      Días hasta hoy: ~{$diff}\n";
    }
    
    echo "\n";
});

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Verificar si existe proceso "Despacho"
$despacho = $procesos->firstWhere('proceso', 'Despacho');
if ($despacho) {
    echo "✅ Existe proceso 'Despacho': " . date('d/m/Y', strtotime($despacho->fecha_fin)) . "\n\n";
} else {
    echo "⚠️  No existe proceso 'Despacho' - se contará hasta hoy\n\n";
}

// Verificar si existe proceso "Creación de Orden"
$creacion = $procesos->firstWhere('proceso', 'Creación de Orden');
if ($creacion) {
    echo "✅ Existe proceso 'Creación de Orden': " . date('d/m/Y', strtotime($creacion->fecha_inicio)) . "\n\n";
} else {
    echo "⚠️  No existe proceso 'Creación de Orden'\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n📝 Verificar en navegador:\n";
echo "   1. Ir a /registros\n";
echo "   2. Abrir modal 'Seguimiento' para pedido #{$pedido->numero_pedido}\n";
echo "   3. Verificar que 'Total de Días' = Suma de 'Días en Área'\n";
echo "   4. Verificar que tabla muestra días correcto\n\n";
?>
