-- Script para corregir rutas duplicadas /storage/storage/ en la BD
-- Ejecutar en la consola de base de datos

-- 1. Corregir tabla prenda_fotos_pedido
UPDATE prenda_fotos_pedido 
SET ruta_webp = REPLACE(ruta_webp, '/storage/storage/', '/storage/')
WHERE ruta_webp LIKE '%/storage/storage/%';

UPDATE prenda_fotos_pedido 
SET ruta_original = REPLACE(ruta_original, '/storage/storage/', '/storage/')
WHERE ruta_original LIKE '%/storage/storage/%';

UPDATE prenda_fotos_pedido 
SET ruta_miniatura = REPLACE(ruta_miniatura, '/storage/storage/', '/storage/')
WHERE ruta_miniatura LIKE '%/storage/storage/%';

-- 2. Corregir tabla prenda_fotos_tela_pedido
UPDATE prenda_fotos_tela_pedido 
SET ruta_webp = REPLACE(ruta_webp, '/storage/storage/', '/storage/')
WHERE ruta_webp LIKE '%/storage/storage/%';

UPDATE prenda_fotos_tela_pedido 
SET ruta_original = REPLACE(ruta_original, '/storage/storage/', '/storage/')
WHERE ruta_original LIKE '%/storage/storage/%';

UPDATE prenda_fotos_tela_pedido 
SET ruta_miniatura = REPLACE(ruta_miniatura, '/storage/storage/', '/storage/')
WHERE ruta_miniatura LIKE '%/storage/storage/%';

-- 3. Verificar que se corrigieron
SELECT 'prenda_fotos_pedido - ruta_webp' as tabla, COUNT(*) as cantidad_con_duplicado 
FROM prenda_fotos_pedido 
WHERE ruta_webp LIKE '%/storage/storage/%'
UNION ALL
SELECT 'prenda_fotos_tela_pedido - ruta_webp', COUNT(*) 
FROM prenda_fotos_tela_pedido 
WHERE ruta_webp LIKE '%/storage/storage/%';
