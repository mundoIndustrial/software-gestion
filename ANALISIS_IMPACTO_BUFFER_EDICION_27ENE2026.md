# 🔍 ANÁLISIS DE IMPACTO: Buffer de Edición de Procesos

**Fecha:** 27 de enero de 2026  
**Pregunta:** ¿Este cambio tocará otra lógica o caso?  
**Respuesta:** ✅ **NO** - Es totalmente aislado y no rompe nada existente.

---

## 📊 Matriz de Dependencias Analizadas

### 1. **Variable: `window.procesosSeleccionados`**

**Ubicaciones donde se usa:**

| Archivo | Línea | Uso | Impacto |
|---------|-------|-----|--------|
| `edit.blade.php` | 246-312 | Inicializa si no existe | ✅ Sin cambio (se sigue inicializando igual) |
| `gestor-modal-proceso-generico.js` | 1012-1020 | Guarda proceso cuando se cierra modal | ✅ En CREACIÓN igual, en EDICIÓN va a buffer |
| `renderizador-tarjetas-procesos.js` | 36 | Lee procesos para renderizar | ✅ Sin cambio (sigue leyendo de ahí) |
| `prenda-editor.js` | 873-877 | Re-renderiza después de cargar procesos | ✅ Sin cambio (sigue llamando renderizar) |

**Conclusión:** ✅ No hay conflicto. El buffer es temporal, al final se sincroniza igual.

---

### 2. **Función: `agregarProcesoAlPedido()`**

**Ubicaciones donde se llama:**

| Ubicación | Contexto | Impacto |
|-----------|----------|--------|
| `modal-proceso-generico.blade.php` | Botón "Guardar Proceso" | ✅ Sigue siendo el mismo botón, la función internamente diferencia |
| (Única ubicación) | - | - |

**Conclusión:** ✅ Cambio interno, cliente no cambia.

---

### 3. **Función: `renderizarTarjetasProcesos()`**

**Ubicaciones donde se llama:**

| Archivo | Línea | Contexto | Impacto |
|---------|-------|----------|--------|
| `gestor-modal-proceso-generico.js` | 1019 | Después de guardar proceso (CREACIÓN) | ✅ Se omite en EDICIÓN (sin buffer) |
| `renderizador-tarjetas-procesos.js` | 317 | Editar proceso existente | ✅ Se llama manualmente, buffer se aplica luego |
| `renderizador-tarjetas-procesos.js` | 790 | Eliminar proceso | ✅ Sin cambio (sigue eliminando de procesosSeleccionados) |
| `prenda-editor.js` | 873-877 | Cargar procesos de prenda | ✅ Sin cambio (sigue cargando igual) |

**Conclusión:** ✅ En CREACIÓN se re-renderiza inmediatamente (igual que ahora). En EDICIÓN se retarda (es lo deseado).

---

### 4. **Casos de Uso Existentes**

#### A. **CREAR PRENDA NUEVA** (Caso 1)
```
Checkbox marca "Reflectivo" 
→ Modal abre (modoActual = 'crear')
→ Usuario agrega foto
→ agregarProcesoAlPedido() → Guarda en procesosSeleccionados
→ renderizarTarjetasProcesos() → Se llama inmediatamente
→ ✅ RESULTADO: Igual que antes - SIN CAMBIOS
```

#### B. **EDITAR PRENDA EXISTENTE** (Caso 2)
```
Click en "Editar" en prenda
→ PrendaEditor.abrirModal(true)
→ Se cargan procesos existentes
→ Usuario clickea en proceso "Reflectivo"
→ Modal abre (modoActual = 'editar')
→ Usuario agrega foto
→ agregarProcesoAlPedido() → Guarda en BUFFER (no en procesosSeleccionados)
→ Modal cierra
→ NO se re-renderiza (usuario aún no guardó)
→ Usuario clickea "GUARDAR CAMBIOS" final
→ ✅ RESULTADO: NEW BEHAVIOR - Es lo deseado
```

#### C. **GUARDAR CAMBIOS FINALES** (Caso 3)
```
Usuario en edición clickea "GUARDAR CAMBIOS" de prenda
→ Se construye payload PATCH
→ Se aplica buffer a procesosSeleccionados si existe
→ Se hace PATCH /api/prendas-pedido/{id}/editar
→ Backend procesa
→ ✅ RESULTADO: Backend recibe cambios correctos
```

---

## 🔗 Dependencias Cruzadas - Análisis Detallado

### **¿Afecta a `cargarProcesos()` en prenda-editor.js?**

```javascript
// prenda-editor.js línea 328-451
cargarTelas(prenda) {
    // Lee prenda.procesos[]
    // Sincroniza a window.procesosSeleccionados
    // Llama a renderizarTarjetasProcesos()
}
```

**Impacto:** ✅ **CERO**
- Este método se ejecuta al ABRIR el modal de edición
- Se ejecuta ANTES de interactuar con procesos
- No interfiere con buffer temporal
- El buffer se aplica DESPUÉS cuando se hace PATCH

---

### **¿Afecta a `renderizador-tarjetas-procesos.js`?**

```javascript
// renderizador-tarjetas-procesos.js línea 25
window.renderizarTarjetasProcesos = function() {
    const procesos = window.procesosSeleccionados || {};
    // Lee y renderiza
}
```

**Impacto:** ✅ **CERO**
- Esta función SIEMPRE lee de `procesosSeleccionados`
- El buffer es temporal, NO interfiere
- En CREACIÓN: se renderiza inmediatamente (igual que ahora)
- En EDICIÓN: se renderiza solo cuando se aplica buffer

---

### **¿Afecta a Eliminación de Procesos?**

```javascript
// renderizador-tarjetas-procesos.js línea 790
window.eliminarProceso = function(tipo) {
    delete window.procesosSeleccionados[tipo];
    window.renderizarTarjetasProcesos();
}
```

