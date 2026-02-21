-- =============================================
-- Índices Corregidos para prendas_pedido_novedades_recibo
-- Nombres acortados para cumplir límite de 64 caracteres de MySQL
-- =============================================

-- Eliminar índices existentes si los hay
DROP INDEX IF EXISTS `prendas_pedido_novedades_recibo_prenda_pedido_id_numero_recibo_unique`;
DROP INDEX IF EXISTS `prendas_pedido_novedades_recibo_creado_por_unique`;
DROP INDEX IF EXISTS `prendas_pedido_novedades_recibo_editado_por_unique`;
DROP INDEX IF EXISTS `prendas_pedido_novedades_recibo_resuelto_por_unique`;

-- Crear índices con nombres cortos
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD UNIQUE KEY `idx_prenda_pedido_numero_recibo` (`prenda_pedido_id`, `numero_recibo`),
ADD UNIQUE KEY `idx_creado_por` (`creado_por`),
ADD UNIQUE KEY `idx_editado_por` (`editado_por`),
ADD UNIQUE KEY `idx_resuelto_por` (`resuelto_por`);

-- Índices de rendimiento (nombres cortos)
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD INDEX `idx_prenda_pedido_id` (`prenda_pedido_id`),
ADD INDEX `idx_numero_recibo` (`numero_recibo`),
ADD INDEX `idx_tipo_novedad` (`tipo_novedad`),
ADD INDEX `idx_estado_novedad` (`estado_novedad`),
ADD INDEX `idx_creado_por` (`creado_por`),
ADD INDEX `idx_creado_en` (`creado_en`),
ADD INDEX `idx_editado` (`editado`),
ADD INDEX `idx_editado_en` (`editado_en`),
ADD INDEX `idx_editado_por` (`editado_por`),
ADD INDEX `idx_fecha_resolucion` (`fecha_resolucion`),
ADD INDEX `idx_resuelto_por` (`resuelto_por`);

-- Foreign Keys (mantener los mismos nombres)
ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_prenda_pedido_id_foreign`
FOREIGN KEY (`prenda_pedido_id`)
REFERENCES `prendas_pedido` (`id`)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_creado_por_foreign`
FOREIGN KEY (`creado_por`)
REFERENCES `users` (`id`)
ON DELETE RESTRICT
ON UPDATE CASCADE;

ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_editado_por_foreign`
FOREIGN KEY (`editado_por`)
REFERENCES `users` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

ALTER TABLE `prendas_pedido_novedades_recibo`
ADD CONSTRAINT `prendas_pedido_novedades_recibo_resuelto_por_foreign`
FOREIGN KEY (`resuelto_por`)
REFERENCES `users` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Comentario de la tabla
ALTER TABLE `prendas_pedido_novedades_recibo`
COMMENT = 'Sistema de novedades de recibos con auditoría completa de creaciones, ediciones y resoluciones';
