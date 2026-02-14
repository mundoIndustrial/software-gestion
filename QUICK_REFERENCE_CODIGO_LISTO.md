#  QUICK REFERENCE - CÓDIGO LISTO PARA PRODUCCIÓN

##  ÍNDICE DE CAMBIOS

| Archivo | Cambio | Fase | Estado |
|---------|--------|------|--------|
| `promise-cache.js` | Crear (nuevo) | 1 |  Listo |
| `manejadores-variaciones.js` | Refactorizar `cargarCatalogosModal()` | 1 |  Listo |
| `gestion-items-pedido.js` | Hacer async `abrirModalAgregarPrendaNueva()` | 1 |  Listo |
| `drag-drop-manager.js` | Guard clause en `inicializar()` | 1 |  Listo |
| `modal-listener-registry.js` | Crear (nuevo) | 2 | ⏳ A implementar |
| `modal-fsm.js` | Crear (nuevo) | 3 | ⏳ A implementar |
| `catalog-sync.js` | Crear (nuevo) | 3 | ⏳ A implementar |
| `modal-lifecycle.js` | Crear (nuevo) | 3 | ⏳ A implementar |
| `modal-system.js` | Crear (nuevo) | 3 | ⏳ A implementar |

---

## 🚀 FASE 1 EN 5 MINUTOS

### 1. Crear `promise-cache.js`
Copiar contenido desde `IMPLEMENTACION_FASE1_PASO_A_PASO.md` → sección "Paso 1"

### 2. Actualizar HTML
```html
<script src="{{ asset('public/js/modulos/crear-pedido/prendas/promise-cache.js') }}"></script>
<script src="{{ asset('public/js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>
```

### 3. Verificar en Console
```javascript
typeof PromiseCache  // "object"
PromiseCache.getStatus()  // { size: 0, keys: [], ... }
```

### 4. Testing rápido
```javascript
// En console
await window.cargarCatalogosModal();
await window.cargarCatalogosModal(); // Debe reutilizar promise
```

---

## 🧩 INTEGRACIÓN CON CÓDIGO EXISTENTE

### Patrón 1: Dentro de clase (async/await)
```javascript
class GestionItemsUI {
    async abrirModalAgregarPrendaNueva() {
        try {
            //  Espera a que catálogos carguen
            await window.cargarCatalogosModal();
            
            // 🎯 Abrir modal cuando todo está listo
            this.prendaEditor.abrirModal(false, null);
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

// Uso
await this.gestionItemsUI.abrirModalAgregarPrendaNueva();
```

### Patrón 2: Evento onclick (IIFE async)
```html
<button onclick="(async () => {
    try {
        await window.gestionItemsUI.abrirModalAgregarPrendaNueva();
    } catch (error) {
        console.error(error);
    }
})()">
    Agregar Prenda
</button>
```

### Patrón 3: Event listener (async)
```javascript
document.getElementById('btn-agregar-prenda').addEventListener('click', async (e) => {
    e.preventDefault();
    
    try {
        await window.gestionItemsUI.abrirModalAgregarPrendaNueva();
    } catch (error) {
        console.error('Error abriendo modal:', error);
        alert('Error: ' + error.message);
    }
});
```

### Patrón 4: Promise chain (si no puedes usar async/await)
```javascript
window.gestionItemsUI.abrirModalAgregarPrendaNueva()
    .then(() => {
        console.log('Modal abierto');
    })
    .catch(error => {
        console.error('Error:', error);
    });
```

---

## 🔍 DEBUGGING - COMANDOS ÚTILES

### Ver si hay promesas en cache
```javascript
PromiseCache.getStatus()
// Retorna: { size: 1, keys: ['catalogs:telas-colores'], timestamp: '...' }
```

### Forzar limpiar cache (emergencia)
```javascript
PromiseCache.clear()
```

### Ver historial de FSM (Fase 3)
```javascript
window.__MODAL_STATE_MACHINE__.getHistory()
```

### Ver estado del modal (Fase 3)
```javascript
window.__MODAL_SYSTEM__.getStatus()
```

### Ver listeners registrados (Fase 2)
```javascript
ModalListenerRegistry.getStatus()
```

---

## 🐛 TROUBLESHOOTING

### Problema: "PromiseCache is not defined"
**Causa:** No se cargó `promise-cache.js`  
**Solución:** Verificar orden de scripts en HTML
```html
<!--  CORRECTO -->
<script src="promise-cache.js"></script>
<script src="manejadores-variaciones.js"></script>

<!--  INCORRECTO -->
<script src="manejadores-variaciones.js"></script>
<script src="promise-cache.js"></script>
```

### Problema: Modal abre sin catálogos (dropdown vacío)
**Causa:** No se agregó `await` en caller  
**Solución:**
```javascript
//  INCORRECTO
this.gestionItemsUI.abrirModalAgregarPrendaNueva();

//  CORRECTO
await this.gestionItemsUI.abrirModalAgregarPrendaNueva();
```

### Problema: "Listener ya registrado" warnings
**Este es esperado en Fase 1.** Se arregla en Fase 2.
```javascript
// Mensaje:
[ModalListeners]  Listener ya registrado

// Fase 2 lo elimina completamente
```

### Problema: API calls se duplican igual
**Verificar:**
1. ¿Hay 2+ calls a `abrirModalAgregarPrendaNueva()` simultáneamente?
2. ¿El `await` se agregó correctamente?
3. ¿Se cargó `promise-cache.js` antes de `manejadores-variaciones.js`?

**Debug:**
```javascript
// En console, cuando abre modal:
PromiseCache.getStatus()
// Si size > 1, hay problema con dedup
```

