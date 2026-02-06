<?php

try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========== REVISIÓN DETALLES PROCESOS ==========\n\n";
    
    // 1. Ver TODOS los detalles de procesos para prendas de pedidos 7, 8, 9
    echo "1. TODOS LOS DETALLES DE PROCESOS (sin filtros):\n";
    $stmt = $conn->query("
        SELECT pppd.id, pppd.prenda_pedido_id, pppd.tipo_proceso_id, pppd.estado, pppd.created_at,
               prd.nombre_prenda, pp.numero_pedido
        FROM pedidos_procesos_prenda_detalles pppd
        LEFT JOIN prendas_pedido prd ON pppd.prenda_pedido_id = prd.id
        LEFT JOIN pedidos_produccion pp ON prd.pedido_produccion_id = pp.id
        WHERE pp.numero_pedido IN (7, 8, 9)
        ORDER BY pp.numero_pedido, pppd.id
    ");
    $detalles = $stmt->fetchAll();
    echo "   Total encontrados: " . count($detalles) . "\n";
    foreach($detalles as $d) {
        echo "   - ID={$d['id']}, Pedido#{$d['numero_pedido']}, Prenda={$d['nombre_prenda']}, TipoProceso={$d['tipo_proceso_id']}, Estado={$d['estado']}\n";
    }
    
    echo "\n2. DETALLES CON ESTADO = 'APROBADO':\n";
    $stmt = $conn->query("
        SELECT pppd.id, pppd.prenda_pedido_id, pppd.tipo_proceso_id, pppd.estado, pppd.tipo_recibo,
               prd.nombre_prenda, pp.numero_pedido
        FROM pedidos_procesos_prenda_detalles pppd
        LEFT JOIN prendas_pedido prd ON pppd.prenda_pedido_id = prd.id
        LEFT JOIN pedidos_produccion pp ON prd.pedido_produccion_id = pp.id
        WHERE pp.numero_pedido IN (7, 8, 9) AND pppd.estado = 'APROBADO'
    ");
    $aprobados = $stmt->fetchAll();
    echo "   Total: " . count($aprobados) . "\n";
    foreach($aprobados as $a) {
        echo "   - Pedido#{$a['numero_pedido']}, Prenda={$a['nombre_prenda']}, TipoProceso={$a['tipo_proceso_id']}, TipoRecibo={$a['tipo_recibo']}, Estado={$a['estado']}\n";
    }
    
    echo "\n3. ESTADOS DISPONIBLES:\n";
    $stmt = $conn->query("
        SELECT DISTINCT estado, COUNT(*) as cantidad
        FROM pedidos_procesos_prenda_detalles
        WHERE prenda_pedido_id IN (
            SELECT id FROM prendas_pedido WHERE pedido_produccion_id IN (7, 8, 9)
        )
        GROUP BY estado
    ");
    foreach($stmt as $row) {
        echo "   - {$row['estado']}: {$row['cantidad']}\n";
    }
    
    echo "\n4. INFORMACIÓN SOBRE TIPO_RECIBO:\n";
    $stmt = $conn->query("
        SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'pedidos_procesos_prenda_detalles' AND COLUMN_NAME = 'tipo_recibo'
    ");
    $col = $stmt->fetch();
    if ($col) {
        echo "   Tipo de columna tipo_recibo: {$col['COLUMN_TYPE']}\n";
    }
    
    echo "\n5. VALORES DE tipo_recibo ENCONTRADOS:\n";
    $stmt = $conn->query("
        SELECT DISTINCT tipo_recibo
        FROM pedidos_procesos_prenda_detalles
        WHERE prenda_pedido_id IN (
            SELECT id FROM prendas_pedido WHERE pedido_produccion_id IN (7, 8, 9)
        )
    ");
    $valores = $stmt->fetchAll();
    echo "   Total: " . count($valores) . "\n";
    foreach($valores as $v) {
        echo "   - '{$v['tipo_recibo']}'\n";
    }
    
    echo "\n6. PRENDAS DE LOS PEDIDOS 7, 8, 9:\n";
    $stmt = $conn->query("
        SELECT prd.id, prd.nombre_prenda, pp.numero_pedido
        FROM prendas_pedido prd
        LEFT JOIN pedidos_produccion pp ON prd.pedido_produccion_id = pp.id
        WHERE pp.numero_pedido IN (7, 8, 9)
    ");
    foreach($stmt as $row) {
        echo "   Pedido#{$row['numero_pedido']}: Prenda ID={$row['id']}, Nombre={$row['nombre_prenda']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
