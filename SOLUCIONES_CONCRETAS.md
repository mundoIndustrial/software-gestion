# 🛠️ SOLUCIONES CONCRETAS - Tarjeta No Se Renderiza

**Versión:** 1.0 (Soluciones basadas en nuevo diagnóstico)  
**Fecha:** 15 de enero, 2026

---

## 🎯 PROBLEMA RESUMIDO

**Síntoma:** Prenda se agrega al gestor pero tarjeta NO aparece en la UI, mostrando "No hay ítems agregados"

**Causas Potenciales (en orden de probabilidad):**

1. ❌ **`obtenerActivas()` retorna array vacío** → Prendas están marcadas como eliminadas
2. ❌ **Container no existe o tiene ID diferente** → HTML sin el elemento correcto
3. ❌ **Error en `sincronizarDatosAntesDERenderizar()`** → Falla y detiene renderizado
4. ⚠️ **Procesos vacíos** → Usuario no marcó procesos en modal (secundario, no afecta renderizado)

---

## ✅ SOLUCIÓN 1: Verificar que `obtenerActivas()` No Filtra Prendas

### Problema
```javascript
obtenerActivas() {
    return this.prendas.filter((_, index) => !this.prendasEliminadas.has(index));
}
```

Si por alguna razón `prendasEliminadas` tiene el índice 0, filtrará la única prenda y retornará array vacío.

### Diagnóstico
Ejecuta en consola después de agregar prenda:
```javascript
const g = window.gestorPrendaSinCotizacion;
console.log('Totales:', g.prendas.length);
console.log('Eliminadas:', Array.from(g.prendasEliminadas));
console.log('Activas:', g.obtenerActivas().length);

// Si Totales=1, Eliminadas=[0], Activas=0 → ESE ES EL PROBLEMA
```

### Solución
Busca en todo el código si algo está llamando a `gestor.eliminar(0)` sin razón:

```bash
# Terminal:
grep -r "eliminar(0)" public/js/
grep -r ".eliminar" public/js/ | grep -v "// "
```

Si encuentras llamadas innecesarias, comenta o elimínalas.

---

## ✅ SOLUCIÓN 2: Verificar ID del Container

### Problema
El HTML puede no tener el elemento con ID `prendas-container-editable`

### Diagnóstico
```javascript
// En consola F12:
document.getElementById('prendas-container-editable')
// Si retorna null → PROBLEMA ENCONTRADO
```

### Solución A: Si el container tiene otro ID
1. Encuentra el ID correcto buscando en el HTML
2. Actualiza en `renderizador-prenda-sin-cotizacion.js` línea 472:

```javascript
// ANTES:
const container = document.getElementById('prendas-container-editable');

// DESPUÉS:
const container = document.getElementById('NUEVO_ID_AQUI');
```

### Solución B: Si no existe el container
1. Abre el archivo HTML (ej: `crear-pedido-nuevo.blade.php`)
2. Busca dónde deberían aparecer las prendas
3. Agrega el div:
```html
<div id="prendas-container-editable" style="margin-top: 2rem;">
    <!-- Las prendas se renderizarán aquí -->
</div>
```

---

## ✅ SOLUCIÓN 3: Debuggear `sincronizarDatosAntesDERenderizar()`

### Problema
Esta función se llama en línea 498 de `renderizador-prenda-sin-cotizacion.js` y podría fallar silenciosamente.

### Diagnóstico
```javascript
// En consola, después de agregar prenda:
try {
    window.sincronizarDatosAntesDERenderizar?.();
    console.log('✅ Sincronización OK');
} catch (error) {
    console.error('❌ Error en sincronización:', error);
}
```

### Solución
Si hay error, busca la función en `renderizador-prenda-sin-cotizacion.js` y revisa qué propiedad falta:

