# Análisis de Tablas de Base de Datos - Mundo Industrial

## Resumen
- **Total de tablas**: 58
- **Tablas duplicadas**: 1
- **Tablas innecesarias**: 7
- **Tablas principales (debe mantener)**: Mínimo 8

---

## 1. TABLAS DUPLICADAS (Eliminar una)

### `tipos_prenda` ↔ `tipos_prendas`
- **Problema**: Ambas parecen representar lo mismo
- **Recomendación**: Eliminar la más antigua, mantener una sola
- **Acción**: Revisar cuál tiene datos, consolidar, eliminar la otra

---

## 2. TABLAS INNECESARIAS (Pueden eliminarse si son históricas)

| Tabla | Razón | Recomendación |
|-------|-------|---------------|
| `tabla_original` | Datos históricos/temporales | ⚠️ Verificar si contiene datos actuales |
| `tabla_original_bodega` | Datos históricos/temporales | ⚠️ Verificar si contiene datos actuales |
| `registros_por_orden` | Datos históricos/temporales | ❌ Probablemente puede eliminarse |
| `registros_por_orden_bodega` | Datos históricos/temporales | ❌ Probablemente puede eliminarse |
| `registro_piso_produccion` | Datos históricos/temporales | ❌ Probablemente puede eliminarse |
| `registro_piso_polo` | Datos históricos/temporales | ❌ Probablemente puede eliminarse |
| `registro_piso_corte` | Datos históricos/temporales | ❌ Probablemente puede eliminarse |

---

## 3. TABLAS PRINCIPALES (Mantener obligatoriamente)

| Tabla | Propósito |
|-------|-----------|
| `users` | Usuarios del sistema |
| `roles` | Roles y permisos |
| `cotizaciones` | Cotizaciones de clientes |
| `pedidos_produccion` | ⭐ **CRÍTICA** - Pedidos en producción |
| `prendas_pedido` | Prendas de cada pedido |
| `procesos_prenda` | Procesos de producción |
| `clientes` | Información de clientes |
| `prendas_cotizaciones` | Prendas en cotizaciones |

---

## 4. TABLAS AUXILIARES (Keep pero menos críticas)

- `catalogo_colores`, `catalogo_hilos`, `catalogo_telas` - Catálogos
- `categorias_prendas`, `colores_prenda`, `generos_prenda` - Atributos
- `tipos_manga`, `tipos_broche` - Configuración
- `balanceos`, `operaciones_balanceo` - Control de producción
- `inventario_telas`, `inventario_telas_historial` - Inventario
- `costós_prendas`, `producto_imagenes` - Datos de producto
- `historial_cotizaciones`, `logo_cotizaciones` - Auditoría
- `entregas_*`, `entrega_*` - Entregas
- `horas`, `maquinas`, `telas`, `tiempo_ciclos` - Configuración
- `news` - Noticias
- `reportes` - Reportes
- `telas_prenda`, `variantes_prenda`, `prenda_variaciones_disponibles` - Variaciones

---

## 5. TABLAS DEL SISTEMA (Mantener)

- `migrations` - Historial de migraciones
- `jobs`, `job_batches`, `failed_jobs` - Sistema de colas
- `cache`, `cache_locks` - Cache
- `sessions` - Sesiones
- `password_reset_tokens` - Reset de contraseña

---

## Recomendaciones

### ✅ Hacer ahora:
1. Consolidar `tipos_prenda` y `tipos_prendas` - Mantener solo una
2. Verificar referencias en migraciones a tablas innecesarias
3. Eliminar migraciones de tablas históricas que no se usen

### ⚠️ Revisar primero:
1. `tabla_original` y `tabla_original_bodega` - ¿Contienen datos importantes?
2. Si contienen datos, hacer backup antes de eliminar

### Migraciones a eliminar (si decides limpiar):
```
- 2025_09_23_152226_create_tabla_original_table.php
- 2025_10_02_151405_create_tabla_original_bodega_table.php
- 2025_09_23_152227_create_registros_por_orden_table.php
- 2025_10_05_000000_create_registros_por_orden_bodega_table.php
- 2025_10_15_150514_create_registro_piso_produccion_table.php
- 2025_10_15_214502_create_registro_piso_polo_table.php
- 2025_10_28_162020_create_registro_piso_corte_table.php
```

