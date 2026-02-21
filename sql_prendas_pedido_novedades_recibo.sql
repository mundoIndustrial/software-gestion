-- SQL para crear la tabla prendas_pedido_novedades_recibo
-- Generado desde Laravel Migration 2026_02_20_000003_create_prendas_pedido_novedades_recibo_table

CREATE TABLE `prendas_pedido_novedades_recibo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prenda_pedido_id` bigint unsigned NOT NULL,
  `numero_recibo` varchar(50) DEFAULT NULL,
  `novedad_texto` text NOT NULL,
  `tipo_novedad` enum('observacion','problema','cambio','aprobacion','rechazo','correccion') NOT NULL DEFAULT 'observacion',
  `creado_por` bigint unsigned DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_novedad` enum('activa','resuelta','pendiente') NOT NULL DEFAULT 'activa',
  `notas_adicionales` text DEFAULT NULL,
  `fecha_resolucion` timestamp NULL DEFAULT NULL,
  `resuelto_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pp_novedades_recibo_idx` (`prenda_pedido_id`,`numero_recibo`),
  KEY `pp_novedades_tipo_estado_idx` (`tipo_novedad`,`estado_novedad`),
  KEY `pp_novedades_creado_en_idx` (`creado_en`),
  KEY `pp_novedades_creado_por_idx` (`creado_por`),
  KEY `prendas_pedido_novedades_recibo_creado_por_foreign` (`creado_por`),
  KEY `prendas_pedido_novedades_recibo_resuelto_por_foreign` (`resuelto_por`),
  CONSTRAINT `prendas_pedido_novedades_recibo_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prendas_pedido_novedades_recibo_prenda_pedido_id_foreign` FOREIGN KEY (`prenda_pedido_id`) REFERENCES `prendas_pedido` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prendas_pedido_novedades_recibo_resuelto_por_foreign` FOREIGN KEY (`resuelto_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Descripción de campos:
-- id: Primary key auto-incremental
-- prenda_pedido_id: FK a prendas_pedido (relación con la prenda específica)
-- numero_recibo: Número del recibo (ej: "1", "2", etc.)
-- novedad_texto: Texto completo de la novedad
-- tipo_novedad: Tipo de novedad (observacion, problema, cambio, aprobacion, rechazo, correccion)
-- creado_por: FK a users (usuario que registró la novedad)
-- creado_en: Timestamp automático de cuándo se creó la novedad
-- estado_novedad: Estado de la novedad (activa, resuelta, pendiente)
-- notas_adicionales: Notas complementarias opcionales
-- fecha_resolucion: Cuándo se resolvió la novedad (si aplica)
-- resuelto_por: FK a users (usuario que resolvió la novedad)
-- created_at/updated_at: Timestamps estándar de Laravel

-- Índices para optimización:
-- pp_novedades_recibo_idx: Búsqueda por prenda y número de recibo
-- pp_novedades_tipo_estado_idx: Filtrado por tipo y estado
-- pp_novedades_creado_en_idx: Ordenamiento por fecha de creación
-- pp_novedades_creado_por_idx: Búsqueda por usuario creador

-- Relaciones con integridad referencial:
-- ON DELETE CASCADE en prenda_pedido_id: Si se elimina la prenda, se eliminan sus novedades
-- ON DELETE SET NULL en creado_por/resuelto_por: Si se elimina el usuario, no se pierde la novedad
