# 🔍 ANÁLISIS DE PERFORMANCE: RENDER DEL MODAL DE EDICIÓN

**Fecha**: 27 Enero 2026  
**Problema**: Modal tarda ~1s en mostrarse (assets cargan en µs)  
**Root cause**: Render pesado, múltiples reflows, batch ineficiente

---

## 📊 CUELLOS IDENTIFICADOS

### 1. **innerHTML en bucles (CRITICAL)**
```javascript
// ❌ PROBLEMA EN gestion-telas.js:~330
telasParaMostrar.forEach((telaData, index) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `...contenido HTML...`;  // ✗ Crea un reflow POR CADA TELA
    tbody.appendChild(tr);                  // ✗ Otro reflow
});
```

**Impacto**: Si hay 3-10 telas = 6-20 reflows  
**Tiempo estimado**: 100-300ms

---

### 2. **Múltiples appendChild() (HIGH)**
```javascript
// ❌ prenda-editor-modal.js:354
document.body.appendChild(modal);  // Reflow 1
// Luego en gestion-telas.js
tbody.appendChild(row);            // Reflow 2-N
// Y en renderizador-tarjetas-procesos.js
container.innerHTML = html;        // Reflow N+1
```

**Impacto**: 10-15 operaciones DOM separadas = cascada de reflows  
**Tiempo estimado**: 200-400ms

---

### 3. **innerHTML = '' + innerHTML += (CRITICAL)**
```javascript
// ❌ renderizador-tarjetas-procesos.js:63
let html = '';
procesosConDatos.forEach(tipo => {
    html += generarTarjetaProceso(tipo, proceso.datos);  // ✗ Concatenación de strings
});
container.innerHTML = html;  // ✗ Parse + render en una pasada
```

**Impacto**: String concatenation es O(n²), luego un solo innerHTML pesado  
**Tiempo estimado**: 100-200ms

---

### 4. **SweetAlert mount (MEDIUM)**
```javascript
// ❌ prenda-editor-modal.js:~350
Swal.fire({...});  // Mount completo del modal
// + prenda-editor-modal.js:354
document.body.appendChild(modal);  // Otro append
```

**Impacto**: SweetAlert tiene su propio ciclo de render  
**Tiempo estimado**: 150-300ms

---

### 5. **Logs masivos en consola (LOW pero visible)**
- 30+ console.log() por cada render
- En modo debug consume CPU

**Tiempo estimado**: 50-100ms

---

## ⏱️ DESGLOSE ESTIMADO DE 1000ms

| Componente | Tiempo | % |
|-----------|--------|------|
| Carga scripts (lazy loading) | 50ms | 5% |
| **SweetAlert mount** | **250ms** | **25%** |
| **generarHTMLFactura** | **200ms** | **20%** |
| **actualizarTablaTelas (reflows)** | **200ms** | **20%** |
| **renderizarTarjetasProcesos** | **150ms** | **15%** |
| Hidratación JS (eventos) | **100ms** | **10%** |
| Logs + misc | **50ms** | **5%** |
| **TOTAL** | **~1000ms** | **100%** |

---

## 🚀 SOLUCIONES PROPUESTAS

### TÉCNICA 1: DocumentFragment + Batch Rendering (MUST DO)
```javascript
// ✅ ANTES: appendChild + innerHTML por cada elemento
// ✅ DESPUÉS: Crear fragment, insertar todo de una vez

const fragment = document.createDocumentFragment();

telasParaMostrar.forEach((telaData) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `...contenido...`;
    fragment.appendChild(tr);  // Sin reflow
});

tbody.appendChild(fragment);  // UN SOLO REFLOW
```

**Ganancia estimada**: 150-200ms (20-30% del total)

---

### TÉCNICA 2: Virtual DOM Manual (ADVANCED)
```javascript
// ✅ Template Buffer: construir HTML en memoria, insertar una sola vez

let htmlBuffer = '';
procesosConDatos.forEach(tipo => {
    htmlBuffer += generarTarjetaProceso(tipo, proceso.datos);
});

// Usar innerHTML.insertAdjacentHTML en lugar de innerHTML =
const container = document.getElementById('contenedor-procesos');
container.innerHTML = '';  // Limpiar una sola vez
container.insertAdjacentHTML('beforeend', htmlBuffer);  // Más rápido
```

**Ganancia estimada**: 50-100ms (5-10%)

---

### TÉCNICA 3: Lazy Render con requestIdleCallback (NICE TO HAVE)
```javascript
// ✅ Renderizar telas/procesos DESPUÉS de que SweetAlert esté visible

Swal.fire({...}).then(() => {
    // Esperar a que el navegador esté libre
    requestIdleCallback(() => {
        actualizarTablaTelas();      // No bloquea main thread
        renderizarTarjetasProcesos(); // Se renderiza después
    });
});
```

**Ganancia estimada**: "sentir" más rápido (perceived performance +30%)

---

### TÉCNICA 4: Debounce de Logs en Consola
```javascript
// ✅ En desarrollo: logs completos
// ✅ En producción: logs mínimos

const DEBUG_MODE = true; // Set false en producción
if (DEBUG_MODE) console.log('...');
```

**Ganancia estimada**: 50ms (5%)

---

### TÉCNICA 5: SweetAlert Optimización (MEDIUM)
```javascript
// ❌ ANTES: Mount completo con animaciones
Swal.fire({
    didOpen: (modal) => { /* lógica */ }
});

// ✅ DESPUÉS: Desactivar animaciones innecesarias en modal de edición
Swal.fire({
    allowOutsideClick: false,
    allowEscapeKey: true,
    showConfirmButton: false,
    didOpen: async (modal) => {
        // Diferir hidratación JS
        await new Promise(r => requestAnimationFrame(r));
        inicializarElementosModales();
    }
});
```

**Ganancia estimada**: 50-100ms (5-10%)

---

## 📋 PLAN DE ACCIÓN (Prioridad)

### PHASE 1: HIGH IMPACT (implementar primero)
1. ✅ DocumentFragment + batch rendering en gestion-telas.js
2. ✅ Virtual DOM manual en renderizador-tarjetas-procesos.js
3. ✅ Eliminar logs de consola en modo producción

**Ganancia**: 250-300ms (**25-30%** del total)

### PHASE 2: MEDIUM IMPACT (si necesario)
4. ✅ requestIdleCallback para lazy render
5. ✅ Optimizar SweetAlert

**Ganancia adicional**: 100-150ms (**10-15%**)

### PHASE 3: NICE TO HAVE
6. ✅ Virtual scrolling si hay +50 telas/procesos
7. ✅ Service Worker para cache de assets

---

## 🎯 OBJETIVO FINAL

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **Modal aparece** | 1000ms | **600-700ms** | **30-40%** ⬆️ |
| **Totalmente interactivo** | 1200ms | **700-800ms** | **35-40%** ⬆️ |
| **Perceived performance** | Lento | **Rápido** | **Muy notorio** ✨ |

---

## 💻 PRÓXIMOS PASOS
1. Implementar Phase 1
2. Medir con DevTools Performance tab (F12 > Performance)
3. Validar que no se rompe UX
4. Iterar Phase 2 si es necesario
