<?php

try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========== DIAGNÓSTICO RECIBOS REFLECTIVO ==========\n\n";
    
    // 1. Recibos REFLECTIVO
    echo "1. RECIBOS REFLECTIVO ACTIVOS:\n";
    $stmt = $conn->query("
        SELECT crp.id, crp.pedido_produccion_id, crp.prenda_id, crp.tipo_recibo, crp.activo,
               pp.numero_pedido, pp.estado as pedido_estado, pp.area,
               prd.nombre_prenda
        FROM consecutivos_recibos_pedidos crp
        LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id
        LEFT JOIN prendas_pedido prd ON crp.prenda_id = prd.id
        WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
    ");
    $datos = $stmt->fetchAll();
    echo "   Total encontrados: " . count($datos) . "\n";
    foreach($datos as $row) {
        echo "   Recibo: ID={$row['id']}, Pedido#{$row['numero_pedido']}, Estado={$row['pedido_estado']}, Area={$row['area']}, Prenda={$row['nombre_prenda']}\n";
    }
    
    echo "\n2. DETALLES PROCESO APROBADOS:\n";
    $stmt = $conn->query("
        SELECT pppd.id, pppd.prenda_pedido_id, pppd.tipo_recibo, pppd.estado,
               prd.nombre_prenda, pp.numero_pedido
        FROM pedidos_procesos_prenda_detalles pppd
        LEFT JOIN prendas_pedido prd ON pppd.prenda_pedido_id = prd.id
        LEFT JOIN pedidos_produccion pp ON prd.pedido_produccion_id = pp.id
        WHERE pppd.tipo_recibo = 'REFLECTIVO' AND pppd.estado = 'APROBADO'
    ");
    $aprobados = $stmt->fetchAll();
    echo "   Total aprobados: " . count($aprobados) . "\n";
    foreach($aprobados as $row) {
        echo "   - Pedido#{$row['numero_pedido']}, Prenda={$row['nombre_prenda']}, Estado={$row['estado']}\n";
    }
    
    echo "\n3. CORRELACIÓN COMPLETA:\n";
    $stmt = $conn->query("
        SELECT 
            crp.pedido_produccion_id,
            pp.numero_pedido,
            pp.estado as pedido_estado,
            pp.area,
            crp.prenda_id,
            prd.nombre_prenda,
            pppd.estado as detalle_estado,
            pppd.tipo_recibo as detalle_tipo_recibo
        FROM consecutivos_recibos_pedidos crp
        LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id
        LEFT JOIN prendas_pedido prd ON crp.prenda_id = prd.id
        LEFT JOIN pedidos_procesos_prenda_detalles pppd ON prd.id = pppd.prenda_pedido_id 
            AND pppd.tipo_recibo = 'REFLECTIVO'
        WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
    ");
    $resultados = $stmt->fetchAll();
    foreach($resultados as $row) {
        $area_ok = strtolower($row['area']) === 'insumos' ? '✓' : '✗';
        $estado_ok = $row['pedido_estado'] === 'PENDIENTE_INSUMOS' ? '✓' : '✗';
        $aprobado_ok = $row['detalle_estado'] === 'APROBADO' ? '✓' : '✗';
        echo "   Pedido#{$row['numero_pedido']}: area={$row['area']}($area_ok), estado={$row['pedido_estado']}($estado_ok), detalle={$row['detalle_estado']}($aprobado_ok)\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
