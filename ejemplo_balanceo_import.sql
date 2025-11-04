-- ===============================================
-- 👕 IMPORTACIÓN: JEANS CABALLERO EJEMPLO
-- ===============================================
-- Generado automáticamente desde: ejemplo_balanceo.csv
-- Fecha: 2025-11-04 15:42:47
-- SAM Total: 628.3
-- Operaciones: 28
-- ===============================================

-- 1️⃣ Insertar la prenda
INSERT INTO prendas (nombre, descripcion, referencia, tipo, activo, created_at, updated_at)
SELECT nombre, descripcion, referencia, tipo, activo, created_at, updated_at
FROM (
    SELECT
        'JEANS CABALLERO EJEMPLO' AS nombre,
        'JEANS CABALLERO EJEMPLO' AS descripcion,
        'REF-JEANCAB-EJEMPLO' AS referencia,
        'jean' AS tipo,
        1 AS activo,
        NOW() AS created_at,
        NOW() AS updated_at
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM prendas WHERE referencia = 'REF-JEANCAB-EJEMPLO'
);

-- 2️⃣ Obtener el ID de la prenda
SET @prenda_id = (SELECT id FROM prendas WHERE referencia = 'REF-JEANCAB-EJEMPLO');

-- 3️⃣ Crear el balanceo
INSERT INTO balanceos (
    prenda_id, version, total_operarios, turnos, horas_por_turno,
    tiempo_disponible_horas, tiempo_disponible_segundos, sam_total,
    meta_teorica, meta_real, operario_cuello_botella, tiempo_cuello_botella,
    sam_real, meta_sugerida_85, activo, created_at, updated_at
)
VALUES (
    @prenda_id, '1.0', 10, 1, 8,
    0.0, 0.0, 0.0,
    NULL, NULL, NULL, NULL,
    NULL, NULL, 1, NOW(), NOW()
);

-- 4️⃣ Obtener el ID del balanceo
SET @balanceo_id = LAST_INSERT_ID();

