# 📊 ANÁLISIS DE TABLAS DE BASE DE DATOS

## 📈 ESTADÍSTICAS GENERALES

```
Total de tablas: 99
├── Tablas activas (CON DATOS): 71 ✅
└── Tablas vacías (SIN DATOS): 28 ⚠️
```

---

## ✅ TABLAS ACTIVAS (CON DATOS) - NO ELIMINAR

### 📁 Prendas (6 tablas)
```
✅ prendas                    (145 registros)
✅ prendas_cotizaciones       (9 registros)
✅ telas_prenda               (9 registros)
✅ variantes_prenda           (9 registros)
✅ telas                       (509 registros)
✅ tipos_prenda               (1 registros)
```

### 📁 Cotizaciones (4 tablas)
```
✅ cotizaciones               (12 registros)
✅ historial_cambios_cotizaciones (12 registros)
✅ logo_cotizaciones          (10 registros)
✅ tipos_cotizacion           (3 registros)
```

### 📁 Órdenes (5 tablas)
```
✅ pedidos_produccion         (2261 registros)
✅ prendas_pedido             (2907 registros)
✅ procesos_prenda            (13042 registros)
✅ procesos_historial         (13022 registros)
✅ entregas_pedido_costura    (2480 registros)
```

### 📁 Sistema (8 tablas)
```
✅ migrations                 (82 registros)
✅ sessions                   (5 registros)
✅ jobs                       (31 registros)
✅ cache                      (69 registros)
✅ ... y más
```

### 📁 Usuarios (2 tablas)
```
✅ users                      (60 registros)
✅ roles                      (10 registros)
```

---

## ⚠️ TABLAS VACÍAS (PUEDEN ELIMINARSE)

### 🗑️ Candidatas para Eliminación (28 tablas)

```
1. cache_locks                    (0 registros)
2. catalogo_colores               (0 registros)
3. catalogo_hilos                 (0 registros)
4. catalogo_telas                 (0 registros)
5. categorias_prendas             (0 registros)
6. failed_jobs                    (0 registros)
7. historial_cambios_pedidos      (0 registros)
8. historial_cotizaciones         (0 registros)
9. inventario_telas               (0 registros)
10. inventario_telas_historial    (0 registros)
11. job_batches                   (0 registros)
12. notifications                 (0 registros)
13. password_reset_tokens         (0 registros)
14. prenda_variaciones_disponibles (0 registros)
15. prendas_metraje               (0 registros)
16. producto_imagenes             (0 registros)
17. productos_pedido              (0 registros)
18. registros_por_orden           (0 registros)
19. reportes                      (0 registros)
20. tabla_original                (0 registros)
21. talla_metraje                 (0 registros)
22. tallas                        (0 registros)
23. tipos_prendas                 (0 registros)
... y más
```

---

## 🎯 RECOMENDACIONES

### ✅ MANTENER (Tablas activas)
- **Todas las tablas con datos**
- Especialmente las de producción y órdenes
- No eliminar sin análisis previo

### ⚠️ REVISAR ANTES DE ELIMINAR
```
historial_cotizaciones    - Vacía pero puede ser importante
registros_por_orden       - Vacía pero puede ser importante
```

### 🗑️ SEGURO ELIMINAR (Tablas vacías)
```
cache_locks
catalogo_colores
catalogo_hilos
catalogo_telas
categorias_prendas
failed_jobs
inventario_telas
inventario_telas_historial
job_batches
notifications
password_reset_tokens
prenda_variaciones_disponibles
prendas_metraje
producto_imagenes
productos_pedido
reportes
tabla_original
talla_metraje
tallas
tipos_prendas
```

---

## 📋 SCRIPT PARA ELIMINAR TABLAS VACÍAS

### Opción 1: Eliminar tablas específicas
```sql
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS catalogo_colores;
DROP TABLE IF EXISTS catalogo_hilos;
DROP TABLE IF EXISTS catalogo_telas;
DROP TABLE IF EXISTS categorias_prendas;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS historial_cambios_pedidos;
DROP TABLE IF EXISTS inventario_telas;
DROP TABLE IF EXISTS inventario_telas_historial;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS prenda_variaciones_disponibles;
DROP TABLE IF EXISTS prendas_metraje;
DROP TABLE IF EXISTS producto_imagenes;
DROP TABLE IF EXISTS productos_pedido;
DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS tabla_original;
DROP TABLE IF EXISTS talla_metraje;
DROP TABLE IF EXISTS tallas;
DROP TABLE IF EXISTS tipos_prendas;
```

### Opción 2: Crear migración de eliminación
```php
// database/migrations/2025_12_10_drop_empty_tables.php
Schema::dropIfExists('cache_locks');
Schema::dropIfExists('catalogo_colores');
Schema::dropIfExists('catalogo_hilos');
// ... etc
```

---

## ⚠️ PRECAUCIONES

1. **HACER BACKUP ANTES**
   ```bash
   mysqldump -u usuario -p nombre_bd > backup.sql
   ```

2. **VERIFICAR DEPENDENCIAS**
   - Buscar foreign keys
   - Buscar referencias en código
   - Buscar en migraciones

3. **ELIMINAR EN ORDEN**
   - Primero las tablas sin dependencias
   - Luego las que dependen de otras

4. **PROBAR EN DESARROLLO**
   - No eliminar directamente en producción
   - Probar primero en ambiente de desarrollo

---

## 🔍 CÓMO VERIFICAR DEPENDENCIAS

### Buscar referencias en código
```bash
grep -r "tabla_nombre" app/
grep -r "tabla_nombre" database/
```

### Ver foreign keys
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mundo_bd' AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

## 📊 RESUMEN

| Categoría | Cantidad | Acción |
|-----------|----------|--------|
| **Tablas activas** | 71 | ✅ MANTENER |
| **Tablas vacías** | 28 | ⚠️ REVISAR |
| **Seguro eliminar** | ~20 | 🗑️ ELIMINAR |

---

## 🚀 PRÓXIMOS PASOS

1. **Hacer backup** de la BD
2. **Revisar dependencias** de tablas vacías
3. **Crear migración** para eliminar
4. **Probar en desarrollo**
5. **Ejecutar en producción** (si todo OK)

---

**Análisis completado:** 10 de Diciembre de 2025
**Script:** `analizar_tablas_db.php`
**Estado:** ✅ LISTO PARA REVISAR

