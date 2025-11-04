-- Verificar los valores SAM insertados y su suma
SELECT 
    letra,
    operacion,
    sam,
    CAST(sam AS DECIMAL(10,2)) as sam_decimal
FROM operaciones_balanceo 
WHERE balanceo_id = (SELECT MAX(id) FROM balanceos)
ORDER BY letra;

-- Suma total con diferentes precisiones
SELECT 
    SUM(sam) as suma_double,
    CAST(SUM(sam) AS DECIMAL(10,2)) as suma_decimal_2,
    CAST(SUM(sam) AS DECIMAL(10,1)) as suma_decimal_1,
    ROUND(SUM(sam), 1) as suma_redondeada_1,
    ROUND(SUM(sam), 2) as suma_redondeada_2
FROM operaciones_balanceo 
WHERE balanceo_id = (SELECT MAX(id) FROM balanceos);

-- Verificar el balanceo actual
SELECT 
    id,
    prenda_id,
    sam_total,
    CAST(sam_total AS DECIMAL(10,1)) as sam_total_1_decimal
FROM balanceos 
WHERE id = (SELECT MAX(id) FROM balanceos);
