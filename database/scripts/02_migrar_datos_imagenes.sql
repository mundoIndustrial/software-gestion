-- ============================================
-- SCRIPT 2: MIGRAR DATOS DE IMÁGENES
-- ============================================
-- Fecha: 10 de Diciembre de 2025
-- Propósito: Migrar datos existentes a nuevas tablas

-- ============================================
-- 1. MIGRAR FOTOS DE TELAS
-- ============================================
-- De: prenda_fotos_cot (donde tipo='tela')
-- A: prenda_tela_fotos_cot

INSERT INTO prenda_tela_fotos_cot (
    prenda_cot_id,
    ruta_original,
    ruta_webp,
    ruta_miniatura,
    orden,
    ancho,
    alto,
    tamaño,
    created_at,
    updated_at
)
SELECT 
    prenda_cot_id,
    ruta_original,
    ruta_webp,
    ruta_miniatura,
    orden,
    ancho,
    alto,
    tamaño,
    created_at,
    updated_at
FROM prenda_fotos_cot
WHERE tipo = 'tela'
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Verificar migración
SELECT COUNT(*) as 'Fotos de telas migradas' FROM prenda_tela_fotos_cot;

-- ============================================
-- 2. MIGRAR IMÁGENES DE LOGOS
-- ============================================
-- De: logo_cotizaciones (JSON imagenes)
-- A: logo_fotos_cot

-- Nota: Este script es más complejo porque las imágenes están en JSON
-- Se necesita procesar manualmente o usar un script PHP

-- Verificar datos JSON en logo_cotizaciones
SELECT 
    id,
    cotizacion_id,
    JSON_LENGTH(imagenes) as cantidad_imagenes,
    imagenes
FROM logo_cotizaciones
WHERE imagenes IS NOT NULL AND imagenes != 'null'
LIMIT 5;

-- ============================================
-- 3. LIMPIAR DATOS ANTIGUOS DE prenda_fotos_cot
-- ============================================
-- Eliminar fotos de telas (ya están en prenda_tela_fotos_cot)
-- COMENTADO: Descomentar después de verificar migración

-- DELETE FROM prenda_fotos_cot WHERE tipo = 'tela';

-- ============================================
-- 4. VERIFICACIÓN FINAL
-- ============================================
SELECT 
    'prenda_fotos_cot (tipo=prenda)' as tabla,
    COUNT(*) as cantidad
FROM prenda_fotos_cot
WHERE tipo = 'prenda'
UNION ALL
SELECT 
    'prenda_tela_fotos_cot' as tabla,
    COUNT(*) as cantidad
FROM prenda_tela_fotos_cot
UNION ALL
SELECT 
    'logo_fotos_cot' as tabla,
    COUNT(*) as cantidad
FROM logo_fotos_cot;

-- ============================================
-- FIN DEL SCRIPT 2
-- ============================================
