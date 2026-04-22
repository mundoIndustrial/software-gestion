# Análisis de Rendimiento: Vista Insumos/Materiales
**Fecha:** 2026-04-22  
**URL:** http://localhost:8000/insumos/materiales

---

## 📊 Resumen de Problemas Identificados

La vista está lenta por **múltiples JOINs subqueries complejas** en la BD + **carga secuencial de JavaScript**. Aquí están los 5 principales cuellos de botella:

---

## 🔴 PROBLEMA 1: 5 JOINs Subqueries Complejas (CRÍTICO)

**Ubicación:** `app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php` líneas 26-73

**¿Qué hace?**
```php
applyActividadPrendaJoins($query)  // Se ejecuta en CADA query de recibos
```

Agrega 5 LEFT JOINs subqueries para calcular `actividad_prenda_en`:
1. Máximo updated_at de `prendas_pedido`
2. Máximo updated_at de `prenda_pedido_tallas`
3. Máximo updated_at de combinación `prenda_pedido_talla_colores + prenda_pedido_tallas`
4. Máximo updated_at de `prenda_pedido_colores_telas`
5. Máximo updated_at de `prenda_pedido_variantes`

**Impacto:** 
- Esto ejecuta **2-3 subqueries adicionales POR CADA RECIBO** en la tabla de resultados
- Con 10 recibos por página, son 50-150 operaciones DB extra
- Los JOINs subqueries con GROUP BY/MAX son lentos en tablas grandes

**Síntoma observado:**
```
RecibosQueryService: completado → Total = X recibos  // Toma 2-5 segundos
```

---

## 🟡 PROBLEMA 2: JavaScript Cargando Secuencialmente (MODERADO)

**Ubicación:** `public/js/insumos/materiales-page-loader.js` líneas 12-27

**¿Qué hace?**
```javascript
const MODULE_SCRIPTS = [
  { src: '/js/insumos/pagination.js' },
  { src: '/js/insumos/index.js', type: 'module' },
  { src: '/js/insumos/modal-handlers-insumos.js' },
  { src: '/js/insumos/form-handlers-insumos.js' },
  { src: '/js/insumos/status-actions-insumos.js' },
  // ... 15 scripts en total
];

// Cargados secuencialmente sin paralelización
for (const scriptDef of MODULE_SCRIPTS) {
  await loadScript(scriptDef);  // ← ESPERA a que terminen, UNO POR UNO
}
```

**Impacto:**
- Cada script debe ser descargado, parseado y ejecutado antes del siguiente
- Con 15 scripts, si cada uno toma 50-100ms, son 750-1500ms adicionales
- El loading overlay se ve "pegado" porque los scripts tardan

---

## 🟡 PROBLEMA 3: Cálculo de `actividad_prenda` Muy Complejo (MODERADO)

