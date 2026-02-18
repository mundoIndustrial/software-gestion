-- Tabla para registrar el historial de entregas de prendas
CREATE TABLE `entregas_prendas` (
    `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pedido_produccion_id` bigint UNSIGNED NOT NULL,
    `numero_pedido` int UNSIGNED NOT NULL,
    `prenda_nombre` text NOT NULL,
    `talla` varchar(255) NOT NULL,
    `cantidad` int NOT NULL,
    `cliente` varchar(500) NOT NULL,
    `asesor` varchar(255) NOT NULL,
    `fecha_entrega` datetime DEFAULT NULL,
    `hora_entrega` time DEFAULT NULL,
    `usuario_entrega_id` bigint UNSIGNED DEFAULT NULL,
    `usuario_entrega_nombre` varchar(255) DEFAULT NULL,
    `observaciones_entrega` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`pedido_produccion_id`) REFERENCES `pedidos_produccion`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_entrega_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_pedido_produccion_id` (`pedido_produccion_id`),
    INDEX `idx_numero_pedido` (`numero_pedido`),
    INDEX `idx_fecha_entrega` (`fecha_entrega`),
    INDEX `idx_estado_pedido` (`estado_pedido`),
    INDEX `idx_cliente` (`cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentario sobre la tabla
ALTER TABLE `entregas_prendas` COMMENT = 'Tabla para registrar el historial de entregas de prendas cuando un pedido cambia a estado Entregado';
