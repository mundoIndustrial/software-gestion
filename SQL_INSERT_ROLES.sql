-- ============================================
-- INSERTAR ROLES: supervisor_asesores y costurero
-- ============================================

-- Insertar rol supervisor_asesores
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at)
VALUES (
    'supervisor_asesores',
    'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    description = 'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
    requires_credentials = 1,
    updated_at = NOW();

-- Insertar rol costurero
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at)
VALUES (
    'costurero',
    'Operario de costura',
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    description = 'Operario de costura',
    requires_credentials = 0,
    updated_at = NOW();

-- ============================================
-- VERIFICAR QUE LOS ROLES SE INSERTARON
-- ============================================
SELECT id, name, description, requires_credentials 
FROM roles 
WHERE name IN ('supervisor_asesores', 'costurero')
ORDER BY name;
