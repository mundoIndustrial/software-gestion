-- Agregar el rol supervisor_planta a la base de datos

INSERT INTO roles (name, description, created_at, updated_at) 
VALUES ('supervisor_planta', 'Supervisor de Planta - Gestión de órdenes, entregas, tableros, balanceo, vistas e insumos', NOW(), NOW());

-- Verificar que se agregó correctamente
SELECT * FROM roles WHERE name = 'supervisor_planta';

-- Para asignar el rol a un usuario, usa:
-- UPDATE users SET roles_ids = '[ID_DEL_ROL_supervisor_planta]' WHERE id = USER_ID;
-- Ejemplo: UPDATE users SET roles_ids = '[3]' WHERE id = 1;
