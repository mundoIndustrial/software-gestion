<?php

/**
 * Script SQL para verificar procesos de pedidos reflectivo
 * Ejecuta directamente en la base de datos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

echo "\n🔍 VERIFICACIÓN DIRECTA DE PROCESOS REFLECTIVO\n";
echo str_repeat("=", 70) . "\n";

// Obtener pedidos reflectivo
$sql = <<<SQL
SELECT 
    pp.id,
    pp.numero_pedido,
    pp.cotizacion_id,
    c.numero_cotizacion,
    tc.nombre as tipo_cotizacion,
    pp.estado,
    COUNT(DISTINCT procesos.proceso) as total_procesos_distintos,
    GROUP_CONCAT(DISTINCT procesos.proceso) as procesos_list,
    GROUP_CONCAT(DISTINCT procesos.encargado) as encargados
FROM pedidos_produccion pp
LEFT JOIN cotizaciones c ON c.id = pp.cotizacion_id
LEFT JOIN tipos_cotizacion tc ON tc.id = c.tipo_cotizacion_id
LEFT JOIN procesos_prenda procesos ON procesos.numero_pedido = pp.numero_pedido
WHERE tc.nombre = 'Reflectivo'
GROUP BY pp.id, pp.numero_pedido
ORDER BY pp.created_at DESC
LIMIT 10;
SQL;

$resultados = DB::select($sql);

if (empty($resultados)) {
    echo "❌ No hay pedidos de tipo REFLECTIVO en la BD\n";
} else {
    echo "\n📦 PEDIDOS ENCONTRADOS:\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($resultados as $row) {
        echo "\n🔹 Pedido: {$row->numero_pedido}\n";
        echo "   ID: {$row->id}\n";
        echo "   Cotización: {$row->numero_cotizacion} ({$row->tipo_cotizacion})\n";
        echo "   Estado: {$row->estado}\n";
        echo "   Procesos distintos: {$row->total_procesos_distintos}\n";
        
        if ($row->procesos_list) {
            $procesos = explode(',', $row->procesos_list);
            $encargados = explode(',', $row->encargados);
            
            echo "   Procesos:\n";
            foreach ($procesos as $idx => $p) {
                $enc = trim($encargados[$idx] ?? '(Sin asignar)');
                $icon = (trim($p) === 'Costura' && trim($enc) === 'Ramiro') ? '✅' : '⚠️';
                echo "      $icon {$p} → {$enc}\n";
            }
        } else {
            echo "   ❌ SIN PROCESOS\n";
        }
    }
}

// Estadísticas
echo "\n\n📊 ESTADÍSTICAS GENERALES:\n";
echo str_repeat("-", 70) . "\n";

$stats = DB::select(<<<SQL
SELECT 
    COUNT(DISTINCT pp.id) as total_pedidos_reflectivo,
    COUNT(DISTINCT procesos.id) as total_procesos,
    SUM(CASE WHEN procesos.proceso = 'Costura' THEN 1 ELSE 0 END) as procesos_costura,
    SUM(CASE WHEN procesos.encargado = 'Ramiro' THEN 1 ELSE 0 END) as asignados_ramiro
FROM pedidos_produccion pp
LEFT JOIN cotizaciones c ON c.id = pp.cotizacion_id
LEFT JOIN tipos_cotizacion tc ON tc.id = c.tipo_cotizacion_id
LEFT JOIN procesos_prenda procesos ON procesos.numero_pedido = pp.numero_pedido
WHERE tc.nombre = 'Reflectivo';
SQL)[0];

echo "Total pedidos REFLECTIVO: {$stats->total_pedidos_reflectivo}\n";
echo "Total procesos: {$stats->total_procesos}\n";
echo "Procesos tipo COSTURA: {$stats->procesos_costura}\n";
echo "Asignados a RAMIRO: {$stats->asignados_ramiro}\n";

if ($stats->procesos_costura > 0 && $stats->asignados_ramiro === $stats->procesos_costura) {
    echo "\n✅ ¡ÉXITO! Todos los procesos Costura están asignados a Ramiro\n";
} else {
    echo "\n⚠️ Verificar: No todos los procesos tienen asignación correcta\n";
}

echo "\n";
