# 🔧 Fix: Insumos/Materiales - Prendas no visibles en estado CORTE/COSTURA

## 📋 Resumen del Problema

**Síntoma:** Los recibos en estado "CORTE" y "COSTURA" no aparecían en `http://localhost:8000/insumos/materiales`

**Impacto:** 
- Antes: Solo 3 recibos visibles
- Después: 10 recibos visibles (7 recibos recuperados)

---

## 🔍 Análisis Realizado

### Hallazgo Principal
El archivo [RecibosCosturaReadRepository.php](app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php) contenía una condición WHERE que buscaba en la **tabla equivocada**.

### Comparativa de BD

| Campo | Tabla | Valores encontrados |
|-------|-------|-------------------|
| `consecutivos_recibos_pedidos.estado` | Recibos | `PENDIENTE_INSUMOS`, `En Ejecución` |
| `consecutivos_recibos_pedidos.area` | Recibos | `CORTE`, `COSTURA`, `Insumos`, `Control Calidad`, `Despacho` |
| `pedidos_produccion.area` | Pedidos | `Costura`, `Corte`, `Despacho` |

### El Problema

**Código original (❌ INCORRECTO):**
```php
->where(function ($q) {
    $q->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
        // BUG: Buscaba en pedidos_produccion.area (tabla del pedido)
        ->orWhere('pedidos_produccion.area', 'LIKE', '%Corte%')
        ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion%orden%')
        ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion de orden%');
})
```

**Resultado:** Solo encontraba:
- 2 recibos con estado `PENDIENTE_INSUMOS`
- 1 recibo con pedido.area `LIKE '%Corte%'`
- **Total: 3 recibos**

---

## ✅ Solución Implementada

**Código corregido (✓ CORRECTO):**
```php
->where(function ($q) {
    $q->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
        // FIX: Ahora busca en consecutivos_recibos_pedidos.area (tabla del recibo)
        ->orWhereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA']);
})
```

**Resultado:** Ahora encuentra:
- 2 recibos con estado `PENDIENTE_INSUMOS`
- 6 recibos con área `COSTURA`
- 2 recibos con área `CORTE`
- **Total: 10 recibos** ✅

---

## 📊 Comparativa de Resultados

### Query ANTERIOR (❌)
```sql
... WHERE tipo_recibo = 'COSTURA' 
  AND activo = 1 
  AND (consecutivos_recibos_pedidos.estado = 'PENDIENTE_INSUMOS'
       OR pedidos_produccion.area LIKE '%Corte%'
       OR pedidos_produccion.area LIKE '%Creacion%orden%'
       OR pedidos_produccion.area LIKE '%Creacion de orden%')
  AND pedidos_produccion.estado != 'PENDIENTE_SUPERVISOR'
```
**Resultado: 3 recibos**

### Query DESPUÉS (✓)
```sql
... WHERE tipo_recibo = 'COSTURA' 
  AND activo = 1 
  AND (consecutivos_recibos_pedidos.estado = 'PENDIENTE_INSUMOS'
       OR consecutivos_recibos_pedidos.area IN ('CORTE', 'COSTURA'))
  AND pedidos_produccion.estado != 'PENDIENTE_SUPERVISOR'
```
**Resultado: 10 recibos**

---

## 📍 Recibos Recuperados

| Recibo | Estado | Área | Pedido | Cliente |
|--------|--------|------|--------|---------|
| 8 | En Ejecución | Costura | 37 | SUELAS E INSUMOS DEL NORTE |
| 20 | En Ejecución | Costura | 79 | OPERADORA LCM |
| 22 | En Ejecución | Costura | 74 | LA PERLA |
| 24 | En Ejecución | Costura | 74 | LA PERLA |
| 30 | En Ejecución | Costura | 127 | CURTIEMBRES CUCUTA |
| 31 | En Ejecución | Costura | 127 | CURTIEMBRES CUCUTA |
| 36 | En Ejecución | Corte | 144 | SDRAS INGENIEROS |

---

## 🔧 Archivos Modificados

- **[app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php](app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php)**
  - Método: `buildBaseQuery()`
  - Línea: ~25-32
  - Cambio: Ajustar condición WHERE para buscar en `consecutivos_recibos_pedidos.area` en lugar de `pedidos_produccion.area`

---

## ✨ Validación

✅ Script de debug confirma: **10 recibos** encontrados con la query corregida  
✅ Incluye todos los estados: `PENDIENTE_INSUMOS`, `En Ejecución`  
✅ Incluye todas las áreas del recibo: `CORTE`, `COSTURA`  

---

## 📝 Notas

La confusión surgió porque:
1. El modelo `consecutivos_recibos_pedidos` tiene su propio campo `area` que puede contener valores como "CORTE", "COSTURA"
2. Pero también está relacionado con `pedidos_produccion` que tiene su propio `area`
3. La query original estaba usando el área del pedido en lugar del área del recibo

La solución fue normalizar la condición para usar consistentemente el campo `area` del recibo (tabla `consecutivos_recibos_pedidos`).

---

**Fecha de corrección:** 2026-04-09  
**Impacto:** Los usuarios del módulo insumos/materiales ahora verán todos los recibos en estado CORTE/COSTURA
