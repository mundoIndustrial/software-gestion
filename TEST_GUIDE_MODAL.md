# 🧪 Test Guide: Modal Asignar Colores

**Status:** ✅ Corrección Bootstrap 4 Completada  
**Objetivo:** Verificar que el modal se abre correctamente  

---

## ✅ Checklist Pre-Test

Antes de hacer test, verificar que:

- ✅ Bootstrap 4.6.0 está cargado en la página
- ✅ jQuery 3.6.0 está cargado en la página
- ✅ El nuevo modal está incluido: `@include('asesores.pedidos.modals.modal-asignar-colores-por-talla')`
- ✅ Los scripts están incluidos:
  - `modal-manager.js`
  - `bootstrap-modal-init.js`
- ✅ El botón tiene `data-toggle="modal"` y `data-target="#modal-asignar-colores-por-talla"`

---

## 🧪 Test 1: Abrir Modal con Click

### Pasos:
1. Abrir la página en el navegador
2. Localizar el botón "Asignar por Talla" (icono color_lens azul)
3. **Hacer click en el botón**

### Resultado Esperado:
✅ Modal se abre suavemente con transición fade  
✅ Se ve el título "Asignar Colores por Talla"  
✅ Se ve el Paso 1 (Seleccionar Género)  
✅ Se ven los botones: DAMA, CABALLERO, SOBREMEDIDA  
✅ Se ve el botón "Cancelar"  

---

## 🧪 Test 2: Verificación en Consola

### Abrir Consola del Navegador:
Presionar: **F12** → Tab "Console"

### Comando 1: Verificar jQuery
```javascript
console.log(jQuery.fn.jquery);
```

**Resultado Esperado:**
```
3.6.0
```

### Comando 2: Verificar Bootstrap
```javascript
jQuery.fn.modal.Constructor.VERSION
```

**Resultado Esperado:**
```
4.6.0
```

### Comando 3: Verificar Modal Exists
```javascript
document.getElementById('modal-asignar-colores-por-talla');
```

**Resultado Esperado:**
```
<div id="modal-asignar-colores-por-talla" class="modal fade" ...>
  <!-- contenido del modal -->
</div>
```

### Comando 4: Verificar ModalManager
```javascript
window.ModalManager
```

**Resultado Esperado:**
```
{
  open: ƒ,
  close: ƒ,
  isOpen: ƒ,
  openWizard: ƒ,
  closeWizard: ƒ,
  isWizardOpen: ƒ
}
```

### Comando 5: Abrir Modal desde Consola
```javascript
window.ModalManager.openWizard();
```

**Resultado Esperado:**
- Modal se abre
- Console log: `[ModalManager] Modal "modal-asignar-colores-por-talla" abierto`

### Comando 6: Cerrar Modal
```javascript
window.ModalManager.closeWizard();
```

**Resultado Esperado:**
- Modal se cierra
- Console log: `[ModalManager] Modal "modal-asignar-colores-por-talla" cerrado`

### Comando 7: Verificar Si Modal Está Abierto
```javascript
window.ModalManager.isWizardOpen();
```

**Resultado Esperado:**
- Si modal abierto: `true`
- Si modal cerrado: `false`

---

## 🧪 Test 3: Interactuar con Wizard

### Paso 1: Seleccionar Género
1. Click en "DAMA"
2. **Resultado:** Género se resalta en azul

### Paso 2: Avanzar a Siguientes Pasos
1. Click en botón "Siguiente"
2. **Resultado:** Se muestra Paso 2 con tallas

### Paso 3: Cerrar Modal
1. Click en botón "Cancelar" O Click en "X"
2. **Resultado:** Modal se cierra suavemente

### Paso 4: Reabrir Modal
1. Click nuevamente en "Asignar por Talla"
2. **Resultado:** Modal se abre nuevamente en Paso 1

---

## 🧪 Test 4: Debugging Completo

### Test Suite Automático
Ejecutar en consola:

