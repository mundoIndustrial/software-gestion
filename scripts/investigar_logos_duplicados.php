<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=================================================\n";
echo "INVESTIGACIÓN DE PEDIDOS DE LOGO DUPLICADOS\n";
echo "=================================================\n\n";

// Buscar todos los logo_pedidos ordenados por fecha de creación
$logosPedidos = DB::table('logo_pedidos')
    ->select('id', 'numero_pedido', 'pedido_id', 'numero_pedido_cost', 'cotizacion_id', 'numero_cotizacion', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "📋 ÚLTIMOS 20 PEDIDOS DE LOGO:\n";
echo str_repeat("-", 150) . "\n";
printf("%-5s | %-15s | %-10s | %-18s | %-15s | %-18s | %-19s\n", 
    "ID", "Numero Pedido", "Pedido ID", "Numero Pedido Cost", "Cotizacion ID", "Numero Cotizacion", "Creado"
);
echo str_repeat("-", 150) . "\n";

foreach ($logosPedidos as $logo) {
    printf("%-5s | %-15s | %-10s | %-18s | %-15s | %-18s | %-19s\n",
        $logo->id,
        $logo->numero_pedido,
        $logo->pedido_id ?? 'NULL',
        $logo->numero_pedido_cost ?? 'NULL',
        $logo->cotizacion_id ?? 'NULL',
        $logo->numero_cotizacion ?? 'NULL',
        $logo->created_at
    );
}

echo "\n\n";
echo "🔍 BUSCANDO POSIBLES DUPLICADOS (misma cotización):\n";
echo str_repeat("-", 150) . "\n";

$duplicados = DB::select("
    SELECT 
        cotizacion_id,
        numero_cotizacion,
        COUNT(*) as cantidad,
        GROUP_CONCAT(id ORDER BY id) as ids,
        GROUP_CONCAT(numero_pedido ORDER BY id) as numeros_pedido,
        GROUP_CONCAT(COALESCE(numero_pedido_cost, 'NULL') ORDER BY id) as numeros_cost
    FROM logo_pedidos
    WHERE cotizacion_id IS NOT NULL
    GROUP BY cotizacion_id, numero_cotizacion
    HAVING COUNT(*) > 1
    ORDER BY cotizacion_id DESC
");

if (count($duplicados) > 0) {
    echo "⚠️  SE ENCONTRARON " . count($duplicados) . " COTIZACIONES CON MÚLTIPLES PEDIDOS DE LOGO:\n\n";
    
    foreach ($duplicados as $dup) {
        echo "Cotización ID: {$dup->cotizacion_id} ({$dup->numero_cotizacion})\n";
        echo "  - Cantidad de pedidos: {$dup->cantidad}\n";
        echo "  - IDs: {$dup->ids}\n";
        echo "  - Números de pedido: {$dup->numeros_pedido}\n";
        echo "  - Números pedido cost: {$dup->numeros_cost}\n";
        echo "\n";
    }
} else {
    echo "✅ No se encontraron duplicados\n";
}

echo "\n\n";
echo "🔍 VERIFICANDO PEDIDOS SIN numero_pedido_cost pero CON pedido_id:\n";
echo str_repeat("-", 150) . "\n";

$sinCost = DB::table('logo_pedidos')
    ->whereNotNull('pedido_id')
    ->whereNull('numero_pedido_cost')
    ->select('id', 'numero_pedido', 'pedido_id', 'numero_cotizacion', 'created_at')
    ->get();

if (count($sinCost) > 0) {
    echo "⚠️  ENCONTRADOS " . count($sinCost) . " PEDIDOS SIN numero_pedido_cost:\n\n";
    
    foreach ($sinCost as $logo) {
        echo "ID: {$logo->id} | Numero: {$logo->numero_pedido} | Pedido ID: {$logo->pedido_id} | Cotización: {$logo->numero_cotizacion}\n";
        
        // Buscar el pedido de producción asociado
        $pedidoProd = DB::table('pedidos_produccion')
            ->where('id', $logo->pedido_id)
            ->first();
        
        if ($pedidoProd) {
            echo "  ✅ Pedido Producción existe: ID={$pedidoProd->id}, Numero={$pedidoProd->numero_pedido}\n";
        } else {
            echo "  ❌ Pedido Producción NO existe para pedido_id={$logo->pedido_id}\n";
        }
        echo "\n";
    }
} else {
    echo "✅ Todos los pedidos con pedido_id tienen numero_pedido_cost\n";
}

echo "\n=================================================\n";
echo "FIN DE LA INVESTIGACIÓN\n";
echo "=================================================\n";
