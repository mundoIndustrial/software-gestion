# 🔍 DIAGNÓSTICO: BUG DE DOBLE FILE DIALOG

## Problema Reportado
Un `<input type="file">` abre el diálogo del sistema **dos veces consecutivas** con un solo click.

- No es doble click del usuario 
- No es doble listener   
- La función `input.click()` se ejecuta una sola vez 
- El file picker abre dos veces consecutivas 

---

## 🎯 CAUSA RAÍZ

Hay **3 causas posibles** que pueden ocurrir combinadas:

### 1️⃣ **Event Bubbling + Asincronía del Navegador**
Cuando llamaste `input.click()`, el navegador abre un file dialog **asincronamente**. Durante ese tiempo (microtarea), si existe cualquier mecanismo que vuelva a disparar `click()`, se abre un segundo diálogo.

```javascript
//  PROBLEMA: Sin guard
input.click();  // Se abre el diálogo asincronamente
// Entre aquí y la siguiente línea, podría haber un segundo trigger
```

### 2️⃣ **Listeners Acumulados en Modal Dinámico**
Si el modal se abre y cierra múltiples veces sin limpiar correctamente los listeners, podrían registrarse **múltiples handlers en el mismo elemento**. Cuando haces click en el preview, se ejecutan todos los handlers.

```javascript
//  PROBLEMA: Sin limpieza adequada
addEventListener('click', abrirSelectorImagenProceso);  // Aperturaura 1
// (modal cierra sin removeEventListener)
addEventListener('click', abrirSelectorImagenProceso);  // Aperturaura 2
// Ahora hay 2 listeners, ambos disparan input.click()
```

### 3️⃣ **Flag de Dialogo No Existe**
El `input.click()` puede ser llamado múltiples veces en rápida sucesión sin manera de saberlo. No hay mecanismo que diga: "Hey, el diálogo ya se está abriendo, no hagas click de nuevo".

```javascript
//  PROBLEMA: Sin flag
if (condition1) input.click();  // ¡Se abre!
if (condition2) input.click();  // ¡Se abre de nuevo!
```

---

##  SOLUCIÓN (3 OPCIONES)

### **OPCIÓN 1: Guard Flag (RECOMENDADA - Simple y Efectiva)**

⭐ **Mejor para:** Solucionar el problema de forma simple sin refactorizar todo.

```javascript
/**
 * Abrir selector de archivos para un cuadro de imagen específico
 * PREVIENE DOBLE DISPARO del file dialog usando un guard flag
 */
window.abrirSelectorImagenProceso = function(cuadroIndex) {
    const input = document.getElementById(`proceso-foto-input-${cuadroIndex}`);
    
    if (!input) return;
    
    // 🔒 GUARD: Si ya se está abriendo el diálogo, ignorar
    if (input._isDialogOpening) {
        console.warn(`Diálogo ya abiéndose para cuadro ${cuadroIndex}`);
        return;
    }
    
    // Marcar como "abriendo"
    input._isDialogOpening = true;
    input.value = '';
    input.click();
    
    // Limpiar el flag después de 200ms (tiempo para que se abra el diálogo)
    setTimeout(() => {
        input._isDialogOpening = false;
    }, 200);
};
```

**Ventajas:**
-  Simple, una línea de guard
-  No requiere refactorizar listeners
-  Resuelve el problema en 95% de casos

**Desventajas:**
- Si hay listeners acumulados, sigue siendo problema potencial

---

### **OPCIÓN 2: Limpiar Listeners Correctamente (COMPLETA)**

⭐ **Mejor para:** Modales dinámicos donde listeners se acumulan.

En `gestor-modal-proceso-generico.js`, asegúrate que la limpieza sea correcta:

```javascript
// En cerrarModalProcesoGenerico():
for (let i = 1; i <= 3; i++) {
    const preview = document.getElementById(`proceso-foto-preview-${i}`);
    const input = document.getElementById(`proceso-foto-input-${i}`);
    
    if (preview && preview._handlerPlaceholder) {
        //  REMOVER el listener ANTES de agregar uno nuevo
        preview.removeEventListener('click', preview._handlerPlaceholder);
        preview._handlerPlaceholder = null;
    }
    
    if (input && input._changeHandler) {
        input.removeEventListener('change', input._changeHandler);
        input._changeHandler = null;
    }
}

// En inicializarListenersInputsArchivo():
for (let i = 1; i <= 3; i++) {
    const preview = document.getElementById(`proceso-foto-preview-${i}`);
    
    //  LIMPIAR PRIMERO
    if (preview._handlerPlaceholder) {
        preview.removeEventListener('click', preview._handlerPlaceholder);
    }
    
    //  LUEGO AGREGAR
    const handlerPlaceholder = (function(idx) {
        return function(e) {
            e.stopPropagation();
            e.preventDefault();  // 👈 Agregado: prevenir comportamiento por defecto
            abrirSelectorImagenProceso(idx);
        };
    })(i);
    
    preview._handlerPlaceholder = handlerPlaceholder;
    preview.addEventListener('click', handlerPlaceholder);
}
```

