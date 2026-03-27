-- ============================================================
-- Script para limpiar las imágenes PNG antiguas de procesos
-- Ejecutar después de las migraciones de WebP
-- ============================================================

-- 1. Mostrar cuántas imágenes PNG existen antes de eliminar
SELECT 
    COUNT(*) as cantidad_png,
    SUM(tamano) as tamano_total_bytes,
    SUM(tamano) / 1024 / 1024 as tamano_total_mb
FROM pedidos_procesos_imagenes 
WHERE tipo_mime = 'image/png' 
   OR ruta LIKE '%.png'
   OR nombre_original LIKE '%.png';

-- 2. Mostrar las imágenes PNG a eliminar
SELECT 
    id,
    ruta,
    nombre_original,
    tipo_mime,
    tamano,
    created_at
FROM pedidos_procesos_imagenes 
WHERE tipo_mime = 'image/png' 
   OR ruta LIKE '%.png'
   OR nombre_original LIKE '%.png'
ORDER BY created_at DESC;

-- 3. COMENTADO: Descomenta la siguiente línea SOLO si quieres eliminar registros
-- DELETE FROM pedidos_procesos_imagenes 
-- WHERE tipo_mime = 'image/png' 
--    OR ruta LIKE '%.png'
--    OR nombre_original LIKE '%.png';

-- 4. Verificar que las imágenes nuevas (WebP) están bien guardadas
SELECT 
    COUNT(*) as cantidad_webp,
    SUM(tamano) as tamano_total_bytes,
    SUM(tamano) / 1024 / 1024 as tamano_total_mb
FROM pedidos_procesos_imagenes 
WHERE tipo_mime = 'image/webp' 
   AND ruta LIKE '%.webp';

-- ============================================================
-- Nota: Las imágenes PNG antiguas en storage/app/procesos-imagenes/
-- también deben eliminarse manualmente del servidor:
-- 
-- Desde terminal (Unix/Linux):
--   rm -rf storage/app/procesos-imagenes/
--
-- Desde PowerShell (Windows):
--   Remove-Item -Path storage\app\procesos-imagenes -Recurse -Force
-- ============================================================