**Ubicación:** [RecibosCosturaReadRepository.php líneas 10-22](app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php#L10-L22)

```php
DB::raw("CASE WHEN ... ELSE GREATEST(
  COALESCE(prenda_max.updated_at, '1970-01-01'),
  COALESCE(tallas_max.updated_at, '1970-01-01'),
  COALESCE(talla_colores_max.updated_at, '1970-01-01'),
  COALESCE(colores_telas_max.updated_at, '1970-01-01'),
  COALESCE(variantes_max.updated_at, '1970-01-01')
) END")
```

**¿Por qué es lento?**
- GREATEST() en MySQL es O(n) para cada fila
- Se ejecuta en CADA ROW de recibos
- Los 5 JOINs subqueries se evalúan incluso si el recibo no usa prendas

**Se usa para:** Ordenar recibos por "actividad más reciente"

---

## 🟡 PROBLEMA 4: Múltiples Consultas Secuenciales (MODERADO)

**Ubicación:** `app/Application/Insumos/Services/RecibosQueryService.php` líneas 20-134

```php
// Query 1: Obtener paginación (con 5 JOINs subqueries)
$paginador = $query->paginate($perPage);

// Query 2: Obtener mapa de parciales
$parcialCreatedAtMap = $this->repository->obtenerMapaParciales();

// Query 3: Obtener mapa de materiales
$materialesMap = $this->materialesMapBuilder->build();

// Todo en PHP después
$recibosTransformados = $this->transformer->transform();
```

**Impacto:**
- 3 operaciones separadas en la BD
- Las 2 y 3 dependen de los IDs de la query 1
- No hay paralelización posible

---

## 🔵 PROBLEMA 5: Transformación PHP Post-Query (BAJO)

**Ubicación:** `app/Infrastructure/Insumos/ReadModels/RecibosViewTransformer.php` líneas 65-131

```php
return $recibos->map(function ($recibo) use (...) {
  // Lógica regex para extraer motivo anulación
  if (preg_match('/^ANULACION\b/iu', $lineaLimpia)) { ... }
  
  // Lógica regex para extraer novedad asesora  
  if (preg_match('/^Asesor-/iu', $lineaLimpia)) { ... }
  
  // Cálculo de días hábiles en PHP
  $diasCalculados = $calcularDiasCallback($fechaInicio);
});
```

**Impacto:**
- Este código se ejecuta en PHP, NO en BD
- Regex por cada recibo puede ser lento si el campo `notas` es muy grande
- Podría estar en SQL WHERE/CASE (aunque es menor)

---

## ✅ Recomendaciones de Optimización (por prioridad)

### 🔴 CRÍTICA: Eliminar los 5 JOINs Subqueries (Impacto: 50-70% mejora)

**Opción A: Materializar el cálculo en una tabla denormalizada**
```sql
-- Crear tabla de caché
CREATE TABLE actividad_prendas_cache (
  prenda_id BIGINT PRIMARY KEY,
  ultima_actividad DATETIME,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Actualizar cada vez que se modifique alguna tabla
-- (usar triggers o job)
```

**Opción B: Simplificar el cálculo**
- Si `actividad_prenda` solo se usa para ORDENAR, hacer la orden solo por `consecutivos_recibos_pedidos.updated_at` (más rápido)
- Medir si el cambio en el orden afecta la UX

**Opción C: Lazy-load la actividad**
```php
// En query principal: NO calcular actividad_prenda
// En transformación: Si se necesita, un SELECT adicional por lote
```

**Recomendación:** Opción B o C. Opción A requiere mantenimiento de tabla adicional.

---

### 🟡 ALTA: Parallelizar Carga de JavaScript

**Cambio en `materiales-page-loader.js` línea 78:**

```javascript
// ❌ ACTUAL (secuencial)
async function loadModuleScripts() {
  for (const scriptDef of MODULE_SCRIPTS) {
    await loadScript(scriptDef);
  }
}

// ✅ PROPUESTO (grupos paralelos con dependencias)
async function loadModuleScripts() {
  // Grupo 1: Scripts base (sin dependencias)
  await Promise.all([
    loadScript(MODULE_SCRIPTS[0]),  // pagination.js
    loadScript(MODULE_SCRIPTS[1]),  // index.js
  ]);
  
  // Grupo 2: Scripts que dependen del grupo 1
  await Promise.all(
    MODULE_SCRIPTS.slice(2).map(s => loadScript(s))
  );
}
```

**Impacto esperado:** Reducir carga de JS de 1500ms → 300-500ms

---

### 🟡 MEDIA: Combinar Queries BD

**En `RecibosQueryService.php` línea 75:**

```php
// ❌ ACTUAL (3 queries separadas)
$paginador = $query->paginate($perPage);  // Query 1
$parcialMap = $this->repository->obtenerMapaParciales($ids);  // Query 2  
$materialesMap = $this->materialesMapBuilder->build($recibos);  // Query 3

// ✅ PROPUESTO (2 queries)
// Query 1: Paginación + Materiales en SELECT subquery
$paginador = $query
  ->addSelect(DB::raw('(SELECT COUNT(*) FROM materiales_orden_insumos WHERE numero_pedido = ...) as cant_materiales'))
  ->paginate($perPage);

// Query 2: Parciales (si es necesario)
$parcialMap = $this->repository->obtenerMapaParciales(...);

// Evitar: Query 3 del materialesMapBuilder no es necesaria
```

**Impacto esperado:** Reducir 20-30% del tiempo de BD

---

### 🔵 BAJA: Optimizar Transformación (Bonus)

**En `RecibosViewTransformer.php` línea 65:**

```php
// Usar SQL CASE/REGEXP en lugar de PHP regex
// en la query principal:
DB::raw("CASE 
  WHEN notas REGEXP '^ANULACION\\b' THEN notas
  ELSE NULL 
END as motivo_anulacion")
```

**Impacto esperado:** 5-10% mejora (menor impacto)

---

## 🚀 Plan de Implementación Recomendado

### Fase 1 (Urgente) - 30 minutos
1. Cambiar orden de recibos: usar solo `updated_at` en lugar de `actividad_prenda` (línea 76)
2. Documentar el cambio en notas de desarrollo

### Fase 2 (Importante) - 1-2 horas
1. Parallelizar carga de JS (materiales-page-loader.js)
2. Probar en navegador

### Fase 3 (Opcional) - 3-4 horas
1. Combinar queries en RecibosQueryService
2. Optimizar transformación
3. Testing completo

---

## 📈 Métricas Esperadas (Post-Optimización)

| Métrica | Actual | Meta |
|---------|--------|------|
| Tiempo carga página | ~5-10s | ~1-2s |
| Tiempo BD query | ~3-5s | ~0.8-1.5s |
| Tiempo JS load | ~1-2s | ~0.3-0.5s |
| Rendering tabla | ~1-2s | ~0.3-0.5s |
| **Total** | **~5-10s** | **~1-2s** |

---

## 🔧 Archivos a Modificar

1. `app/Infrastructure/Insumos/ReadModels/RecibosCosturaReadRepository.php`
2. `public/js/insumos/materiales-page-loader.js`
3. `app/Application/Insumos/Services/RecibosQueryService.php`
4. `app/Infrastructure/Insumos/ReadModels/RecibosViewTransformer.php` (opcional)

---

## 📝 Notas Adicionales

- El archivo `debug_insumos_materiales.php` ya identifica problemas similares en la query
- Los logs muestran que `RecibosQueryService` es el cuello principal
- El loading overlay está bien implementado pero tarda porque el contenido tarda en cargarse