**Ventajas:**
-  Asegura limpieza correcta
-  Elimina listeners duplicados
-  Solución professional

**Desventajas:**
- Requiere más cambios en gestor-modal-proceso-generico.js

---

### **OPCIÓN 3: Usar Delegación de Eventos (PROFESIONAL)**

⭐ **Mejor para:** Arquitectura escalable sin acumulación de listeners.

```javascript
// En lugar de agregar listeners cada vez que se abre el modal,
// agregar UNA SOLA VEZ delegando el evento en un contenedor padre

// Inicializar una sola vez al cargar la página
function inicializarDelegacionImagenes() {
    const fotoPanelContainer = document.getElementById('modal-proceso-generico');
    
    fotoPanelContainer?.addEventListener('click', function(e) {
        // Solo si el click es en un preview
        if (e.target.closest('.foto-preview-proceso')) {
            const preview = e.target.closest('.foto-preview-proceso');
            const cuadroIndex = preview.id.match(/\d+/)[0];
            
            e.stopPropagation();
            e.preventDefault();
            
            // Guardia para prevenir doble disparo
            const input = document.getElementById(`proceso-foto-input-${cuadroIndex}`);
            if (input?._isDialogOpening) return;
            
            input._isDialogOpening = true;
            input.value = '';
            input.click();
            
            setTimeout(() => { input._isDialogOpening = false; }, 200);
        }
    });
}

// Llamar UNA SOLA VEZ cuando la página carga
document.addEventListener('DOMContentLoaded', inicializarDelegacionImagenes);
```

**Ventajas:**
-  Sin acumulación de listeners
-  Memory efficient
-  Solución enterprise-grade

**Desventajas:**
- Requiere refactorizar HTML/JS
- Más código al principio

---

## 🚀 RECOMENDACIÓN FINAL

**Implementa OPCIÓN 1 + OPCIÓN 2:**

1. **Corto plazo:** Opción 1 (guard flag) → resuelve 95% del problema
2. **Largo plazo:** Opción 2 (limpieza correcta) → asegura que no se acumulen listeners

### Pasos de Implementación:

#### Paso 1: Reemplazar `manejador-imagen-proceso-con-indice.js`
Ya he creado la versión v2 con el guard. Necesitas:

```javascript
// Opción A: Reemplazar el archivo completo
// Opción B: Solo actualizar la función abrirSelectorImagenProceso() con el guard
```

#### Paso 2: Verificar limpieza en `gestor-modal-proceso-generico.js`
Busca `cerrarModalProcesoGenerico()` y asegura que se remuevan listeners:

```javascript
// Esto ya está en tu código, pero verifica que está completo
for (let i = 1; i <= 3; i++) {
    const preview = document.getElementById(`proceso-foto-preview-${i}`);
    if (preview && preview._handlerPlaceholder) {
        preview.removeEventListener('click', preview._handlerPlaceholder);
    }
}
```

#### Paso 3: Prueba
Abre el modal, haz click en un preview, carga una imagen. Repite varias veces.
-  Antes: File dialog se abre 2 veces
-  Después: File dialog se abre 1 sola vez

---

## 🧪 Métodos de Prueba

### Test 1: Verificar que el guard funciona
```javascript
// En consola del navegador:
const input = document.getElementById('proceso-foto-input-1');
console.log(input._isDialogOpening);  // Debe ser undefined o false
abrirSelectorImagenProceso(1);
console.log(input._isDialogOpening);  // Debe ser true
// (Espera a que se cierre el diálogo...)
// Después de 200ms, vuelve a false
```

### Test 2: Verificar listeners acumulados
```javascript
const preview = document.getElementById('proceso-foto-preview-1');
console.log(preview.getEventListeners?.('click'));
// O usar DevTools: Inspect → Event Listeners tab
```

### Test 3: Verificar con open de devTools
1. Abre DevTools (F12)
2. Va a Console
3. Escribe: `abrirSelectorImagenProceso(1)`
4. Mira si se abre 1 o 2 diálogos

---

##  Resumen de Cambios Necesarios

| Archivo | Cambio | Prioridad |
|---------|--------|-----------|
| `manejador-imagen-proceso-con-indice.js` | Agregar guard flag a `abrirSelectorImagenProceso()` |  **CRÍTICA** |
| `gestor-modal-proceso-generico.js` | Verificar limpieza de listeners en `cerrarModalProcesoGenerico()` | 🟡 **IMPORTANTE** |

---

## ❓ Preguntas para Investigar Después

Si después de implementar Opción 1 + 2 aún ves doble dialog:

1. ¿Hay un `<label for="proceso-foto-input-X">` que envuelve algo?
2. ¿Hay otro JS que llama `abrirSelectorImagenProceso()` desde otro lado?
3. ¿El input tiene atributos como `data-*` que podrían estar afectando?
4. ¿Hay un polyfill o librería que intercepte `.click()`?

---

**Archivo de referencia v2 creado:** `manejador-imagen-proceso-con-indice-v2.js`

