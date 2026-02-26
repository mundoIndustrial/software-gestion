-- SQL para agregar campos de ocultado a pedidos_produccion
-- Ejecutar este script en lugar de la migración si prefieres

-- Agregar columnas
ALTER TABLE pedidos_produccion 
ADD COLUMN ocultado_en TIMESTAMP NULL AFTER deleted_at,
ADD COLUMN usuario_ocultado_por BIGINT UNSIGNED NULL AFTER ocultado_en;

-- Crear índice para búsquedas rápidas
ALTER TABLE pedidos_produccion 
ADD INDEX pedidos_ocultado_idx (ocultado_en);

-- Agregar foreign key para el usuario que ocultó
ALTER TABLE pedidos_produccion 
ADD CONSTRAINT fk_pedidos_usuario_ocultado_por 
FOREIGN KEY (usuario_ocultado_por) REFERENCES users(id) ON DELETE SET NULL;

-- Ver la estructura final de la tabla
DESCRIBE pedidos_produccion;
