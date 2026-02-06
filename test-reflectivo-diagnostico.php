<?php

// Script de diagnóstico para REFLECTIVO
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========== DIAGNÓSTICO BD - REFLECTIVO ==========\n\n";
    
    // 1. Pedidos en área costura
    echo "1. PEDIDOS EN AREA COSTURA:\n";
    $stmt = $conn->query("SELECT estado, COUNT(*) as cantidad FROM pedidos_produccion WHERE area = 'costura' GROUP BY estado");
    $totalPedidos = 0;
    foreach($stmt as $row) {
        echo "   {$row['estado']}: {$row['cantidad']}\n";
        $totalPedidos += (int)$row['cantidad'];
    }
    echo "   TOTAL: $totalPedidos\n\n";
    
    // 2. Pedidos en PENDIENTE_INSUMOS
    echo "2. PEDIDOS EN PENDIENTE_INSUMOS:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pedidos_produccion WHERE estado = 'PENDIENTE_INSUMOS' AND area = 'costura'");
    $total = $stmt->fetch()['total'];
    echo "   Total: $total\n";
    if ($total > 0) {
        $stmt = $conn->query("SELECT id, numero_pedido FROM pedidos_produccion WHERE estado = 'PENDIENTE_INSUMOS' AND area = 'costura' LIMIT 5");
        foreach($stmt as $row) {
            echo "      - Pedido #{$row['numero_pedido']} (ID: {$row['id']})\n";
        }
    }
    echo "\n";
    
    // 3. Recibos REFLECTIVO activos
    echo "3. RECIBOS REFLECTIVO ACTIVOS:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM consecutivos_recibos_pedidos WHERE tipo_recibo = 'REFLECTIVO' AND activo = 1");
    $total = $stmt->fetch()['total'];
    echo "   Total: $total\n";
    if ($total > 0) {
        $stmt = $conn->query("SELECT crp.id, crp.pedido_produccion_id, pp.numero_pedido, pp.estado FROM consecutivos_recibos_pedidos crp LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1 LIMIT 5");
        foreach($stmt as $row) {
            echo "      - Recibo REFLECTIVO para Pedido #{$row['numero_pedido']} (Estado: {$row['estado']})\n";
        }
    }
    echo "\n";
    
    // 4. Detalles de proceso REFLECTIVO aprobados
    echo "4. DETALLES PROCESO REFLECTIVO APROBADOS:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pedidos_procesos_prenda_detalles WHERE tipo_recibo = 'REFLECTIVO' AND estado = 'APROBADO'");
    $total = $stmt->fetch()['total'];
    echo "   Total: $total\n\n";
    
    // 5. Usuarios con rol costura-reflectivo
    echo "5. USUARIOS CON ROL COSTURA-REFLECTIVO:\n";
    $stmt = $conn->query("SELECT u.id, u.name FROM users u JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id WHERE r.name = 'costura-reflectivo'");
    $usuarios = $stmt->fetchAll();
    echo "   Total: " . count($usuarios) . "\n";
    if (count($usuarios) > 0) {
        foreach($usuarios as $u) {
            echo "      - {$u['name']} (ID: {$u['id']})\n";
        }
    }
    echo "\n";
    
    // 6. Correlación completa
    echo "6. DATOS QUE COINCIDEN (PENDIENTE_INSUMOS + REFLECTIVO + APROBADO):\n";
    $sql = "
        SELECT 
            pp.id as pedido_id,
            pp.numero_pedido,
            COUNT(DISTINCT prd.id) as total_prendas,
            SUM(CASE WHEN crp.id IS NOT NULL THEN 1 ELSE 0 END) as prendas_con_recibo_reflectivo,
            SUM(CASE WHEN pppd.estado = 'APROBADO' THEN 1 ELSE 0 END) as prendas_con_detalle_aprobado
        FROM pedidos_produccion pp
        LEFT JOIN prendas_pedidos prd ON pp.id = prd.pedido_produccion_id
        LEFT JOIN consecutivos_recibos_pedidos crp ON pp.id = crp.pedido_produccion_id AND crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
        LEFT JOIN pedidos_procesos_prenda_detalles pppd ON prd.id = pppd.prenda_pedido_id AND pppd.tipo_recibo = 'REFLECTIVO' AND pppd.estado = 'APROBADO'
        WHERE pp.estado = 'PENDIENTE_INSUMOS' AND pp.area = 'costura'
        GROUP BY pp.id, pp.numero_pedido
    ";
    $stmt = $conn->query($sql);
    $resultados = $stmt->fetchAll();
    echo "   Total de pedidos: " . count($resultados) . "\n";
    if (count($resultados) > 0) {
        foreach($resultados as $r) {
            echo "      - Pedido #{$r['numero_pedido']}: {$r['total_prendas']} prendas, {$r['prendas_con_recibo_reflectivo']} con recibo REFLECTIVO, {$r['prendas_con_detalle_aprobado']} con detalle APROBADO\n";
        }
    }
    echo "\n";
    
    echo "========== FIN DIAGNÓSTICO ==========\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
