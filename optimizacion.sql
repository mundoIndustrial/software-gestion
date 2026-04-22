ALTER TABLE `prendas_pedido` ADD INDEX `prendas_pedido_pedido_produccion_id_index` (`pedido_produccion_id`);

ALTER TABLE `pedido_anexos_historial` ADD INDEX `pedido_anexos_historial_pedido_produccion_id_created_at_index` (`pedido_produccion_id`, `created_at`);
