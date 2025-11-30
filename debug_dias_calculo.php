<?php
require_once 'bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\Cache;

// Limpiar todo el caché
Cache::flush();

echo "=" . str_repeat("=", 80) . "\n";
echo "DEBUG: Probando lógica de cálculo de días\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Obtener una orden en estado "No iniciado"
$orden = PedidoProduccion::where('estado', 'No iniciado')->first();

if (!$orden) {
    echo "❌ No se encontró orden en estado 'No iniciado'\n";
    exit(1);
}

echo "Orden de prueba:\n";
echo "  Número: {$orden->numero_pedido}\n";
echo "  Estado: {$orden->estado}\n";
echo "  Fecha creación: {$orden->fecha_de_creacion_de_orden}\n\n";

// Verificar procesos
$procesos = \DB::table('procesos_prenda')
    ->where('numero_pedido', $orden->numero_pedido)
    ->count();

echo "Procesos encontrados: {$procesos}\n";
if ($procesos === 0) {
    echo "✅ (Correcto: orden sin procesos)\n";
}

echo "\n";

// Calcular días
$dias = CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);

echo "Resultado del cálculo:\n";
echo "  Días calculados: {$dias}\n";

if ($dias > 0) {
    echo "✅ Cálculo correcto - Mostrando días positivos\n";
} else {
    echo "❌ Cálculo incorrecto - Mostrando 0\n";
    
    // Debug adicional
    echo "\n--- Debug adicional ---\n";
    echo "Fecha de creación: " . $orden->fecha_de_creacion_de_orden . "\n";
    echo "Hoy: " . \Carbon\Carbon::now()->format('Y-m-d H:i:s') . "\n";
    echo "Diferencia en días: " . \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->diffInDays(\Carbon\Carbon::now()) . "\n";
}

echo "\n✅ Debug completado\n";
