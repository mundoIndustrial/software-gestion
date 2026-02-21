-- =============================================
-- Table: prendas_pedido_novedades_recibo
-- Sistema de Novedades de Recibos con Auditoría (Sin Duplicados)
-- =============================================

CREATE TABLE `prendas_pedido_novedades_recibo` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `prenda_pedido_id` bigint(20) UNSIGNED NOT NULL,
    `numero_recibo` varchar(50) NOT NULL,
    `novedad_texto` text NOT NULL,
    `tipo_novedad` enum('observacion','problema','cambio','aprobacion','rechazo','correccion') NOT NULL DEFAULT 'observacion',
    `estado_novedad` enum('activa','resuelta','pendiente') NOT NULL DEFAULT 'activa',
    `notas_adicionales` text NULL,
    
    -- Campos de creación
    `creado_por` bigint(20) UNSIGNED NOT NULL,
    `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Campos de edición (auditoría)
    `editado` tinyint(1) NOT NULL DEFAULT 0,
    `editado_en` timestamp NULL DEFAULT NULL,
    `editado_por` bigint(20) UNSIGNED NULL DEFAULT NULL,
    
    -- Campos de resolución
    `fecha_resolucion` timestamp NULL DEFAULT NULL,
    `resuelto_por` bigint(20) UNSIGNED NULL DEFAULT NULL,
    
    -- Timestamps de Laravel
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_prenda_pedido_numero_recibo` (`prenda_pedido_id`, `numero_recibo`),
    UNIQUE KEY `idx_creado_por` (`creado_por`),
    UNIQUE KEY `idx_editado_por` (`editado_por`),
    UNIQUE KEY `idx_resuelto_por` (`resuelto_por`),
    
    -- Índices para rendimiento (sin duplicados)
    KEY `idx_prenda_pedido_id` (`prenda_pedido_id`),
    KEY `idx_numero_recibo` (`numero_recibo`),
    KEY `idx_tipo_novedad` (`tipo_novedad`),
    KEY `idx_estado_novedad` (`estado_novedad`),
    KEY `idx_creado_en` (`creado_en`),
    KEY `idx_editado` (`editado`),
    KEY `idx_editado_en` (`editado_en`),
    KEY `idx_editado_por` (`editado_por`),
    KEY `idx_fecha_resolucion` (`fecha_resolucion`),
    KEY `idx_resuelto_por` (`resuelto_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Foreign Keys
-- =============================================

-- Relación con prendas_pedido
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_prenda_pedido_id_foreign`
FOREIGN KEY (`prenda_pedido_id`)
REFERENCES `prendas_pedido` (`id`)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- Relación con users (creado_por)
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_creado_por_foreign`
FOREIGN KEY (`creado_por`)
REFERENCES `users` (`id`)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- Relación con users (editado_por)
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_editado_por_foreign`
FOREIGN KEY (`editado_por`)
REFERENCES `users` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Relación con users (resuelto_por)
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_resuelto_por_foreign`
FOREIGN KEY (`resuelto_por`)
REFERENCES `users` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- =============================================
-- Trigger para auditoría automática
-- =============================================

DELIMITER $$

-- Trigger para actualizar campo editado_en y editado_por cuando se actualiza el registro
CREATE TRIGGER `prendas_pedido_novedades_recibo_before_update`
BEFORE UPDATE ON `prendas_pedido_novedades_recibo`
FOR EACH ROW
BEGIN
    -- Si el texto de la novedad cambia, marcar como editado
    IF OLD.novedad_texto <> NEW.novedad_texto THEN
        SET NEW.editado = 1;
        SET NEW.editado_en = CURRENT_TIMESTAMP;
        -- El editado_por se establece en la aplicación para mayor control
    END IF;
    
    -- Si el estado cambia a resuelta, establecer fecha de resolución
    IF OLD.estado_novedad <> 'resuelta' AND NEW.estado_novedad = 'resuelta' THEN
        SET NEW.fecha_resolucion = CURRENT_TIMESTAMP;
        -- El resuelto_por se establece en la aplicación para mayor control
    END IF;
END$$

DELIMITER ;

-- =============================================
-- Datos de ejemplo (opcional)
-- =============================================

INSERT INTO `prendas_pedido_novedades_recibo` (
    `prenda_pedido_id`, 
    `numero_recibo`, 
    `novedad_texto`, 
    `tipo_novedad`, 
    `estado_novedad`, 
    `creado_por`
) VALUES 
(1, '1', 'Novedad inicial de prueba', 'observacion', 'activa', 1),
(1, '1', 'Problema detectado en costura', 'problema', 'activa', 1),
(2, '2', 'Cambio solicitado por cliente', 'cambio', 'pendiente', 2);

-- =============================================
-- Comentarios de la tabla
-- =============================================

ALTER TABLE `prendas_pedido_novedades_recibo` COMMENT = 'Sistema de novedades de recibos con auditoría completa de creaciones, ediciones y resoluciones';

-- =============================================
-- Estadísticas y mantenimiento
-- =============================================

-- Consulta para obtener novedades por usuario
SELECT 
    u.name as usuario,
    COUNT(*) as total_novedades,
    SUM(CASE WHEN pnr.editado = 1 THEN 1 ELSE 0 END) as editadas,
    SUM(CASE WHEN pnr.estado_novedad = 'resuelta' THEN 1 ELSE 0 END) as resueltas
FROM prendas_pedido_novedades_recibo pnr
JOIN users u ON pnr.creado_por = u.id
GROUP BY u.id, u.name
ORDER BY total_novedades DESC;

-- Consulta para obtener novedades editadas recientemente
SELECT 
    pnr.id,
    pnr.numero_recibo,
    pnr.novedad_texto,
    pnr.tipo_novedad,
    pnr.estado_novedad,
    pnr.creado_en,
    pnr.editado_en,
    pnr.creado_por,
    u.name as creado_por_nombre,
    e.name as editado_por_nombre
FROM prendas_pedido_novedades_recibo pnr
JOIN users u ON pnr.creado_por = u.id
LEFT JOIN users e ON pnr.editado_por = e.id
WHERE pnr.editado = 1
ORDER BY pnr.editado_en DESC;

-- Consulta para obtener todas las novedades de un recibo específico
SELECT 
    pnr.id,
    pnr.numero_recibo,
    pnr.novedad_texto,
    pnr.tipo_novedad,
    pnr.estado_novedad,
    pnr.creado_en,
    pnr.editado,
    pnr.editado_en,
    pnr.creado_por,
    u.name as creado_por_nombre,
    e.name as editado_por_nombre
FROM prendas_pedido_novedades_recibo pnr
JOIN users u ON pnr.creado_por = u.id
LEFT JOIN users e ON pnr.editado_por = e.id
WHERE pnr.numero_recibo = '1'
ORDER BY pnr.creado_en DESC;
