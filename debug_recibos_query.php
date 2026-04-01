<?php
/**
 * Script para debuggear por qué los recibos no aparecen en /insumos/materiales
 * Uso: php artisan tinker < debug_recibos_query.php
 */

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Infrastructure\Insumos\ReadModels\RecibosCosturaReadRepository;

echo "====== ANÁLISIS DEL PEDIDO 73 Y SUS RECIBOS ======\n\n";

// 1. Buscar el pedido 73
echo "1. BUSCANDO PEDIDO 73:\n";
$pedido73 = PedidoProduccion::where('numero_pedido', 73)->first();

if (!$pedido73) {
    echo "❌ No se encontró pedido con número 73\n";
    exit(1);
}

echo "✓ Pedido encontrado:\n";
echo "  - ID: {$pedido73->id}\n";
echo "  - Número: {$pedido73->numero_pedido}\n";
echo "  - Estado: {$pedido73->estado}\n";
echo "  - Área: {$pedido73->area}\n";
echo "  - Cliente: {$pedido73->cliente}\n";
echo "  - Creado: {$pedido73->created_at}\n\n";

// 2. Buscar recibos de costura asociados
echo "2. BUSCANDO RECIBOS DE COSTURA DIRECTAMENTE:\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedido73->id)
    ->where('tipo_recibo', 'COSTURA')
    ->get();

echo "Recibos encontrados: " . $recibos->count() . "\n";
foreach ($recibos as $recibo) {
    echo "  - ID: {$recibo->id}, Consecutivo: {$recibo->consecutivo_actual}, Estado: {$recibo->estado}, Activo: {$recibo->activo}\n";
}
echo "\n";

// 3. Ejecutar la query del buildBaseQuery() paso a paso
echo "3. EJECUTANDO QUERY DEL REPOSITORY:\n";

// Primera parte: la query sin la lógica where compleja
$queryBasica = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->select(
        'consecutivos_recibos_pedidos.*',
        'pedidos_produccion.numero_pedido',
        'pedidos_produccion.estado as pedido_estado',
        'pedidos_produccion.area as pedido_area'
    );

echo "SQL después de JOIN y COSTURA/ACTIVO:\n";
echo $queryBasica->toSql() . "\n";
echo "Resultados: " . $queryBasica->count() . " recibos\n\n";

// Segunda parte: agregar la lógica de WHERE
echo "4. ANALIZANDO CONDICIONES WHERE:\n";

// Probar cada condición por separado
echo "\nCondición A - Estado = PENDIENTE_INSUMOS:\n";
$condA = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->where('pedidos_produccion.estado', 'PENDIENTE_INSUMOS');

echo "Resultados: " . $condA->count() . "\n";
if ($pedido73->estado === 'PENDIENTE_INSUMOS') {
    echo "✓ El pedido 73 cumple esta condición\n";
} else {
    echo "❌ El pedido 73 NO cumple esta condición (está en: {$pedido73->estado})\n";
}

echo "\nCondición B - Area contiene %Corte%:\n";
$condB = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->where('pedidos_produccion.area', 'LIKE', '%Corte%');

echo "Resultados: " . $condB->count() . "\n";
if (str_contains($pedido73->area ?? '', 'Corte')) {
    echo "✓ El pedido 73 cumple esta condición\n";
} else {
    echo "❌ El pedido 73 NO cumple esta condición (área: {$pedido73->area})\n";
}

echo "\nCondición C - Área contiene %Creacion%orden%:\n";
$condC = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->where('pedidos_produccion.area', 'LIKE', '%Creacion%orden%');

echo "Resultados: " . $condC->count() . "\n";

// 5. Ejecutar la query completa actual
echo "\n5. QUERY COMPLETA CON LÓGICA CORREGIDA:\n";

$repository = new RecibosCosturaReadRepository();
$queryCompleta = $repository->buildBaseQuery();

echo "SQL completo:\n";
echo $queryCompleta->toSql() . "\n";
echo "Bindings: " . json_encode($queryCompleta->getBindings()) . "\n";
echo "Resultados: " . $queryCompleta->count() . " recibos\n";

if ($queryCompleta->count() > 0) {
    echo "\n✓ QUERY FUNCIONA - Mostrando resultados:\n";
    foreach ($queryCompleta->get() as $row) {
        echo "  - Pedido: {$row->numero_pedido}, Recibo: {$row->consecutivo_actual}, Estado: {$row->pedido_estado}\n";
    }
} else {
    echo "\n❌ Query no retorna resultados\n";
    
    // 6. Verificar si el problema es PENDIENTE_SUPERVISOR
    echo "\n6. VERIFICANDO SI ESTÁ EXCLUIDA POR PENDIENTE_SUPERVISOR:\n";
    $todosPendiente = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA')
        ->where('activo', 1)
        ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
        ->where(function ($q) {
            $q->where('pedidos_produccion.estado', 'PENDIENTE_INSUMOS')
                ->orWhere('pedidos_produccion.area', 'LIKE', '%Corte%')
                ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion%orden%')
                ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion de orden%');
        })
        ->get();
    
    echo "Sin filtro PENDIENTE_SUPERVISOR: " . $todosPendiente->count() . " recibos\n";
    
    // Mostrar los estados de esos recibos
    echo "Estados encontrados:\n";
    foreach ($todosPendiente as $r) {
        echo "  - Pedido {$r->numero_pedido}: {$r->pedido_estado}\n";
    }
}

echo "\n====== FIN DEL ANÁLISIS ======\n";