---

## 📊 LOGGING PATTERNS

### Formato de logs esperados (Fase 1)
```
[PromiseCache] Promise guardada { key: 'catalogs:telas-colores', size: 1 }
[Catálogos] Iniciando carga de catálogos...
[Telas] Iniciando carga de telas disponibles...
[Telas] Respuesta de API...
[Colores] Iniciando carga de colores disponibles...
[Colores] Respuesta de API...
[Catálogos]  Ambos catálogos cargados { telas: 48, colores: 25 }
[PromiseCache] Promise limpiada automáticamente
[abrirModalAgregarPrendaNueva]  Catálogos cargados correctamente
[abrirModalAgregarPrendaNueva] ➕ CREACIÓN - Abriendo modal vacío para nueva prenda
[abrirModalAgregarPrendaNueva]  ÉXITO - Modal abierto correctamente
[Modal] Modal completamente visible
```

### Cómo detectar problemas
```javascript
// Si ves esto, hay problema de dedup:
[PromiseCache] Promise guardada
[Catálogos] Iniciando carga...
[PromiseCache] Promise guardada  // ← Debe reutilizar, no guardar 2 veces
[Catálogos] Iniciando carga...   // ← Debe aparecer 1 vez, no 2

// Solución: Verificar que PromiseCache se reutiliza
console.log(PromiseCache.size())  // Debe ser 0 o 1, no más
```

---

## 🧪 TEST CHECKLIST RÁPIDO

```
Abrir modal:
☐ Logs de dedup aparecen
☐ Network: /api/public/telas - 1 call
☐ Network: /api/public/colores - 1 call
☐ Modal se abre
☐ Catálogos están llenos

Abrir modal OTRA VEZ (reapertura):
☐ [PromiseCache] Promise en flight, reutilizando
☐ Network: Sin nuevos calls (caché)
☐ Modal se abre más rápido

Hacer click múltiples veces rápido:
☐ Solo un fetch se ejecuta
☐ Sin error en console
☐ Modal se abre cuando está listo

Cerrar y abrir:
☐ [DragDropManager] Ya inicializado, ignorando llamada duplicada
☐ Modal funciona igual
☐ Sin memory leaks visibles
```

---

## 🔄 MIGRANDO DESDE ANTIGUA ARQUITECTURA

### Antigua forma
```javascript
// Múltiples puntos, no coordinados
if (!window._modalAbierto) {
    window.cargarCatalogosModal().catch(err => alert(err));
    this.prendaEditor.abrirModal();
    window._modalAbierto = true;
}
```

### Nueva forma (Fase 1)
```javascript
// Coordinado, seguro, esperando catálogos
try {
    await window.cargarCatalogosModal();
    this.prendaEditor.abrirModal();
} catch (error) {
    console.error('Error:', error);
}
```

### Forma final (Fase 3)
```javascript
// Centralizado, con FSM, idempotente
try {
    await window.__MODAL_SYSTEM__.abrirParaCrear();
} catch (error) {
    console.error('Error:', error);
}
```

---

## 📝 CHECKLIST DE DEPLOYMENT

### Pre-deploy
- [ ] Todos los tests locales pasaron
- [ ] Console limpia (sin errores)
- [ ] Network muestra dedup (1 api call)
- [ ] Modal funciona 10 veces sin problemas
- [ ] Probado en Chrome, Firefox, Safari

### Deploy
- [ ] Crear rama `feature/fase1-dedup`
- [ ] Push a repositorio
- [ ] Crear Pull Request
- [ ] Code review aprobado
- [ ] Merge a main/master
- [ ] Deploy a staging
- [ ] Testing en staging (1h)
- [ ] Deploy a producción

### Post-deploy
- [ ] Monitorear console en producción (24h)
- [ ] Verificar Network requests bajan
- [ ] Verificar no hay aumentos de error logs
- [ ] Feedback de usuarios

---

## 💡 TIPS Y TRICKS

### Tip 1: Usar tilde (~) para limpiar cache
```javascript
// Si necesitas forzar recarga de catálogos:
PromiseCache.delete('catalogs:telas-colores');
// Próxima llamada hará fetch real

// Fase 3:
window.__MODAL_SYSTEM__.resetCatalogs('telas');
```

### Tip 2: Agregar loading indicator
```javascript
async abrirModal() {
    const btn = document.getElementById('btn-agregar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Cargando...';
    
    try {
        await window.cargarCatalogosModal();
        this.prendaEditor.abrirModal();
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Agregar Prenda';
    }
}
```

### Tip 3: Timeout seguro (si es necesario)
```javascript
const timeoutPromise = new Promise((resolve, reject) => {
    setTimeout(() => reject(new Error('Timeout')), 5000);
});

Promise.race([
    window.cargarCatalogosModal(),
    timeoutPromise
])
.catch(error => {
    console.error('Catálogos tardaron demasiado:', error);
});
```

---

## 📞 CONTACTO Y ESCALACIÓN

**Preguntas sobre Fase 1:**
- Revisar `PLAN_MIGRACION_INCREMENTAL.md` sección "Riesgos"
- Revisar logs en console
- Hacer rollback si hay problemas (5 minutos)

**Preguntas sobre arquitectura:**
- Revisar `ARQUITECTURA_MODAL_ANALYSIS.md`
- Revisar `RESUMEN_EJECUTIVO_SOLUCION.md`

**Problemas no esperados:**
- Hacer rollback: `git reset --hard HEAD~1`
- Crear issue con logs y pasos reproducción
- Contactar al architect

---

**Última actualización:** 2026-02-13  
**Versión:** 1.0.0  
**Mantenido por:** Software Architect Senior
