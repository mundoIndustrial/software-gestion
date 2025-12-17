-- Obtener ID del rol costurero
SELECT @roleId := id FROM roles WHERE name = 'costurero' LIMIT 1;

-- Crear usuario Costura-Reflectivo si no existe
INSERT INTO users (name, email, password, roles_ids, created_at, updated_at)
SELECT 
    'Costura-Reflectivo' as name,
    'costura-reflectivo@mundoindustrial.com' as email,
    '$2y$10$n8O2RJb/xQqpqKYFJ3n6he8JBMX5qmH3QZJxCCJ9K3BQdGLOmx8zW' as password, -- password123
    JSON_ARRAY(@roleId) as roles_ids,
    NOW() as created_at,
    NOW() as updated_at
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'costura-reflectivo@mundoindustrial.com'
);

-- Verificar que el usuario fue creado
SELECT id, name, email, roles_ids FROM users WHERE email = 'costura-reflectivo@mundoindustrial.com';
