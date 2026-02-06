<?php

try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========== DIAGNÓSTICO DETALLADO ==========\n\n";
    
    // Ver los pedidos 7, 8, 9 específicamente
    echo "1. PEDIDOS ESPECÍFICOS 7, 8, 9:\n";
    $stmt = $conn->query("SELECT id, numero_pedido, estado, area FROM pedidos_produccion WHERE numero_pedido IN (7, 8, 9)");
    foreach($stmt as $row) {
        echo "   Pedido #{$row['numero_pedido']}: Estado={$row['estado']}, Area={$row['area']}, ID={$row['id']}\n";
    }
    echo "\n";
    
    // Ver cuáles son las áreas que existen
    echo "2. AREAS EXISTENTES:\n";
    $stmt = $conn->query("SELECT DISTINCT area FROM pedidos_produccion");
    foreach($stmt as $row) {
        echo "   - {$row['area']}\n";
    }
    echo "\n";
    
    // Ver estados de los pedidos con recibos REFLECTIVO
    echo "3. ESTADOS DE PEDIDOS CON RECIBOS REFLECTIVO:\n";
    $stmt = $conn->query("
        SELECT DISTINCT pp.id, pp.numero_pedido, pp.estado, pp.area
        FROM consecutivos_recibos_pedidos crp
        LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id
        WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
    ");
    foreach($stmt as $row) {
        echo "   Pedido #{$row['numero_pedido']}: Estado={$row['estado']}, Area={$row['area']}, ID={$row['id']}\n";
    }
    echo "\n";
    
    // Ver qué prendas tienen recibos REFLECTIVO
    echo "4. PRENDAS CON RECIBOS REFLECTIVO:\n";
    $stmt = $conn->query("
        SELECT crp.id, crp.prenda_id, crp.pedido_produccion_id, prd.nombre_prenda, pp.numero_pedido
        FROM consecutivos_recibos_pedidos crp
        LEFT JOIN prendas_pedidos prd ON crp.prenda_id = prd.id
        LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id
        WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
    ");
    foreach($stmt as $row) {
        echo "   Recibo: Pedido#{$row['numero_pedido']}, Prenda={$row['nombre_prenda']}\n";
    }
    echo "\n";
    
    // Verificar tabla de usuarios y roles
    echo "5. TABLAS DE USUARIOS Y ROLES:\n";
    $tables = ['users', 'roles', 'role_user', 'model_has_roles', 'model_has_permissions'];
    foreach($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "   - $table: $count registros\n";
        } catch (Exception $e) {
            echo "   - $table: NO EXISTE\n";
        }
    }
    echo "\n";
    
    // Buscar usuarios con rol
    echo "6. USUARIOS Y ROLES (usando tabla disponible):\n";
    try {
        $stmt = $conn->query("SELECT DISTINCT model_id FROM model_has_roles WHERE role_id IN (SELECT id FROM roles WHERE name = 'costura-reflectivo')");
        $usuarios = $stmt->fetchAll();
        if (count($usuarios) > 0) {
            foreach($usuarios as $u) {
                $userStmt = $conn->query("SELECT id, name FROM users WHERE id = " . $u['model_id']);
                $user = $userStmt->fetch();
                if ($user) {
                    echo "   - " . $user['name'] . " (ID: " . $user['id'] . ")\n";
                }
            }
        } else {
            echo "   Sin usuarios con rol costura-reflectivo\n";
        }
    } catch (Exception $e) {
        echo "   Error al obtener usuarios: " . $e->getMessage() . "\n";
    }
    
    echo "\n========== FIN DIAGNÓSTICO ==========\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
