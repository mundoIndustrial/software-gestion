<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE PEDIDOS CON NÚMERO_PEDIDO ===\n\n";

// 1. Contar todos los pedidos en la BD
$totalPedidos = PedidoProduccion::count();
echo "📊 Total de pedidos en la base de datos: {$totalPedidos}\n\n";

// 2. Contar pedidos con numero_pedido (no nulos)
$pedidosConNumero = PedidoProduccion::whereNotNull('numero_pedido')->count();
echo "📈 Pedidos CON numero_pedido (no nulos): {$pedidosConNumero}\n\n";

// 3. Contar pedidos sin numero_pedido (nulos)
$pedidosSinNumero = PedidoProduccion::whereNull('numero_pedido')->count();
echo "📉 Pedidos SIN numero_pedido (nulos): {$pedidosSinNumero}\n\n";

// 4. Mostrar todos los pedidos con numero_pedido
echo "🔍 PEDIDOS CON NÚMERO_PEDIDO:\n";
echo str_repeat("=", 80) . "\n";

$pedidosConNumeroList = PedidoProduccion::whereNotNull('numero_pedido')
    ->select('id', 'numero_pedido', 'cliente', 'estado', 'created_at')
    ->orderBy('numero_pedido', 'asc')
    ->get();

foreach ($pedidosConNumeroList as $pedido) {
    echo sprintf(
        "ID: %d | N° Pedido: %d | Cliente: %-20s | Estado: %-15s | Creado: %s\n",
        $pedido->id,
        $pedido->numero_pedido,
        substr($pedido->cliente, 0, 20),
        $pedido->estado,
        $pedido->created_at->format('d/m/Y H:i')
    );
}

echo "\n" . str_repeat("=", 80) . "\n";

// 5. Mostrar pedidos sin numero_pedido
echo "❌ PEDIDOS SIN NÚMERO_PEDIDO:\n";
echo str_repeat("-", 80) . "\n";

$pedidosSinNumeroList = PedidoProduccion::whereNull('numero_pedido')
    ->select('id', 'cliente', 'estado', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($pedidosSinNumeroList as $pedido) {
    echo sprintf(
        "ID: %d | Cliente: %-20s | Estado: %-15s | Creado: %s\n",
        $pedido->id,
        substr($pedido->cliente, 0, 20),
        $pedido->estado,
        $pedido->created_at->format('d/m/Y H:i')
    );
}

echo "\n" . str_repeat("=", 80) . "\n";

// 6. Simular la consulta del RegistroOrdenExtendedQueryService (CORREGIDA)
echo "🔬 SIMULACIÓN DE CONSULTA DEL SERVICIO (CORREGIDA):\n";
echo str_repeat("-", 80) . "\n";

$query = PedidoProduccion::query()
    ->select([
        'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
        'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
        'fecha_estimada_de_entrega', 'asesor_id', 'cliente_id', 'id'
    ])
    ->whereNotNull('numero_pedido') // El filtro que agregamos
    ->where(function ($query) {
        $query
            ->whereIn('estado', [
                'Entregado', 'En Ejecución', 'No iniciado', 'Anulada',
                'Pendiente', 'PENDIENTE_SUPERVISOR' // 🆕 Estados agregados
            ])
            ->orWhere(function ($q) {
                $q->where('estado', 'PENDIENTE_INSUMOS')
                    ->whereHas('prendas', function ($prendasQuery) {
                        $prendasQuery->where('de_bodega', true);
                    });
            });
    });

$resultados = $query->get();

echo "📋 Resultados de la consulta filtrada: {$resultados->count()} pedidos\n\n";

foreach ($resultados as $pedido) {
    echo sprintf(
        "ID: %d | N° Pedido: %d | Cliente: %-20s | Estado: %-15s\n",
        $pedido->id,
        $pedido->numero_pedido,
        substr($pedido->cliente, 0, 20),
        $pedido->estado
    );
}

echo "\n" . str_repeat("=", 80) . "\n";

// 7. Verificar si hay discrepancias
echo "🚨 VERIFICACIÓN DE DISCREPANCIAS:\n";
echo str_repeat("-", 80) . "\n";

$esperados = $pedidosConNumero;
$obtenidos = $resultados->count();

echo "Pedidos esperados con numero_pedido: {$esperados}\n";
echo "Pedidos obtenidos en consulta: {$obtenidos}\n";

if ($esperados !== $obtenidos) {
    echo "⚠️  HAY DISCREPANCIA!\n";
    
    // Encontrar qué pedidos faltan
    $todosConNumero = $pedidosConNumeroList->pluck('numero_pedido')->toArray();
    $obtenidosNumeros = $resultados->pluck('numero_pedido')->toArray();
    $faltantes = array_diff($todosConNumero, $obtenidosNumeros);
    
    if (!empty($faltantes)) {
        echo "📝 Pedidos con numero_pedido que NO aparecen en la consulta:\n";
        foreach ($faltantes as $numero) {
            $pedido = $pedidosConNumeroList->firstWhere('numero_pedido', $numero);
            echo sprintf(
                "  - N° Pedido: %d | Cliente: %s | Estado: %s\n",
                $numero,
                $pedido->cliente,
                $pedido->estado
            );
        }
    }
} else {
    echo "✅ No hay discrepancias. Todos los pedidos con número aparecen en la consulta.\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "🏁 FIN DE VERIFICACIÓN\n";