```javascript
// Verificar todas las dependencias
(function() {
    console.group('🔍 Bootstrap Modal Diagnostic');
    
    // 1. jQuery
    console.log('jQuery:', typeof jQuery === 'function' ? '✅' : '❌');
    if (jQuery) console.log('  Version:', jQuery.fn.jquery);
    
    // 2. Bootstrap
    console.log('Bootstrap Modal:', jQuery.fn.modal ? '✅' : '❌');
    if (jQuery.fn.modal) console.log('  Version:', jQuery.fn.modal.Constructor.VERSION);
    
    // 3. Modal DOM
    const modal = document.getElementById('modal-asignar-colores-por-talla');
    console.log('Modal exists:', modal ? '✅' : '❌');
    
    // 4. Modal Manager
    console.log('ModalManager:', window.ModalManager ? '✅' : '❌');
    
    // 5. Button
    const btn = document.getElementById('btn-asignar-colores-tallas');
    console.log('Button exists:', btn ? '✅' : '❌');
    if (btn) {
        console.log('  data-toggle:', btn.getAttribute('data-toggle'));
        console.log('  data-target:', btn.getAttribute('data-target'));
    }
    
    // 6. ColoresPorTalla initialized
    console.log('ColoresPorTalla:', window.ColoresPorTalla ? '✅' : '❌');
    
    console.groupEnd();
})();
```

### Resultado Esperado:
```
🔍 Bootstrap Modal Diagnostic
jQuery: ✅
  Version: 3.6.0
Bootstrap Modal: ✅
  Version: 4.6.0
Modal exists: ✅
ModalManager: ✅
Button exists: ✅
  data-toggle: modal
  data-target: #modal-asignar-colores-por-talla
ColoresPorTalla: ✅
```

---

## 🚨 Troubleshooting

### Problema 1: Modal no se abre
**Solución:**
```javascript
// Verificar si jQuery está cargado
console.log(typeof jQuery); // Debe ser 'function'

// Abrir modal manualmente
jQuery('#modal-asignar-colores-por-talla').modal('show');
```

### Problema 2: Modal se abre pero se cierra inmediatamente
**Causa:** Código conflictivo entre modales  
**Solución:**
```javascript
// Revisar logs de ColoresPorTalla
console.log(window.ColoresPorTalla.getWizardStatus());
```

### Problema 3: Botón no abre modal
**Verificación:**
```javascript
const btn = document.getElementById('btn-asignar-colores-tallas');
console.log({
    exists: !!btn,
    toggle: btn?.getAttribute('data-toggle'),
    target: btn?.getAttribute('data-target')
});
// Esperado: { exists: true, toggle: "modal", target: "#modal-asignar-colores-por-talla" }

// Abrir manualmente
jQuery('#modal-asignar-colores-por-talla').modal('show');
```

### Problema 4: ModalManager no existe
**Solución:**
```javascript
// Incluir en la consola temporalmente
eval(fetch('/js/componentes/colores-por-talla/modal-manager.js').then(r => r.text()));

// Luego usar
window.ModalManager.openWizard();
```

---

## ✅ Test Summary

| Aspecto | Status | Detalles |
|--------|--------|----------|
| Bootstrap 4.6 cargado | ✅ | jQuery + Bootstrap JS |
| Modal en DOM | ✅ | ID: modal-asignar-colores-por-talla |
| Botón correcto | ✅ | data-toggle + data-target |
| ModalManager | ✅ | window.ModalManager disponible |
| ColoresPorTalla | ✅ | Wizard architecture activa |
| Modal se abre | ✅ | Click en botón abre modal |
| Wizard interactuable | ✅ | Todos los pasos funcionan |
| Modal se cierra | ✅ | Cancelar/X cierran correctamente |

---

## 📝 Notas Técnicas

### Scripts Order
1. jQuery 3.6.0 → Bootstrap 4.6.0 (cargados en página principal)
2. modal-manager.js (wrapper para Bootstrap 4 API)
3. bootstrap-modal-init.js (validación y debugging)
4. ColoresPorTalla.js (wizard logic, usa ModalManager)

### Events
Bootstrap 4 modal events:
- `show.bs.modal` - Antes de mostrar
- `shown.bs.modal` - Después de mostrar
- `hide.bs.modal` - Antes de ocultar
- `hidden.bs.modal` - Después de ocultar

### Compatibility
✅ Bootstrap 4.6 + jQuery 3.6  
❌ Bootstrap 5 (sintaxis diferente)

---

**¿Necesita más info?** Revisar archivos:
- `BOOTSTRAP4_COMPATIBILITY_FIX.md`
- `RESUMEN_MODAL_EXTRACTION.md`