-- 5️⃣ Insertar operaciones (28 operaciones)
INSERT INTO operaciones_balanceo
(balanceo_id, letra, operacion, precedencia, maquina, sam, operario, op, seccion, orden, created_at, updated_at)
VALUES
(@balanceo_id, 'A', 'Filetear aletilla', '', 'FL', 4.3, 'LEONARDO', NULL, 'DEL', 0, NOW(), NOW()),
(@balanceo_id, 'B', 'Filetear aletillon', '', 'FL', 8.9, 'LEONARDO', NULL, 'DEL', 1, NOW(), NOW()),
(@balanceo_id, 'C', 'Montar cierre a aletilla', '', 'PL', 6.5, 'EDINSON', NULL, 'DEL', 2, NOW(), NOW()),
(@balanceo_id, 'D', 'Montar cierre a aletillon colocando marquilla', '', 'PL', 9, 'EDINSON', NULL, 'DEL', 3, NOW(), NOW()),
(@balanceo_id, 'E', 'Embonar relojera', '', '2A', 6.2, 'LUIS', NULL, 'DEL', 4, NOW(), NOW()),
(@balanceo_id, 'F', 'Montar relojera a vista', '', '2A', 15.6, 'GUZMAN', NULL, 'DEL', 5, NOW(), NOW()),
(@balanceo_id, 'G', 'Embonar parche x2', '', '2A', 8.9, 'LUIS', NULL, 'TRAS', 6, NOW(), NOW()),
(@balanceo_id, 'H', 'Filetear vista x2', '', 'FL', 5.5, 'LEONARDO', NULL, 'DEL', 7, NOW(), NOW()),
(@balanceo_id, 'I', 'Montar vista a telabolsillo x2', '', 'PL', 18.9, 'FELIPE', NULL, 'DEL', 8, NOW(), NOW()),
(@balanceo_id, 'J', 'Cerrar telabolsillo x2', '', 'FL', 9.4, 'LEONARDO', NULL, 'DEL', 9, NOW(), NOW()),
(@balanceo_id, 'K', 'Pisar telabolsillo x2', '', 'PL', 14.5, 'DIEGO', NULL, 'DEL', 10, NOW(), NOW()),
(@balanceo_id, 'L', 'Parchar x2', '', '2A', 82.4, 'ALEXIS', NULL, 'TRAS', 11, NOW(), NOW()),
(@balanceo_id, 'M', 'Hacer figura de parche x2', '', '2A', 8.9, 'LUIS', NULL, 'TRAS', 12, NOW(), NOW()),
(@balanceo_id, 'N', 'Preparar revoque x2', '', 'PL', 40.3, 'FELIPE', NULL, 'DEL', 13, NOW(), NOW()),
(@balanceo_id, 'O', 'Pisar revoque x2', '', '2A', 37, 'LUIS', NULL, 'DEL', 14, NOW(), NOW()),
(@balanceo_id, 'P', 'Montar cierre a pantalón pisando', '', 'PL', 18.4, 'EDINSON', NULL, 'DEL', 15, NOW(), NOW()),
(@balanceo_id, 'Q', 'Encuadrilar x2', '', 'PL', 26.8, 'DIEGO', NULL, 'DEL', 16, NOW(), NOW()),
(@balanceo_id, 'R', 'Hacer J', '', '2A', 24.8, 'GUZMAN', NULL, 'DEL', 17, NOW(), NOW()),
(@balanceo_id, 'S', 'Encajar', '', '2A', 48.1, 'GUZMAN', NULL, 'ENS', 18, NOW(), NOW()),
(@balanceo_id, 'T', 'Cerrar entrepierna', '', 'FL', 23.4, 'LEONARDO', NULL, 'ENS', 19, NOW(), NOW()),
(@balanceo_id, 'U', 'Pegar cotilla x2', '', 'CERR', 16.5, 'ANDERSON', NULL, 'ENS', 20, NOW(), NOW()),
(@balanceo_id, 'V', 'Cerrar cola', '', 'CERR', 20.6, 'ANDERSON', NULL, 'ENS', 21, NOW(), NOW()),
(@balanceo_id, 'W', 'Hacer bota x2', '', 'PL', 37.9, 'YAIR', NULL, 'ENS', 22, NOW(), NOW()),
(@balanceo_id, 'X', 'Cerrar costados x2', '', 'CERR', 38.4, 'ANDERSON', NULL, 'ENS', 23, NOW(), NOW()),
(@balanceo_id, 'Y', 'Montar pretina poniendo pasadores', '', 'PRE', 25, 'ALEXANDRA', NULL, 'ENS', 24, NOW(), NOW()),
(@balanceo_id, 'AA', 'Hacer pasadores', '', 'COLL', 12.2, 'YAIR', NULL, 'ENS', 25, NOW(), NOW()),
(@balanceo_id, 'AB', 'Unir pretinas', '', 'PL', 17.4, 'ALEXANDRA', NULL, 'ENS', 26, NOW(), NOW()),
(@balanceo_id, 'AC', 'Hacer punta x2', '', 'PL', 42.5, 'ALEXANDRA', NULL, 'ENS', 27, NOW(), NOW());

-- 6️⃣ Calcular métricas
UPDATE balanceos b
SET b.sam_total = (
  SELECT SUM(o.sam) FROM operaciones_balanceo o WHERE o.balanceo_id = b.id
)
WHERE b.id = @balanceo_id;

-- ✅ Verificación
SELECT
  b.id AS balanceo_id,
  p.nombre AS prenda,
  ROUND(b.sam_total, 1) AS sam_total,
  COUNT(o.id) AS total_operaciones
FROM balanceos b
JOIN prendas p ON b.prenda_id = p.id
LEFT JOIN operaciones_balanceo o ON o.balanceo_id = b.id
WHERE b.id = @balanceo_id
GROUP BY b.id, p.nombre, b.sam_total;
