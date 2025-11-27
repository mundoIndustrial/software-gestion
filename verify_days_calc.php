<?php
/**
 * Verificación rápida de que el cálculo de días funciona
 */
require 'bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Http\Controllers\RegistroOrdenController;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════\n";
echo "TEST: Verificación de Cálculo de Días\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Obtener primer pedido con procesos
$pedido = PedidoProduccion::has('procesos')->first();

if (!$pedido) {
    echo "❌ No hay pedidos con procesos\n";
    exit(1);
}

echo "📋 Pedido: #{$pedido->numero_pedido}\n";
echo "👤 Cliente: {$pedido->cliente}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Obtener festivos
$festivos = Festivo::pluck('fecha')->toArray();
echo "📅 Festivos en BD: " . count($festivos) . "\n\n";

// Crear instancia del controlador
$controller = new RegistroOrdenController();

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calcularTotalDiasBatchConCache');
$method->setAccessible(true);

// Calcular días
$resultado = $method->invoke($controller, [$pedido], $festivos);

echo "✅ Resultado del cálculo:\n";
echo "   Pedido {$pedido->numero_pedido}: " . ($resultado[$pedido->numero_pedido] ?? 'NO CALCULADO') . " días\n\n";

// Mostrar procesos
echo "📊 Procesos del pedido:\n";
$procesos = $pedido->procesos()->orderBy('fecha_inicio')->get();
$procesos->each(function($p, $idx) {
    echo "   [$idx] {$p->proceso}\n";
    echo "       Fecha: " . ($p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : 'N/A') . "\n";
    echo "       Encargado: {$p->encargado}\n\n";
});

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════\n";
?>
