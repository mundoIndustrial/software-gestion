-- 1️⃣ Insertar la prenda
INSERT INTO prendas (nombre, descripcion, referencia, tipo, activo, created_at, updated_at)
VALUES 
('JEAN CAB CON REFLECTIVO', 'JEAN CABALLERO CON DETALLE REFLECTIVO', 'REF-REFLECTIVO-001', 'pantalon', 1, NOW(), NOW());

-- 2️⃣ Obtener el ID de la prenda recién insertada
SET @prenda_id = LAST_INSERT_ID();

-- 3️⃣ Insertar el balanceo con sam_total inicial = 0
INSERT INTO balanceos (
    prenda_id, version, total_operarios, turnos, horas_por_turno, 
    tiempo_disponible_horas, tiempo_disponible_segundos, sam_total, 
    meta_teorica, meta_real, operario_cuello_botella, tiempo_cuello_botella,
    sam_real, meta_sugerida_85, activo, created_at, updated_at
) VALUES (
    @prenda_id, '1.0', 10, 1, 8.00,
    8.00, 28800, 0.0, 
    NULL, NULL, NULL, NULL,
    NULL, NULL, 1, NOW(), NOW()
);

-- 4️⃣ Obtener el ID del balanceo recién insertado
SET @balanceo_id = LAST_INSERT_ID();

-- 5️⃣ Insertar 25 operaciones (A-Y) - SIN duplicar Parchar
INSERT INTO operaciones_balanceo 
(balanceo_id, letra, operacion, precedencia, maquina, sam, operario, op, seccion, orden, created_at, updated_at)
VALUES
(@balanceo_id, 'A', 'Filetear vista x2', 'N/A', 'FL', 4.8, NULL, 'op1', 'DEL', 0, NOW(), NOW()),
(@balanceo_id, 'B', 'Filetear aletillas', 'N/A', 'FL', 4.8, NULL, 'op1', 'DEL', 1, NOW(), NOW()),
(@balanceo_id, 'C', 'Filetear aletillones', 'N/A', 'FL', 4.8, NULL, 'op1', 'DEL', 2, NOW(), NOW()),
(@balanceo_id, 'D', 'Embonar relojeras', 'N/A', '2 AG 1/4', 14.0, NULL, 'op2', 'DEL', 3, NOW(), NOW()),
(@balanceo_id, 'E', 'Montar relojera a vista', 'A-D', '2 AG 1/4', 13.3, NULL, 'op2', 'DEL', 4, NOW(), NOW()),
(@balanceo_id, 'F', 'Pegar vistas a telabolsillo x2', 'E', 'PL', 16.9, NULL, 'op3', 'DEL', 5, NOW(), NOW()),
(@balanceo_id, 'G', 'Embonar parches x2', 'N/A', '2 AG 1/4', 14.5, NULL, 'op4', 'TRAS', 6, NOW(), NOW()),
(@balanceo_id, 'H', 'Filetear telabolsillo x2', 'F', 'FL', 8.5, NULL, 'op1', 'DEL', 7, NOW(), NOW()),
(@balanceo_id, 'I', 'Pisar tela bolsillo x2', 'H', 'PL', 10.2, NULL, 'op3', 'DEL', 8, NOW(), NOW()),
(@balanceo_id, 'J', 'Pegar la vista al delantero x2', 'I', 'PL', 29.0, NULL, 'op3', 'DEL', 9, NOW(), NOW()),
(@balanceo_id, 'K', 'Reboque x2', 'J', '2 AG 1/4', 24.2, NULL, 'op2', 'DEL', 10, NOW(), NOW()),
(@balanceo_id, 'L', 'Encuadrilar x2', 'K', 'PL', 29.0, NULL, 'op5', 'DEL', 11, NOW(), NOW()),
(@balanceo_id, 'M', 'Armar cierre', 'B-C', 'PL', 29.2, NULL, 'op5', 'DEL', 12, NOW(), NOW()),
(@balanceo_id, 'N', 'Montar cierre al delantero', 'M', 'PL', 18.0, NULL, 'op5', 'DEL', 13, NOW(), NOW()),
(@balanceo_id, 'O', 'Hacer J', 'N', '2 AG 1/4', 9.9, NULL, 'op4', 'DEL', 14, NOW(), NOW()),
(@balanceo_id, 'P', 'Encajar', 'O-L', '2 AG 1/4', 38.7, NULL, 'op4', 'DEL', 15, NOW(), NOW()),
(@balanceo_id, 'Q', 'Hacer figura al parche x2', 'G', '2 AG 1/4', 4.8, NULL, 'op2', 'TRAS', 16, NOW(), NOW()),
(@balanceo_id, 'R', 'Parchar', 'Q', '2 AG 1/4', 72.6, NULL, 'op6', 'TRAS', 17, NOW(), NOW()),
-- NOTA: Solo UNA operación de Parchar con letra R
(@balanceo_id, 'S', 'Cerrar de costados', 'P-R', 'FL', 65.3, NULL, 'op8', 'ENS', 18, NOW(), NOW()),
(@balanceo_id, 'T', 'Cerrar entrepierna', 'S', 'FL', 31.5, NULL, 'op1', 'ENS', 19, NOW(), NOW()),
(@balanceo_id, 'U', 'Unir pretinas', 'N/A', 'PL', 6.1, NULL, 'op3', 'ENS', 20, NOW(), NOW()),
(@balanceo_id, 'V', 'Empretina', 'U-T', 'PRET', 38.7, NULL, 'op9', 'ENS', 21, NOW(), NOW()),
(@balanceo_id, 'W', 'Puntas', 'V', 'PL', 48.4, NULL, 'op9', 'ENS', 22, NOW(), NOW()),
(@balanceo_id, 'X', 'Botas x2', 'W', 'PL', 75.0, NULL, 'op10', 'ENS', 23, NOW(), NOW()),
(@balanceo_id, 'Y', 'Presilla', 'X', 'PRES', 72.0, NULL, 'op11', 'ENS', 24, NOW(), NOW());

-- Suma total: 757.1 segundos (25 operaciones)
