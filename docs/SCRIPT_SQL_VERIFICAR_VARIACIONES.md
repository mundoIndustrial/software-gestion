#  Script SQL para Verificar Datos de Variaciones Guardados

## Verificación 1: ¿Se guardaron las observaciones?

```sql
-- Ver la última prenda creada con sus observaciones
SELECT 
    id,
    numero_pedido,
    nombre_prenda,
    descripcion,
    manga_obs,
    bolsillos_obs,
    broche_obs,
    reflectivo_obs,
    tipo_manga_id,
    tipo_broche_id,
    tiene_bolsillos,
    tiene_reflectivo,
    de_bodega,
    created_at
FROM prendas_pedido
ORDER BY id DESC
LIMIT 5;
```

## Verificación 2: Ver prendas de un pedido específico

```sql
-- Reemplaza 'PED-2024-001' con tu número de pedido
SELECT 
    pp.id,
    pp.numero_pedido,
    pp.nombre_prenda,
    pp.descripcion,
    pp.cantidad,
    pp.cantidad_talla,
    -- VARIACIONES: Tipos
    pp.tipo_manga_id,
    pp.tipo_broche_id,
    pp.tiene_bolsillos,
    pp.tiene_reflectivo,
    -- VARIACIONES: Observaciones
    pp.manga_obs,
    pp.bolsillos_obs,
    pp.broche_obs,
    pp.reflectivo_obs,
    -- OTROS CAMPOS IMPORTANTES
    pp.de_bodega,
    pp.tela_id,
    pp.color_id,
    pp.created_at
FROM prendas_pedido pp
WHERE pp.numero_pedido = 'TU_NUMERO_PEDIDO'
ORDER BY pp.id;
```

## Verificación 3: Ver prendas con sus telas y fotos

```sql
-- Ver estructura completa de una prenda
SELECT 
    pp.id AS prenda_id,
    pp.nombre_prenda,
    pp.manga_obs,
    pp.bolsillos_obs,
    (SELECT COUNT(*) FROM prenda_fotos_pedido WHERE prenda_pedido_id = pp.id) AS cantidad_fotos_prenda,
    (SELECT COUNT(*) FROM prenda_fotos_tela_pedido WHERE prenda_pedido_id = pp.id) AS cantidad_fotos_telas,
    (SELECT COUNT(*) FROM prenda_tallas_ped WHERE prenda_id = pp.id) AS cantidad_tallas,
    pp.created_at
FROM prendas_pedido pp
WHERE pp.numero_pedido = 'TU_NUMERO_PEDIDO'
ORDER BY pp.id;
```

## Verificación 4: Ver fotos guardadas de una prenda

```sql
-- Ver si las fotos se guardaron correctamente
SELECT 
    pfp.id,
    pfp.prenda_pedido_id,
    pfp.ruta_original,
    pfp.ruta_webp,
    pfp.ruta_miniatura,
    pfp.tamaño,
    pfp.ancho,
    pfp.alto,
    pfp.orden,
    pfp.created_at
FROM prenda_fotos_pedido pfp
WHERE pfp.prenda_pedido_id IN (
    SELECT id FROM prendas_pedido 
    WHERE numero_pedido = 'TU_NUMERO_PEDIDO'
)
ORDER BY pfp.prenda_pedido_id, pfp.orden;
```

## Verificación 5: Ver procesos con imágenes

```sql
-- Ver si los procesos con imágenes se guardaron
SELECT 
    pp.id,
    pp.prenda_pedido_id,
    pp.nombre,
    pp.descripcion,
    (SELECT COUNT(*) FROM pedidos_procesos_imagenes WHERE proceso_id = pp.id) AS cantidad_imagenes,
    pp.orden,
    pp.created_at
FROM prenda_procesos pp
WHERE pp.prenda_pedido_id IN (
    SELECT id FROM prendas_pedido 
    WHERE numero_pedido = 'TU_NUMERO_PEDIDO'
)
ORDER BY pp.prenda_pedido_id, pp.orden;
```

## Verificación 6: Ver imágenes de procesos (con tamaño)

```sql
-- Ver si el tamaño de imágenes se guardó correctamente
SELECT 
    ppi.id,
    ppi.proceso_id,
    ppi.ruta_original,
    ppi.tamaño,  --  Este campo NO debe ser NULL
    ppi.ancho,
    ppi.alto,
    ppi.created_at
FROM pedidos_procesos_imagenes ppi
WHERE ppi.proceso_id IN (
    SELECT id FROM prenda_procesos
    WHERE prenda_pedido_id IN (
        SELECT id FROM prendas_pedido 
        WHERE numero_pedido = 'TU_NUMERO_PEDIDO'
    )
)
ORDER BY ppi.proceso_id;
```

## Verificación 7: Tallas de la prenda

```sql
-- Ver si las tallas con cantidades se guardaron
SELECT 
    ptp.id,
    ptp.prenda_id,
    ptp.talla_id,
    ptp.cantidad,
    ptp.created_at
FROM prenda_tallas_ped ptp
WHERE ptp.prenda_id IN (
    SELECT id FROM prendas_pedido 
    WHERE numero_pedido = 'TU_NUMERO_PEDIDO'
)
ORDER BY ptp.prenda_id, ptp.id;
```

## Verificación 8: Diagnóstico completo de un pedido

