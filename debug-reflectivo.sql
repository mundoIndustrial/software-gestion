-- ============================================
-- DEBUG SCRIPT: Verificar datos para REFLECTIVO
-- ============================================

-- 1. Ver todos los pedidos en área "costura"
SELECT 'PEDIDOS EN AREA COSTURA' as tipo, COUNT(*) as total FROM pedidos_produccion WHERE area = 'costura';
SELECT 'ESTADOS DE PEDIDOS' as tipo, estado, COUNT(*) as cantidad FROM pedidos_produccion WHERE area = 'costura' GROUP BY estado;

-- 2. Ver específicamente pedidos en PENDIENTE_INSUMOS
SELECT 'PEDIDOS EN PENDIENTE_INSUMOS' as tipo, COUNT(*) as total FROM pedidos_produccion WHERE estado = 'PENDIENTE_INSUMOS' AND area = 'costura';
SELECT pp.id, pp.numero_pedido, pp.estado FROM pedidos_produccion pp WHERE pp.estado = 'PENDIENTE_INSUMOS' AND pp.area = 'costura' LIMIT 10;

-- 3. Ver recibos activos por tipo
SELECT 'RECIBOS ACTIVOS POR TIPO' as tipo, tipo_recibo, COUNT(*) as cantidad FROM consecutivos_recibos_pedidos WHERE activo = 1 GROUP BY tipo_recibo;

-- 4. Ver recibos REFLECTIVO
SELECT 'RECIBOS REFLECTIVO' as tipo;
SELECT crp.id, crp.pedido_produccion_id, crp.tipo_recibo, crp.consecutivo_actual, pp.numero_pedido, pp.estado 
FROM consecutivos_recibos_pedidos crp
LEFT JOIN pedidos_produccion pp ON crp.pedido_produccion_id = pp.id
WHERE crp.tipo_recibo = 'REFLECTIVO' AND crp.activo = 1
LIMIT 20;

-- 5. Ver detalles de procesos APROBADOS
SELECT 'DETALLES DE PROCESOS APROBADOS POR TIPO DE RECIBO' as tipo, tipo_recibo, COUNT(*) as cantidad 
FROM pedidos_procesos_prenda_detalles 
WHERE estado = 'APROBADO' 
GROUP BY tipo_recibo;

-- 6. Ver detalles de procesos REFLECTIVO APROBADOS
SELECT 'DETALLES REFLECTIVO APROBADOS' as tipo;
SELECT pppd.id, pppd.prenda_pedido_id, pppd.tipo_recibo, pppd.estado, pp.nombre_prenda
FROM pedidos_procesos_prenda_detalles pppd
LEFT JOIN prendas_pedidos pp ON pppd.prenda_pedido_id = pp.id
WHERE pppd.tipo_recibo = 'REFLECTIVO' AND pppd.estado = 'APROBADO'
LIMIT 20;

-- 7. Ver prendas con procesos de encargado 'costura-reflectivo'
SELECT 'PRENDAS CON PROCESO COSTURA-REFLECTIVO' as tipo, COUNT(*) as total FROM procesos_prenda WHERE encargado = 'costura-reflectivo';
SELECT pp.id, pp.numero_pedido, prd.id as prenda_id, prd.nombre_prenda, pr.encargado, pr.estado_proceso
FROM procesos_prenda pr
JOIN prendas_pedidos prd ON pr.prenda_pedido_id = prd.id
JOIN pedidos_produccion pp ON pr.numero_pedido = pp.numero_pedido
WHERE pr.encargado = 'costura-reflectivo'
LIMIT 10;

-- 8. Correlación: Pedidos PENDIENTE_INSUMOS + Recibos REFLECTIVO + Detalles APROBADOS
SELECT 'CORRELACION: PENDIENTE_INSUMOS + REFLECTIVO ACTIVO + DETALLE APROBADO' as tipo;
SELECT 
    pp.id as pedido_id,
    pp.numero_pedido,
    pp.estado as pedido_estado,
    prd.id as prenda_id,
    prd.nombre_prenda,
    crp.tipo_recibo,
    pppd.estado as detalle_estado
FROM pedidos_produccion pp
LEFT JOIN prendas_pedidos prd ON pp.id = prd.pedido_produccion_id
LEFT JOIN consecutivos_recibos_pedidos crp ON pp.id = crp.pedido_produccion_id AND crp.tipo_recibo = 'REFLECTIVO'
LEFT JOIN pedidos_procesos_prenda_detalles pppd ON prd.id = pppd.prenda_pedido_id AND pppd.tipo_recibo = 'REFLECTIVO'
WHERE pp.estado = 'PENDIENTE_INSUMOS' AND pp.area = 'costura'
LIMIT 20;

-- 9. Ver roles costura-reflectivo
SELECT 'ROLES COSTURA-REFLECTIVO' as tipo;
SELECT u.id, u.name, r.name as rol FROM users u
JOIN role_user ru ON u.id = ru.user_id
JOIN roles r ON ru.role_id = r.id
WHERE r.name = 'costura-reflectivo';
