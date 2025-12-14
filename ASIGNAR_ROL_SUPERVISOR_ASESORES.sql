-- ============================================
-- Script para asignar rol supervisor_asesores
-- ============================================

-- 1. Verificar que el rol exista
SELECT 'Roles disponibles:' as info;
SELECT id, name FROM roles WHERE name IN ('supervisor_asesores', 'costurero');

-- 2. Obtener el ID del rol supervisor_asesores
-- (Cambiar el email/id según el usuario que desees asignar)

-- Opción A: Asignar a usuario con email específico
UPDATE users 
SET roles_ids = JSON_ARRAY(
    (SELECT id FROM roles WHERE name = 'supervisor_asesores' LIMIT 1)
)
WHERE email = 'your-email@example.com';  -- CAMBIAR EMAIL

-- Verificar asignación
SELECT id, name, email, roles_ids FROM users WHERE email = 'your-email@example.com';

-- ============================================
-- O si conoces el ID del usuario:
-- ============================================
-- Opción B: Asignar por ID de usuario
UPDATE users 
SET roles_ids = JSON_ARRAY(
    (SELECT id FROM roles WHERE name = 'supervisor_asesores' LIMIT 1)
)
WHERE id = 1;  -- CAMBIAR 1 por el ID del usuario

-- Verificar asignación
SELECT id, name, email, roles_ids FROM users WHERE id = 1;

-- ============================================
-- Ver todos los usuarios y sus roles actuales
-- ============================================
SELECT 
    u.id,
    u.name,
    u.email,
    u.roles_ids,
    GROUP_CONCAT(r.name SEPARATOR ', ') as roles
FROM users u
LEFT JOIN roles r ON JSON_CONTAINS(u.roles_ids, JSON_ARRAY(r.id))
GROUP BY u.id
ORDER BY u.id;

-- ============================================
-- Si deseas agregar supervisor_asesores a un usuario que ya tiene otros roles:
-- ============================================
-- Primero obtén su roles_ids actual, luego usa JSON_ARRAY_APPEND
UPDATE users 
SET roles_ids = JSON_ARRAY_APPEND(
    roles_ids, 
    '$', 
    (SELECT id FROM roles WHERE name = 'supervisor_asesores' LIMIT 1)
)
WHERE id = 1;  -- CAMBIAR 1 por el ID del usuario