```sql
-- Ver todo sobre un pedido
SELECT 
    pp.numero_pedido,
    pp.estado_produccion,
    COUNT(DISTINCT pren.id) AS cantidad_prendas,
    COUNT(DISTINCT ptp.id) AS cantidad_tallas,
    COUNT(DISTINCT pfp.id) AS cantidad_fotos_prendas,
    COUNT(DISTINCT ppi.id) AS cantidad_imagenes_procesos,
    pp.created_at
FROM pedidos_produccion pp
LEFT JOIN prendas_pedido pren ON pp.numero_pedido = pren.numero_pedido
LEFT JOIN prenda_tallas_ped ptp ON pren.id = ptp.prenda_id
LEFT JOIN prenda_fotos_pedido pfp ON pren.id = pfp.prenda_pedido_id
LEFT JOIN prenda_procesos proc ON pren.id = proc.prenda_pedido_id
LEFT JOIN pedidos_procesos_imagenes ppi ON proc.id = ppi.proceso_id
WHERE pp.numero_pedido = 'TU_NUMERO_PEDIDO'
GROUP BY pp.numero_pedido, pp.estado_produccion, pp.created_at;
```

## Búsqueda: ¿Qué prendas tienen observaciones?

```sql
-- Encontrar prendas que tienen observaciones guardadas
SELECT 
    id,
    numero_pedido,
    nombre_prenda,
    manga_obs,
    bolsillos_obs,
    broche_obs,
    reflectivo_obs,
    created_at
FROM prendas_pedido
WHERE 
    manga_obs != '' OR
    bolsillos_obs != '' OR
    broche_obs != '' OR
    reflectivo_obs != ''
ORDER BY created_at DESC
LIMIT 20;
```

## Búsqueda: Prendas SIN observaciones (potencial problema)

```sql
-- Prendas creadas recientemente sin observaciones (¿error?)
SELECT 
    id,
    numero_pedido,
    nombre_prenda,
    manga_obs,
    bolsillos_obs,
    broche_obs,
    reflectivo_obs,
    created_at
FROM prendas_pedido
WHERE 
    (manga_obs = '' OR manga_obs IS NULL) AND
    (bolsillos_obs = '' OR bolsillos_obs IS NULL) AND
    (broche_obs = '' OR broche_obs IS NULL) AND
    (reflectivo_obs = '' OR reflectivo_obs IS NULL) AND
    created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;
```

## Verificación: ¿Existen las columnas?

```sql
-- Verificar que la tabla tenga todas las columnas necesarias
DESCRIBE prendas_pedido;

-- O consulta más específica:
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'prendas_pedido' AND COLUMN_NAME IN (
    'manga_obs', 'bolsillos_obs', 'broche_obs', 'reflectivo_obs',
    'tipo_manga_id', 'tipo_broche_id', 'tiene_bolsillos', 'tiene_reflectivo',
    'de_bodega'
)
ORDER BY ORDINAL_POSITION;
```

## Verificación: ¿Existen los campos en pedidos_procesos_imagenes?

```sql
-- Verificar que la tabla de imágenes tenga tamaño
DESCRIBE pedidos_procesos_imagenes;

-- O más específico:
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'pedidos_procesos_imagenes' AND COLUMN_NAME IN (
    'tamaño', 'ruta_original', 'ancho', 'alto'
)
ORDER BY ORDINAL_POSITION;
```

## Exportar datos completos de un pedido

```sql
-- Exportar información completa de un pedido para análisis
SELECT 
    'PEDIDO' AS tipo,
    pp.numero_pedido,
    pp.estado_produccion,
    COUNT(*) as registros
FROM pedidos_produccion pp
WHERE pp.numero_pedido = 'TU_NUMERO_PEDIDO'
GROUP BY pp.numero_pedido

UNION ALL

SELECT 
    'PRENDAS' AS tipo,
    pp.numero_pedido,
    COUNT(*) AS cantidad_prendas,
    COUNT(*) as registros
FROM prendas_pedido pp
WHERE pp.numero_pedido = 'TU_NUMERO_PEDIDO'
GROUP BY pp.numero_pedido

UNION ALL

SELECT 
    'FOTOS_PRENDA' AS tipo,
    p.numero_pedido,
    COUNT(*) AS cantidad_fotos,
    COUNT(*) as registros
FROM prenda_fotos_pedido pfp
JOIN prendas_pedido p ON pfp.prenda_pedido_id = p.id
WHERE p.numero_pedido = 'TU_NUMERO_PEDIDO'
GROUP BY p.numero_pedido;
```

---

##  Cómo usar estos scripts

1. Abre tu cliente MySQL (phpMyAdmin, MySQL Workbench, etc.)
2. Conecta a la BD `mundoindustrial`
3. Reemplaza `'TU_NUMERO_PEDIDO'` con el número real de tu pedido (ej: `'PED-2024-001'`)
4. Copia y pega cada query
5. **Anota los resultados** - ¿Qué datos faltan?

---

##  Interpretación de Resultados

| Campo | Valor Esperado | Si está NULL/vacío | Significa |
|-------|-----------------|------------------|-----------|
| `manga_obs` | "con puño" |  NULL o '' | No se guardó la observación |
| `tipo_manga_id` | 1 (o número) |  NULL | No se creó/guardó el tipo de manga |
| `cantidad_fotos_prenda` | > 0 |  0 | Las fotos no se guardaron |
| `tamaño` (en imagenes) | 12345 |  NULL | El tamaño no se registró |

---

**Guía de solución**: Si encuentras valores NULL/vacíos, revisa el script de análisis en `SCRIPT_ANALISIS_FLUJO_VARIACIONES.md`
