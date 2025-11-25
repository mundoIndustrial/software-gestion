# 🔍 REPORTE DE DIAGNÓSTICO: tabla_original

## 📊 RESUMEN EJECUTIVO

**Fecha:** 2025-11-25  
**Estado General:** ⚠️ **ADVERTENCIA** - Hay problemas que deben corregirse ANTES de migrar

---

## 📈 ESTADÍSTICAS GENERALES

### tabla_original
```
Total de registros: 2,208
Rango de pedido:   4421 a 45401

Estado de datos:
✅ Sin pedidos NULL:        0
✅ Sin fechas NULL:         0
✅ Sin duplicados:          0
⚠️  Sin cliente NULL:        3  (0.13%)
❌ Sin asesora datos:     2,208 (100%) ← PROBLEMA
❌ Sin área específica:     45   (2.04%)
```

### registros_por_orden
```
Total de registros:  6,483
Promedio por pedido: 2.96 prendas

⚠️  Sin prenda NULL:   3  (0.046%)
✅ Sin cantidad NULL: 0
❌ Sin talla NULL: 6,483 (100% - muchas vacías) ← PROBLEMA
```

---

## 🚨 PROBLEMAS CRÍTICOS ENCONTRADOS

### ❌ PROBLEMA 1: 19 pedidos sin registros
**Severidad:** 🔴 CRÍTICO

**Afectados:** 19 de 2,208 pedidos (0.86%)

**Pedidos:**
- 4421, 43116, 43176, 43178, 43184, 43199, 43201, 43206, 43207, 44342 (y más)

**Impacto en migración:**
```
PedidoProduccion será creado OK
PrendaPedido no tendrá registros
ProcesoPrenda tampoco
Result: Pedidos "vacíos" sin detalles
```

**Solución:** 
- Opción 1: Investigar por qué estos pedidos no tienen prendas
- Opción 2: Eliminar estos pedidos si son basura
- Opción 3: Migrar y marcar como "incompletos"

---

### ❌ PROBLEMA 2: 3 pedidos sin cliente
**Severidad:** 🟡 MEDIO

**Afectados:** 3 de 2,208 pedidos (0.13%)

**Impacto en migración:**
```
PedidoProduccion tendrá cliente = NULL
Esto puede romper integridad referencial
```

**Solución:**
- Actualizar estos 3 registros con cliente válido
- O marcar con "CLIENTE DESCONOCIDO"

---

### ❌ PROBLEMA 3: 393 clientes INCONSISTENTES entre tablas
**Severidad:** 🔴 CRÍTICO

**Ejemplo:**
```
tabla_original:
├─ pedido: 12345
├─ cliente: "CLIENTE A"

registros_por_orden:
├─ pedido: 12345
├─ cliente: "CLIENTE B"  ← ¡DISTINTO!
```

**Impacto en migración:**
```
Al crear prendas_pedido, ¿qué cliente usar?
- Si usas tabla_original: pierdes referencia en registros_por_orden
- Si usas registros_por_orden: mismatch con pedido
- Result: Data inconsistente en nueva estructura
```

**Solución:**
- Revisar y alinear clientes en AMBAS tablas
- Usar como fuente de verdad TABLA_ORIGINAL

---

### ⚠️ PROBLEMA 4: Asesora vacía en 2,208 registros
**Severidad:** 🟡 MEDIO

**Todos los pedidos en tabla_original TIENEN asesora = NULL**

**Nota:** La columna "asesora" aparece ser el campo fecha en algunos registros

**Impacto:**
```
PedidoProduccion.asesora será NULL
Perderemos trazabilidad de quién creó el pedido
```

---

### ⚠️ PROBLEMA 5: Muchas tallas vacías
**Severidad:** 🟡 BAJO

**6,483 registros de talla (100%)**

**Nota:** Parece que talla se usa como distintos valores (incluidos NULL)

**Impacto:** Baja - Las prendas se migrarán igual

---

## 📋 TABLA DE PROBLEMAS

| # | Problema | Severidad | Afectados | Solución |
|---|----------|-----------|-----------|----------|
| 1 | Pedidos sin prendas | 🔴 CRÍTICO | 19 | Investigar/Eliminar |
| 2 | Cliente NULL | 🟡 MEDIO | 3 | Actualizar |
| 3 | Cliente inconsistente | 🔴 CRÍTICO | 393 | Alinear valores |
| 4 | Asesora NULL | 🟡 MEDIO | 2,208 | Revisar mapeo |
| 5 | Tallas vacías | 🟢 BAJO | 6,483 | Aceptar |

