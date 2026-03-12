# Refactorización Blade → Backend (Insumos/Materiales)

## 📋 Resumen Ejecutivo

El blade `index.blade.php` contiene **lógica de negocio** que debería estar en el **backend**. Se identificaron funciones que deben migrarse al `CalculadorDemoraService` y `MaterialesService`.

---

## 🔴 Funciones a Refactorizar (Prioridad Alta)

### 1. **calcularDemora()** (Línea ~36-89)

**Ubicación actual**: `resources/views/insumos/materiales/index.blade.php` (Inline Script)

**Qué hace**:
```javascript
function calcularDemora(materialId) {
    // Extrae fechas de inputs
    // Calcula días laborales (sin sábado/domingo)
    // Determina estado (RÁPIDO, NORMAL, LENTO, CRÍTICO)
    // Aplica clases CSS según estado
}
```

**Problemas**:
- ❌ Lógica de negocio en frontend
- ❌ No usa el `CalculadorDemoraService` existente
- ❌ Duplicidad: Ya existe `calcularDemoraAsync()` en `utilities.js`
- ❌ Cálculo de días laborales manual (sin considerar festivos de verdad)

**Solución**:
```javascript
// ANTES (Blade)
function calcularDemora(materialId) {
    // 60+ líneas de código
}

// DESPUÉS (Blade - simplificado)
async function calcularDemora(materialId) {
    const fila = document.getElementById(`row_${materialId}`);
    const inputs = fila.querySelectorAll('input[type="date"]');
    
    const demora = await window.calcularDemoraAsync(
        inputs[0].value,
        inputs[1].value
    );
    
    // Solo UI: aplicar colores
    const span = fila.querySelector('span[id*="dias_"]');
    span.textContent = demora.texto;
    span.className = `... ${demora.clase_bg} ${demora.clase_text}`;
}
```

**Backend correspondiente**: `/api/insumos/calcular-demora` ✅ YA EXISTE

---

### 2. **actualizarDiasDemora()** (Línea ~3184-3234)

**Ubicación actual**: `resources/views/insumos/materiales/index.blade.php` (Inline Script)

**Qué hace**:
```javascript
function actualizarDiasDemora(fila) {
    // Obtiene fechas de inputs
    // Calcula fechas laborales con bucle while
    // Actualiza span visualmente
}
```

**Problemas**:
- ❌ Recalcula cada vez que se edita una fecha (DOM mutation listener)
- ❌ Bucle while que cuenta días es cálculo de negocio
- ❌ No usa CalculadorDiasService (existe en backend)

**Solución**: Usar `calcularDemora()` refactorizado arriba

---

### 3. **Cálculo manual de días laborales** (Línea ~3208-3214)

**Código actual**:
```javascript
while (fecha <= fecha2) {
    if (fecha.getDay() !== 0 && fecha.getDay() !== 6) {
        diasLaborales++;
    }
    fecha.setDate(fecha.getDate() + 1);
}
```

**Problemas**:
- ❌ Código de negocio: no considera festivos reales
- ❌ Duplica lógica de `CalculadorDiasService` (backend)
- ❌ No es confiable para reportes

**Solución**: Delegar completamente a backend

**Backend correspondiente**: `CalculadorDiasService::calcularDiasHabiles()` ✅ YA EXISTE

---

## 🟡 Funciones a Considerar (Prioridad Media)

### 4. **Modal de Filtros** (Línea ~3244-3534)

**Ubicación**: `showFilterModal()`, `renderFilterValues()`, `applyFilters()`

**Análisis**:
- ✅ Filtrado es responsabilidad de presentación (OK en frontend)
- ⚠️ Pero hace fetch a `/insumos/api/filtros/{column}` (backend)
- ⚠️ Usa URLs hardcodeadas con rutas Laravel

**Recomendación**: Mantener como está (es presentación), pero mejorar:
- Usar variables de ruta en lugar de hardcoded
- Considerar caché si hay muchos filtros

---

### 5. **Validaciones en Modal** (Línea ~2063-2090)

**Ubicación**: Dentro de `guardarAnchoMetraje()`

```javascript
if (anchoVal && (isNaN(ancho) || ancho <= 0)) {
    showToast('El ancho debe ser un número mayor a 0', 'warning');
    return;
}
```

