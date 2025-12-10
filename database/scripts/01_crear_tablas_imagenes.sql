-- ============================================
-- SCRIPT 1: CREAR NUEVAS TABLAS DE IMÁGENES
-- ============================================
-- Fecha: 10 de Diciembre de 2025
-- Propósito: Crear tablas separadas para imágenes de telas y logos

-- ============================================
-- 1. CREAR TABLA: prenda_tela_fotos_cot
-- ============================================
-- Propósito: Almacenar fotos de telas de prendas
-- Relación: Una prenda puede tener múltiples fotos de telas

CREATE TABLE IF NOT EXISTS prenda_tela_fotos_cot (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    prenda_cot_id BIGINT UNSIGNED NOT NULL,
    ruta_original VARCHAR(500),
    ruta_webp VARCHAR(500),
    ruta_miniatura VARCHAR(500),
    orden INT DEFAULT 0,
    ancho INT,
    alto INT,
    tamaño INT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_prenda_cot_id (prenda_cot_id),
    INDEX idx_orden (orden),
    
    -- Foreign Keys
    CONSTRAINT fk_prenda_tela_fotos_prenda_cot 
        FOREIGN KEY (prenda_cot_id) 
        REFERENCES prendas_cot(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. CREAR TABLA: logo_fotos_cot
-- ============================================
-- Propósito: Almacenar fotos de logos (máximo 5)
-- Relación: Un logo puede tener múltiples fotos

CREATE TABLE IF NOT EXISTS logo_fotos_cot (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    logo_cotizacion_id BIGINT UNSIGNED NOT NULL,
    ruta_original VARCHAR(500),
    ruta_webp VARCHAR(500),
    ruta_miniatura VARCHAR(500),
    orden INT DEFAULT 0,
    ancho INT,
    alto INT,
    tamaño INT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_logo_cotizacion_id (logo_cotizacion_id),
    INDEX idx_orden (orden),
    
    -- Foreign Keys
    CONSTRAINT fk_logo_fotos_logo_cotizacion 
        FOREIGN KEY (logo_cotizacion_id) 
        REFERENCES logo_cotizaciones(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. VERIFICACIÓN
-- ============================================
-- Mostrar tablas creadas
SELECT TABLE_NAME, TABLE_ROWS, CREATION_TIME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('prenda_tela_fotos_cot', 'logo_fotos_cot')
ORDER BY CREATION_TIME DESC;

-- ============================================
-- FIN DEL SCRIPT 1
-- ============================================
