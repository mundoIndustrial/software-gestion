-- ============================================
-- SCRIPT 3: MODIFICAR TABLAS EXISTENTES
-- ============================================
-- Fecha: 10 de Diciembre de 2025
-- Propósito: Ajustar tablas existentes para nueva estructura

-- ============================================
-- 1. MODIFICAR: prenda_fotos_cot
-- ============================================
-- Eliminar columna 'tipo' (ya no es necesaria)
-- Ahora solo maneja fotos de prendas

ALTER TABLE prenda_fotos_cot 
DROP COLUMN tipo;

-- Verificar cambio
DESCRIBE prenda_fotos_cot;

-- ============================================
-- 2. MODIFICAR: prenda_telas_cot
-- ============================================
-- Cambiar relación: de variante_prenda_cot a prenda_cot

-- Paso 1: Agregar nueva columna prenda_cot_id
ALTER TABLE prenda_telas_cot 
ADD COLUMN prenda_cot_id BIGINT UNSIGNED NULL AFTER id;

-- Paso 2: Copiar datos de variantes a prendas
-- (Obtener prenda_cot_id desde prenda_variantes_cot)
UPDATE prenda_telas_cot pt
INNER JOIN prenda_variantes_cot pv ON pt.variante_prenda_cot_id = pv.id
SET pt.prenda_cot_id = pv.prenda_cot_id;

-- Paso 3: Eliminar FK antigua
ALTER TABLE prenda_telas_cot 
DROP FOREIGN KEY prenda_telas_cot_variante_prenda_cot_id_foreign;

-- Paso 4: Eliminar columna antigua
ALTER TABLE prenda_telas_cot 
DROP COLUMN variante_prenda_cot_id;

-- Paso 5: Agregar FK nueva
ALTER TABLE prenda_telas_cot 
ADD CONSTRAINT fk_prenda_telas_prenda_cot 
FOREIGN KEY (prenda_cot_id) 
REFERENCES prendas_cot(id) 
ON DELETE CASCADE;

-- Paso 6: Hacer columna NOT NULL
ALTER TABLE prenda_telas_cot 
MODIFY COLUMN prenda_cot_id BIGINT UNSIGNED NOT NULL;

-- Verificar cambio
DESCRIBE prenda_telas_cot;

-- ============================================
-- 3. MODIFICAR: logo_cotizaciones
-- ============================================
-- Eliminar columna 'imagenes' (JSON)
-- Las imágenes ahora están en tabla separada

-- Verificar datos antes de eliminar
SELECT 
    id,
    cotizacion_id,
    JSON_LENGTH(imagenes) as cantidad_imagenes
FROM logo_cotizaciones
WHERE imagenes IS NOT NULL AND imagenes != 'null';

-- Eliminar columna (COMENTADO: descomentar después de migrar)
-- ALTER TABLE logo_cotizaciones DROP COLUMN imagenes;

-- Verificar cambio
-- DESCRIBE logo_cotizaciones;

-- ============================================
-- 4. AGREGAR ÍNDICES PARA MEJOR RENDIMIENTO
-- ============================================

-- Índice en prenda_fotos_cot
ALTER TABLE prenda_fotos_cot 
ADD INDEX idx_prenda_cot_id (prenda_cot_id),
ADD INDEX idx_orden (orden);

-- Índice en prenda_telas_cot
ALTER TABLE prenda_telas_cot 
ADD INDEX idx_prenda_cot_id (prenda_cot_id),
ADD INDEX idx_color_id (color_id),
ADD INDEX idx_tela_id (tela_id);

-- ============================================
-- 5. VERIFICACIÓN FINAL
-- ============================================
SHOW CREATE TABLE prenda_fotos_cot\G
SHOW CREATE TABLE prenda_telas_cot\G
SHOW CREATE TABLE prenda_tela_fotos_cot\G
SHOW CREATE TABLE logo_fotos_cot\G

-- ============================================
-- FIN DEL SCRIPT 3
-- ============================================