**Análisis**:
- ✅ Es validación de UI (OK en frontend)
- ⚠️ Pero el backend también debería validar
- ⚠️ No hay validación en backend visible

**Recomendación**: Agregar validación también en backend (DDD)

---

## ✅ Funciones que ESTÁN BIEN (OK en Frontend)

### ✓ **toggleRowCheck()** - Marcar/desmarcar fila
Responsabilidad: UI ✅

### ✓ **Modal Management** - Abrir/cerrar modales
Responsabilidad: UI ✅

### ✓ **crearDropdownAcciones()** - Crear dropdown dinámico
Responsabilidad: UI/Presentación ✅

### ✓ **llenarTablaInsumos()** - Renderizar tabla
Responsabilidad: Presentación ✅

---

## 📊 Plan de Refactorización

### Fase 1: Simplificar (Inmediato)
```
[✅] calcularDemora() → Usar calcularDemoraAsync() existente
[✅] actualizarDiasDemora() → Simplificar a llamada a backend
[✅] Remover cálculo manual de días laborales
```

### Fase 2: Integración (Próxima Semana)
```
[ ] Validar que CalculadorDemoraService cubre todos los casos
[ ] Agregar validación en backend para ancho/metraje
[ ] Tests para CalculadorDemoraService
```

### Fase 3: Optimización (Opcional)
```
[ ] Caché de demoras calculadas
[ ] Cálculo batch de demoras para múltiples materiales
[ ] Webhook para actualizar demoras en tiempo real (si se necesita)
```

---

## 🔧 Implementación Inmediata

### Paso 1: Refactorizar `calcularDemora()`

**Antes**:
```javascript
function calcularDemora(materialId) {
    // 54 líneas
    // Cálculo local de días laborales
    // Lógica de colores hardcodeada
}
```

**Después**:
```javascript
async function calcularDemora(materialId) {
    const fila = document.getElementById(`row_${materialId}`);
    if (!fila) return;

    const inputs = fila.querySelectorAll('input[type="date"]');
    const demora = await window.calcularDemoraAsync(inputs[0].value, inputs[1].value);
    
    const span = fila.querySelector('span[id*="dias_"]');
    span.textContent = demora.texto;
    span.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
}
```

**Líneas reducidas**: 54 → 13 (-76%)

---

### Paso 2: Refactorizar `actualizarDiasDemora()`

**Antes**:
```javascript
function actualizarDiasDemora(fila) {
    // 50 líneas
    // Bucle while
    // Cálculo de días laborales
}
```

**Después**:
```javascript
async function actualizarDiasDemora(fila) {
    const inputs = fila.querySelectorAll('input[type="date"]');
    const materialId = fila.getAttribute('data-material-id');
    
    await calcularDemora(materialId);
}
```

**Líneas reducidas**: 50 → 6 (-88%)

---

## 📈 Impacto de la Refactorización

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Líneas en Blade | ~4,633 | ~4,550 | -83 líneas (-1.8%) |
| Funciones en Blade | 45+ | 35+ | -10 funciones |
| Lógica de negocio en Frontend | 3 funciones | 0 funciones | 100% movido al backend |
| Código duplicado | 2 (calcularDemora + calcularDemoraAsync) | 0 | Eliminado |
| Dependencia de Backend API | No | Sí | ✅ Mejor arquitectura |

---

## 🎯 Conclusión

El **Blade necesita ser simplificado** removiendo:
1. ✂️ Cálculo de demoras (ir a backend)
2. ✂️ Cálculo de días laborales (ir a backend)
3. ✂️ Lógica de colores por estado (ir a backend)

**Resultado**: Blade más limpio, lógica centralizada en backend, cumple DDD.

---

## 📝 Checklist de Refactorización

- [ ] Refactorizar `calcularDemora()` para usar API
- [ ] Refactorizar `actualizarDiasDemora()` para usar `calcularDemora()`
- [ ] Remover bucle while de cálculo de días laborales
- [ ] Probar que demoras se calculan correctamente
- [ ] Eliminar código duplicado
- [ ] Documentar cambios en REFACTORIZACIÓN_INSUMOS.md

---

**Generado**: 12-03-2026
**Estado**: Listo para implementación
