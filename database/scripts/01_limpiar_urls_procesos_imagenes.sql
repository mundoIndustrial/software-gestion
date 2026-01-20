-- ================================================================
-- Script para limpiar URLs completas en pedidos_procesos_imagenes
-- Convierte URLs como 'http://servermi:8000/storage/...' a 'storage/...'
-- ================================================================

-- 1. Mostrar registros que contienen URLs completas
SELECT 
    id,
    ruta_webp,
    LENGTH(ruta_webp) as longitud
FROM pedidos_procesos_imagenes
WHERE ruta_webp LIKE 'http%'
LIMIT 20;

-- 2. Actualizar URLs completas a rutas relativas
-- Extrae la parte 'storage/...' de URLs como 'http://servermi:8000/storage/...'
UPDATE pedidos_procesos_imagenes
SET ruta_webp = SUBSTRING(ruta_webp, POSITION('/storage/' IN ruta_webp))
WHERE ruta_webp LIKE 'http%' AND ruta_webp LIKE '%/storage/%';

-- 3. Limpiar URLs que empiezan con /storage/ (agregar prefijo si es necesario)
UPDATE pedidos_procesos_imagenes
SET ruta_webp = SUBSTRING(ruta_webp, 2)  -- Quitar el / inicial si existe
WHERE ruta_webp LIKE '/storage/%' AND NOT ruta_webp LIKE 'storage/%';

-- 4. Verificar resultados
SELECT 
    id,
    ruta_webp,
    CASE 
        WHEN ruta_webp LIKE 'http%' THEN ' URL Completa'
        WHEN ruta_webp LIKE 'storage/%' THEN ' Ruta Relativa'
        WHEN ruta_webp LIKE '/storage/%' THEN ' Ruta con /'
        ELSE '❓ Otro formato'
    END as tipo_ruta
FROM pedidos_procesos_imagenes
ORDER BY tipo_ruta;

-- 5. Resumen de cambios
SELECT 
    CASE 
        WHEN ruta_webp LIKE 'http%' THEN ' URL Completa'
        WHEN ruta_webp LIKE 'storage/%' THEN ' Ruta Relativa'
        WHEN ruta_webp LIKE '/storage/%' THEN ' Ruta con /'
        ELSE '❓ Otro formato'
    END as tipo_ruta,
    COUNT(*) as cantidad
FROM pedidos_procesos_imagenes
GROUP BY tipo_ruta
ORDER BY cantidad DESC;