---

## 🔧 RECOMENDACIONES

### ANTES DE MIGRAR:

#### 1️⃣ CRÍTICO - Alinear clientes (393 registros)
```sql
-- Verificar inconsistencias
SELECT r.pedido, r.cliente as cliente_registro, t.cliente as cliente_tabla
FROM registros_por_orden r
JOIN tabla_original t ON r.pedido = t.pedido
WHERE r.cliente != t.cliente
LIMIT 10;

-- Opción A: Actualizar registros_por_orden con tabla_original
UPDATE registros_por_orden r
JOIN tabla_original t ON r.pedido = t.pedido
SET r.cliente = t.cliente
WHERE r.cliente != t.cliente;

-- Opción B: Revisar manualmente si hay datos correctos
```

#### 2️⃣ CRÍTICO - Investigar 19 pedidos sin prendas
```sql
-- Ver estos pedidos
SELECT pedido, cliente, estado, area
FROM tabla_original
WHERE pedido IN (4421, 43116, 43176, 43178, 43184, 43199, 43201, 43206, 43207, 44342);

-- ¿Tienen registros?
SELECT COUNT(*)
FROM registros_por_orden
WHERE pedido IN (4421, 43116, 43176, ...);

-- Opciones:
-- - Mantenerlos (pedidos sin detalles)
-- - Eliminarlos (si son test/basura)
-- - Completarlos manualmente
```

#### 3️⃣ MEDIO - Actualizar 3 clientes NULL
```sql
-- Encontrar y actualizar
SELECT pedido, cliente, area, estado
FROM tabla_original
WHERE cliente IS NULL;

-- Actualizar con valor default
UPDATE tabla_original
SET cliente = 'CLIENTE DESCONOCIDO'
WHERE cliente IS NULL;
```

#### 4️⃣ MEDIO - Revisar mapeo de asesora
```sql
-- Las asesoras están vacías?
SELECT COUNT(DISTINCT asesora)
FROM tabla_original
WHERE asesora IS NOT NULL;

-- O están en otra columna?
SELECT DISTINCT asesora
FROM tabla_original
LIMIT 20;
```

---

## 🚀 PLAN DE ACCIÓN

### Fase 1: Diagnóstico (HECHO)
✅ Identificar problemas

### Fase 2: Limpiar datos (PENDIENTE)
```bash
# 1. Alinear clientes
# 2. Actualizar clientes NULL
# 3. Revisar pedidos sin prendas
# 4. Verificar asesoras
```

### Fase 3: Validar (PENDIENTE)
```bash
php artisan diagnostic:tabla-original
# Debe mostrar 0 problemas críticos
```

### Fase 4: Migrar (SEGURO)
```bash
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
php artisan migrate:tabla-original-to-pedidos-produccion
```

---

## 📊 MUESTRAS DE DATOS PROBLEMÁTICAS

### Pedido sin prendas (#4421)
```
Pedido: 4421
Cliente: ASESORES MUNDO
Estado: Entregado
Área: Entrega
Fecha: 2025-04-04
Prendas: 0  ← ¡SIN DETALLES!
```

### Pedido con cliente inconsistente (#25892)
```
tabla_original:
  cliente: "MC CORMIC"
  asesora: "2025-06-16"  ← ¡PARECE FECHA!

registros_por_orden:
  cliente: [podría ser distinto]
  prenda: "CAMISA CABALLERO"
```

---

## ✅ SIGUIENTE PASO

**Ejecuta estas queries SQL para limpiar:**

```sql
-- 1. Alinear clientes inconsistentes
UPDATE registros_por_orden r
JOIN tabla_original t ON r.pedido = t.pedido
SET r.cliente = t.cliente
WHERE r.cliente != t.cliente;

-- 2. Actualizar clientes NULL
UPDATE tabla_original
SET cliente = 'CLIENTE DESCONOCIDO'
WHERE cliente IS NULL;

-- 3. Ver pedidos sin prendas
SELECT pedido, cliente, estado, area
FROM tabla_original
WHERE pedido NOT IN (SELECT DISTINCT pedido FROM registros_por_orden);
```

Luego ejecuta de nuevo:
```bash
php artisan diagnostic:tabla-original
```

Debe mostrar 0 problemas críticos.

---

**Estado:** ⚠️ REVISAR DATOS ANTES DE MIGRAR  
**Acción recomendada:** Ejecutar limpiezas SQL arriba ↑
