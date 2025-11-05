-- Script para verificar datos de octubre en registro_piso_polo
-- Buscar registros perdidos o duplicados

-- 1. Contar registros por fecha en octubre
SELECT 
    fecha,
    COUNT(*) as total_registros,
    SUM(cantidad) as suma_cantidad
FROM registro_piso_polo
WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
GROUP BY fecha
ORDER BY fecha;

-- 2. Verificar registros de la HORA 8 específicamente
SELECT 
    fecha,
    hora,
    COUNT(*) as total_registros,
    SUM(cantidad) as suma_cantidad
FROM registro_piso_polo
WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
  AND hora LIKE '%08%' OR hora LIKE '%HORA 8%' OR hora LIKE '%8%'
GROUP BY fecha, hora
ORDER BY fecha;

-- 3. Ver todos los registros de octubre con hora 8
SELECT 
    id,
    fecha,
    modulo,
    orden_produccion,
    hora,
    cantidad,
    created_at,
    updated_at
FROM registro_piso_polo
WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
  AND (hora LIKE '%08%' OR hora LIKE '%HORA 8%' OR hora LIKE '%8%')
ORDER BY fecha, created_at;

-- 4. Buscar posibles duplicados
SELECT 
    fecha,
    modulo,
    orden_produccion,
    hora,
    COUNT(*) as veces_repetido,
    GROUP_CONCAT(id) as ids,
    SUM(cantidad) as suma_cantidad
FROM registro_piso_polo
WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
GROUP BY fecha, modulo, orden_produccion, hora
HAVING COUNT(*) > 1
ORDER BY fecha;

-- 5. Verificar si hay registros con fecha NULL o vacía en octubre
SELECT 
    COUNT(*) as registros_sin_fecha
FROM registro_piso_polo
WHERE (fecha IS NULL OR fecha = '')
  AND created_at >= '2024-10-01' AND created_at < '2024-11-01';

-- 6. Verificar si hay registros con orden_produccion NULL o vacía en octubre
SELECT 
    COUNT(*) as registros_sin_orden
FROM registro_piso_polo
WHERE (orden_produccion IS NULL OR orden_produccion = '')
  AND created_at >= '2024-10-01' AND created_at < '2024-11-01';

-- 7. Total general de registros en octubre
SELECT 
    COUNT(*) as total_registros_octubre,
    SUM(cantidad) as suma_total_cantidad
FROM registro_piso_polo
WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31';

-- 8. Verificar registros insertados en octubre (por created_at)
SELECT 
    DATE(created_at) as fecha_insercion,
    COUNT(*) as registros_insertados,
    SUM(cantidad) as suma_cantidad
FROM registro_piso_polo
WHERE created_at >= '2024-10-01' AND created_at < '2024-11-01'
GROUP BY DATE(created_at)
ORDER BY fecha_insercion;