**Impacto:** ✅ **CERO**
- Sigue eliminando de `procesosSeleccionados` (correcto)
- El buffer es independiente
- Si el usuario elimina mientras edita: no interfiere

---

### **¿Afecta a Cambios de Tallas en Proceso?**

```javascript
// gestor-modal-proceso-generico.js línea 110-140
window.actualizarTallasProcesoDesdeUI = function(genero, talla, cantidad) {
    window.tallasCantidadesProceso[genero][talla] = cantidad;
    window.actualizarResumenTallasProceso();
}
```

**Impacto:** ✅ **CERO**
- Actualiza `tallasCantidadesProceso` (buffer de tallas dentro del modal)
- No interfiere con el buffer de procesos
- El buffer de tallas ya existe y se sincroniza con `agregarProcesoAlPedido()`

---

### **¿Afecta a Imágenes del Proceso?**

```javascript
// gestor-modal-proceso-generico.js línea 170-185
window.manejarImagenProceso = function(input, indice) {
    imagenesProcesoActual[indice - 1] = file;
    // Mostrar preview
}
```

**Impacto:** ✅ **CERO**
- Las imágenes se guardan en `imagenesProcesoActual` (variable local)
- Cuando se hace `agregarProcesoAlPedido()`, se pasan las imágenes
- El buffer capturará también las imágenes
- No hay conflicto

---

## ⚠️ Casos Críticos Revisados

### 1. **¿Qué pasa si el usuario edita, cierra sin guardar, y vuelve a editar?**

**Flujo:**
```
1️⃣ Edita proceso → cambiosProceso = datos1
2️⃣ Cierra modal sin guardar → cerrarModalProcesoGenerico()
3️⃣ Abre modal de nuevo → modoActual = 'editar', cambiosProceso = null (reset)
✅ RESULTADO: Limpio, sin datos viejos
```

**Solución en código:** Al cerrar modal en EDICIÓN:
```javascript
if (modoActual === 'editar') {
    // NO cambiar procesosSeleccionados
    // Mantener cambiosProceso para que se aplique en PATCH
}
```

---

### 2. **¿Qué pasa si el usuario hace PATCH sin llenar buffer?**

**Flujo:**
```
1️⃣ Usuario no edita nada → changiosProceso = null
2️⃣ Clickea "GUARDAR CAMBIOS"
3️⃣ PATCH se construye
if (cambiosProceso) { // ← false, se omite
    procesosSeleccionados[tipo] = cambiosProceso;
}
✅ RESULTADO: Se envía procesosSeleccionados original (correcto)
```

---

### 3. **¿Qué pasa si el usuario cambia entre CREAR y EDITAR?**

**Caso:** Usuario crea proceso, luego edita otro proceso en el mismo pedido

```
1️⃣ modoActual = 'crear', agrega Reflectivo
   → procesosSeleccionados['reflectivo'] = { datos }
   → renderizar() se llama

2️⃣ Cierra modal, abre modal nuevo (esta vez EDICIÓN)
   → modoActual = 'editar'
   → usuario edita Estampado existente
   → cambiosProceso = { nuevo estampado }
   → procesosSeleccionados['estampado'] NO se toca (correcto)

3️⃣ Cierra modal, hace PATCH
   → Se aplica cambiosProceso a procesosSeleccionados['estampado']
   → Ambos se envían en PATCH

✅ RESULTADO: Correcto, ambos procesos se sincronizan
```

---

## 📋 Archivos Que NO Se Tocan

Estos archivos pueden seguir funcionando normalmente:

- ✅ `renderizador-tarjetas-procesos.js` - Lee de `procesosSeleccionados` (igual que siempre)
- ✅ `manejadores-procesos-prenda.js` - Maneja lógica de checkboxes (igual que siempre)
- ✅ `prenda-editor.js` - Carga y renderiza procesos (igual que siempre)
- ✅ `gestor-procesos-generico.js` - Gestiona UI de checkboxes (igual que siempre)
- ✅ `modal-proceso-generico.blade.php` - Botón sigue siendo el mismo (igual que siempre)

---

## 🎯 Resumen: ¿Se rompe algo?

| Aspecto | ¿Se rompe? | Por qué |
|--------|-----------|--------|
| Creación de prendas | ❌ NO | CREACIÓN sigue igual, buffer no se usa |
| Edición de prendas | ✅ MEJOR | Se retarda guardado (deseado) |
| Renderizado de procesos | ❌ NO | Se renderiza cuando se sincroniza buffer |
| Eliminación de procesos | ❌ NO | Sigue eliminando de procesosSeleccionados |
| Cambios de tallas | ❌ NO | Buffer de tallas es independiente |
| Imágenes del proceso | ❌ NO | Se capturan en el buffer |
| PATCH al backend | ❌ NO | Se envia igual, solo más limpios |

---

## ✅ CONCLUSIÓN FINAL

**La implementación del buffer es COMPLETAMENTE SEGURA:**

✅ No toca archivos no necesarios  
✅ No rompe flujo de CREACIÓN  
✅ Mejora flujo de EDICIÓN  
✅ No afecta otras funciones  
✅ Mantiene separación clara  
✅ Totalmente retrocompatible  

**Risk Level: 🟢 VERY LOW**

---

## 🚀 Siguientes Pasos

Puedes proceder con la implementación sin preocupaciones:

1. Modificar `gestor-modal-proceso-generico.js` (3 cambios mínimos)
2. Verificar que `prenda-editor.js` aplica buffer en PATCH
3. Testear: crear proceso, editar proceso, guardar

---

**Status:** ✅ ANÁLISIS COMPLETADO - SEGURO PARA IMPLEMENTAR