```javascript
// Agregar validaciones defensivas:
function sincronizarDatosAntesDERenderizar() {
    if (!window.gestorPrendaSinCotizacion) return;

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendas.forEach((prenda, prendaIndex) => {
        // ANTES (vulnerable a errores):
        const inputNombre = document.querySelector(`.prenda-nombre[data-prenda="${prendaIndex}"]`);
        if (inputNombre && inputNombre.value) {
            prenda.nombre_producto = inputNombre.value;
        }
        
        // DESPUÉS (más defensivo):
        try {
            const inputNombre = document.querySelector(`.prenda-nombre[data-prenda="${prendaIndex}"]`);
            if (inputNombre?.value?.trim()) {
                prenda.nombre_producto = inputNombre.value;
                console.log(`✅ Sincronizado nombre de prenda ${prendaIndex}`);
            }
        } catch (error) {
            console.warn(`⚠️ Error sincronizando prenda ${prendaIndex}:`, error);
        }
    });
}
```

---

## ✅ SOLUCIÓN 4: Procesos Vacíos (Secundaria)

### Problema Reportado
```
Procesos configurables (antes): {}
Procesos configurables (después): {}
```

### Causa
El usuario **NO está marcando procesos en el modal**.

### Solución A: Verificar que usuario marca procesos

**En el modal `modal-agregar-prenda-nueva.blade.php`:**
1. Usuario debe marcar checkbox: `☑️ Reflectivo`
2. Se debe abrir modal para configurar detalles
3. Usuario debe llenar detalles y guardar

Si los checkboxes no funcionan, revisa `manejadores-procesos-prenda.js`:

```javascript
// Línea que debe ejecutarse:
window.manejarCheckboxProceso = function(tipoProceso, estaChecked) {
    if (estaChecked) {
        procesosSeleccionados[tipoProceso] = {
            tipo: tipoProceso,
            datos: null  // Se llena en el modal genérico
        };
    } else {
        delete procesosSeleccionados[tipoProceso];
    }
};
```

### Solución B: Si procesos siempre están vacíos
Agrega logging en `agregarPrendaNueva()`:

```javascript
// Línea 262 en gestion-items-pedido.js - ANTES:
let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
console.log(`🎨 [GestionItemsUI] Procesos configurables (antes):`, procesosConfigurables);

// DESPUÉS - Agregar verificación:
let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
console.log(`🎨 [GestionItemsUI] Procesos configurables (antes):`, procesosConfigurables);

// DEBUG:
if (Object.keys(procesosConfigurables).length === 0) {
    console.warn('⚠️ ADVERTENCIA: Procesos vacíos. ¿Usuario marcó procesos?');
    console.log('   Procesos seleccionados:', window.procesosSeleccionados);
}
```

---

## 🧪 PLAN DE DEBUGGING PASO A PASO

### Paso 1: Ejecutar Script de Debug
1. Abre F12 (Consola)
2. Copia y pega todo el contenido de `public/js/debug-tarjeta-no-renderiza.js`
3. Presiona Enter

### Paso 2: Agregar Prenda
1. En la UI, completa el formulario
2. Click "Agregar Prenda"

### Paso 3: Ejecutar Diagnóstico
```javascript
debugDiagnosticoCompleto()
```

### Paso 4: Identificar Problema
El script dirá exactamente cuál es el problema:
- ❌ **PROBLEMA 1** → Prenda no se agregó
- ❌ **PROBLEMA 2** → Prendas están eliminadas (buscar `eliminar()`)
- ❌ **PROBLEMA 3** → Container no existe (revisar HTML)
- ❌ **PROBLEMA 4** → Container vacío (error en renderizado)
- ✅ **Sin problemas** → Todo OK

### Paso 5: Aplicar Solución Correspondiente
Según el problema identificado, aplica la solución de arriba.

---

## 📝 RESUMEN RÁPIDO

| Problema | Solución |
|----------|----------|
| Procesos vacíos {} | Usuario debe marcar procesos en modal |
| `obtenerActivas()` retorna vacío | Buscar `gestor.eliminar()` innecesario |
| Container no existe | Revisar HTML, agregar `<div id="prendas-container-editable">` |
| Container vacío | Revisar `sincronizarDatosAntesDERenderizar()` |

---

## ✅ CHECKLIST FINAL

- [ ] Ejecuté `debugDiagnosticoCompleto()` en consola
- [ ] Identifiqué qué problema reporta
- [ ] Aplicué la solución correspondiente
- [ ] Ahora la tarjeta aparece ✅
- [ ] Procesos también aparecen ✅

---

**Próximo paso:** Ejecuta el script de debug y reporta qué PROBLEMA identifica.
