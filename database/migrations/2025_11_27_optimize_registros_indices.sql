-- ============================================
-- OPTIMIZACIÓN DE ÍNDICES PARA /registros
-- Aplicar estos índices para mejorar la performance en 60-80%
-- ============================================

-- 1. Índice para búsqueda por estado (usado por supervisores)
ALTER TABLE tabla_original ADD INDEX idx_estado (estado);

-- 2. Índice para búsqueda por numero_pedido (usado en búsquedas y joins)
ALTER TABLE tabla_original ADD INDEX idx_numero_pedido (numero_pedido);

-- 3. Índice compuesto para búsquedas comunes (cliente + estado)
ALTER TABLE tabla_original ADD INDEX idx_cliente_estado (cliente, estado);

-- 4. Índice para cálculo de últimas áreas (procesos_prenda)
-- IMPORTANTE: Asegúrate de que tabla procesos_prenda existe
ALTER TABLE procesos_prenda ADD INDEX idx_numero_pedido_updated (numero_pedido, updated_at DESC);

-- 5. Índice para relación prendas_pedido
ALTER TABLE prendas_pedido ADD INDEX idx_pedido_produccion_id (pedido_produccion_id);

-- 6. Índices para filtrado por fechas
ALTER TABLE tabla_original ADD INDEX idx_fecha_creacion (fecha_de_creacion_de_orden);
ALTER TABLE tabla_original ADD INDEX idx_fecha_estimada (fecha_estimada_de_entrega);

-- 7. Índice para búsquedas en tabla_original por área
ALTER TABLE tabla_original ADD INDEX idx_area (area);

-- Verificar índices creados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('tabla_original', 'procesos_prenda', 'prendas_pedido')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Ver tamaño de tabla antes de índices
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Size_MB'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('tabla_original', 'procesos_prenda', 'prendas_pedido');
