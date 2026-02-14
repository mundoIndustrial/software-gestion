# 🚀 PLAN DE MIGRACIÓN EN 4 FASES - SISTEMA MODAL PRODUCCIÓN

## 📅 Estimación de Tiempo

- **Fase 1:** 1 hora (crear FSM, bajo riesgo, reversible)
- **Fase 2:** 2 horas (integrar en flujo existente, testing)
- **Fase 3:** 1.5 horas (remover listeners del Blade, verificar)
- **Fase 4:** 0.5 horas (monitoreo, logs)

**Total:** 4.5 horas de trabajo + 1 semana de monitoreo en producción

---

## FASE 1: Crear Mini FSM Ligera (SIN romper nada existente)

### Objetivo
Crear máquina de estados con 4 estados que coordine el ciclo de vida del modal SIN modificar código existente.

### Archivo a crear
`/public/js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js`

```javascript
/**
 * ================================================
 * MINI FSM PARA MODAL (Lightweight, sin sobreengeniería)
 * ================================================
 * 
 * OBJETIVO: Coordinar ciclo de vida del modal
 * - Evitar dobles aperturas
 * - Sincronizar catálogos + DragDrop + lifecycle
 * - Compatible con código existente (NO reescribir)
 * 
 * Estado machine MÍnimo:
 * CLOSED → OPENING → OPEN → CLOSING → CLOSED
 * 
 * @module ModalMiniFSM
 */

class ModalMiniFSM {
    constructor(modalId = 'modal-agregar-prenda-nueva') {
        this.modalId = modalId;
        this.estado = 'CLOSED';
        this.listeners = [];
        this._ultimaCambioOH = Date.now();
    }

    /**
     * Cambiar estado con validación mínima
     * @param {string} nuevoEstado
     * @returns {boolean} Éxito o fallo
     */
    cambiarEstado(nuevoEstado) {
        const transicionesValidas = {
            'CLOSED':  ['OPENING'],
            'OPENING': ['OPEN', 'CLOSED'],  // CLOSED = emergencia
            'OPEN':    ['CLOSING'],
            'CLOSING': ['CLOSED']
        };

        // Guard: transición inválida
        if (!transicionesValidas[this.estado]?.includes(nuevoEstado)) {
            console.warn(
                `[ModalFSM]  Transición inválida: ${this.estado} → ${nuevoEstado}`
            );
            return false;
        }

        const estadoAnterior = this.estado;
        this.estado = nuevoEstado;
        this._ultimaCambioOH = Date.now();

        console.log(
            `[ModalFSM]  ${estadoAnterior} → ${nuevoEstado}`
        );

        // Notificar listeners
        this.listeners.forEach(cb => {
            try {
                cb(nuevoEstado, estadoAnterior);
            } catch (error) {
                console.error('[ModalFSM] Error en listener:', error);
            }
        });

        return true;
    }

    /**
     * Obtener estado actual
     */
    obtenerEstado() {
        return this.estado;
    }

    /**
     * Verificar si es seguro abrir modal
     */
    puedeAbrir() {
        return this.estado === 'CLOSED' || this.estado === 'OPENING';
    }

    /**
     * Registrar listener para cambios de estado
     */
    onStateChange(callback) {
        this.listeners.push(callback);
        
        // Retornar función para desuscribirse
        return () => {
            const idx = this.listeners.indexOf(callback);
            if (idx > -1) this.listeners.splice(idx, 1);
        };
    }
}

// SINGLETON GLOBAL (excepto por esta línea, inyectada explícitamente)
if (!window.__MODAL_FSM__) {
    window.__MODAL_FSM__ = new ModalMiniFSM('modal-agregar-prenda-nueva');
    console.log('[ModalFSM]  Singleton inicializado');
}
```

### Paso 1.1: Cargar archivo en el Blade

En `/resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php`, agregar al final (antes de `</script>`):

```blade
<!-- FSM Lightweight - Coordina ciclo de vida del modal -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js') }}"></script>
```

### Paso 1.2: Verificar que funciona

Abrir consola del navegador y ejecutar:
```javascript
window.__MODAL_FSM__.cambiarEstado('OPENING');
// Output: [ModalFSM]  CLOSED → OPENING

window.__MODAL_FSM__.obtenerEstado();
// Output: 'OPENING'

// Intentar transición inválida
window.__MODAL_FSM__.cambiarEstado('CLOSED');
// Output: [ModalFSM]  OPENING → CLOSED (esto SÍ es válido)
```

 **Resultado esperado:** FSM funciona sin romper nada existente.

---

## FASE 2: Integrar FSM en flujo de abrirModalAgregarPrendaNueva()

### Objetivo
Usar FSM para orquestar: catálogos → modal visible → DragDrop init

### Archivo a modificar
`/public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js` línea 309+

**CAMBIO MÍNIMO:** Envolver el flujo existente con FSM

```javascript
async abrirModalAgregarPrendaNueva() {
    try {
        // 🔒 NUEVO: Guard con FSM
        const fsm = window.__MODAL_FSM__;
        if (!fsm.puedeAbrir()) {
            console.warn(
                `[abrirModalAgregarPrendaNueva] Modal ya está en estado: ${fsm.obtenerEstado()}`
            );
            return;
        }
        
        // 1️⃣ Transicionar a OPENING
        fsm.cambiarEstado('OPENING');
        
        console.log('[abrirModalAgregarPrendaNueva] INICIO');
        
        // 2️⃣ Cargar catálogos (código EXISTENTE - sin cambios)
        if (typeof window.cargarCatalogosModal === 'function') {
            console.log('[abrirModalAgregarPrendaNueva] Cargando catálogosO...');
            await window.cargarCatalogosModal();
            console.log('[abrirModalAgregarPrendaNueva]  Catálogos cargados');
        }
        
        // 3️⃣ Determinar si es edición o creación (código EXISTENTE - sin cambios)
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        
        if (esEdicion) {
            console.log('[abrirModalAgregarPrendaNueva] ✏️ EDICIÓN - index:', this.prendaEditIndex);
            const prendaAEditar = this.prendas[this.prendaEditIndex];
            if (prendaAEditar && this.prendaEditor) {
                this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
            }
        } else {
            console.log('[abrirModalAgregarPrendaNueva] ➕ CREACIÓN - Modal vacío');
            if (this.prendaEditor) {
                this.prendaEditor.abrirModal(false, null);
            }
        }
        
        // 4️⃣ NUEVO: Esperar a que el modal esté visible
        // Esto permite que DragDrop se inicialice sobre un DOM estable
        await this._esperarModalVisible(1000);
        
        // 5️⃣ NUEVO: Inicializar DragDrop (AQUÍ, no en DOMContentLoaded)
        if (window.DragDropManager) {
            window.DragDropManager.inicializar();
        }
        
        // 6️⃣ Transicionar a OPEN
        fsm.cambiarEstado('OPEN');
        
        console.log('[abrirModalAgregarPrendaNueva]  ÉXITO - Modal abierto');
        
    } catch (error) {
        // En error, resetear a CLOSED
        const fsm = window.__MODAL_FSM__;
        fsm.cambiarEstado('CLOSED');
        
        console.error('[abrirModalAgregarPrendaNueva]  ERROR:', error);
        if (typeof NotificationService !== 'undefined') {
            NotificationService.error('Error abriendo modal: ' + error.message);
        }
    }
}

/**
 * Esperar a que el modal esté visible en el DOM
 * @private
 */
async _esperarModalVisible(timeoutMs = 1000) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) {
            console.warn('[_esperarModalVisible] Modal no encontrado en DOM');
            resolve();
            return;
        }

        // Comprobar cada 50ms
        const intervalo = setInterval(() => {
            const isVisible = 
                modal.style.display !== 'none' && 
                modal.offsetHeight > 0;
            
            if (isVisible) {
                clearInterval(intervalo);
                console.log('[_esperarModalVisible]  Modal visible');
                resolve();
            }
        }, 50);

        // Timeout de seguridad
        setTimeout(() => {
            clearInterval(intervalo);
            console.warn('[_esperarModalVisible]  Timeout esperando modal');
            resolve();  // Continuar de todas formas
        }, timeoutMs);
    });
}
```

### Paso 2.1: Remover listener de shown.bs.modal del Blade (si existe)

En `/resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php`, buscar y **COMENTAR** (no eliminar):

```javascript
//  COMENTAR ESTA SECCIÓN (línea ~683-700)
/*
if (window.DragDropManager) {
    try {
        window.DragDropManager.inicializar();
        console.log('[DragDrop]  Sistema inicializado correctamente');
        // ...
    }
}
*/
```

### Paso 2.2: Verificar que funciona

1. Abrir DevTools (F12)
2. Hacer clic en "Agregar nueva prenda"
3. Observar los logs:
   ```
   [ModalFSM]  CLOSED → OPENING
   [abrirModalAgregarPrendaNueva] INICIO
   [abrirModalAgregarPrendaNueva] Cargando catálogos...
   [abrirModalAgregarPrendaNueva]  Catálogos cargados
   [_esperarModalVisible]  Modal visible
   [DragDropManager]  Ya inicializado (solo primera vez)
   [ModalFSM]  OPENING → OPEN
   [abrirModalAgregarPrendaNueva]  ÉXITO
   ```

 **Resultado esperado:** Modal abre correctamente, DragDrop se init UNA SOLA VEZ, no hay dobles fetch.

---

## FASE 3: Remover lógica de inicialización del Blade

### Objetivo
Eliminar los listeners y MutationObserver del Blade que causaban triggers múltiples.

### Archivo a modificar
`/resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php` línea 830+

**REMOVER ESTAS LÍNEAS:**

```javascript
//  REMOVER: MutationObserver (línea ~830-860)
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.style.display !== 'none') {
                window.inicializarDragDropModalPrenda();
            }
        }
    });
});
const modal = document.getElementById('modal-agregar-prenda-nueva');
if (modal) {
    observer.observe(modal, { attributes: true });
}

//  REMOVER: Event listener personalizado
document.addEventListener('modalPrendaAbierto', function() {
    window.inicializarDragDropModalPrenda();
});
```

**¿Por qué es seguro remover esto?**
- Ahora FSM + GestionItemsUI controlan todo
- El DragDropManager.inicializar() tiene guard clause que previene dobles init
- Los listeners están centralizados, no dispersos

### Paso 3.1: Validar rollback rápido

Si algo falla, revertir:
```bash
git diff public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js
git checkout -- resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php
```

 **Resultado esperado:** Sistema funciona igual, pero más limpio y sin race conditions.

---

## FASE 4: Monitoreo y Estabilización

### Qué monitorear en producción

1. **Doble ejecución:** Verificar que logs no muestren duplicados
   - Usar: `grep -r "Telas cargadas en memoria" console.log`
   - Debe ser exactamente UNA vez por apertura

2. **Memory leaks:** Abrir/cerrar modal 10 veces, observar memoria
   - Debe mantenerse estable (no crecer)
   - Dev Tools → Memory tab

3. **DragDrop funcional:** Probar drag/drop en cada zona
   - Prenda, Tela, Proceso

4. **Error rate:** Monitorear errores JS en herramientas de logging
   - Si aparecen nuevos errores, es que algo rompió

### Comando para auditoría rápida

```javascript
// En consola
console.log('Estado FSM:', window.__MODAL_FSM__.obtenerEstado());
console.log('DragDrop inicializado:', window.DragDropManager?.inicializado);
console.log('Catálogos en memoria:', {
    telas: window.telasDisponibles?.length || 0,
    colores: window.coloresDisponibles?.length || 0
});
```

---

## 📊 MATRIZ DE CAMBIOS

| Fase | Cambio | Archivo | Líneas | Riesgo | Reversible |
|------|--------|---------|--------|--------|-----------|
| 1 | Crear FSM | NUEVO | 80 | 🟢 NULO | Sí (borrar archivo) |
| 2 | Integrar FSM en gestionItemsUI | gestion-items-pedido.js | 309-380 | 🟢 BAJO | Sí (git reset) |
| 3 | Remover Blade listeners | modal-agregar-prenda-nueva.blade.php | 683-860 | 🟡 MEDIO | Sí (git revert) |
| 4 | Monitoreo | N/A | N/A | 🟢 NULO | N/A |

---

##  PUNTOS CRÍTICOS A VALIDAR

### Antes de Fase 3:
- [ ] Modal abre correctamente
- [ ] DragDrop funciona con drag/drop real
- [ ] Catálogos cargan correctamente (solo UNA vez)
- [ ] No hay errores en consola

### Después de Fase 3:
- [ ] Modal sigue abriendo correctamente
- [ ] DragDrop SIGUE FUNCIONANDO
- [ ] No hay memory leaks (abrir/cerrar 10 veces)

### En producción (1 semana):
- [ ] Error rate estable
- [ ] No hay reportes de usuarios sobre modal lento
- [ ] Performance: tiempo de apertura < 500ms

---

## 🚨 ROLLBACK RÁPIDO

Si algo falla EN CUALQUIER MOMENTO:

```bash
# Opción 1: Revertir solo gestion-items-pedido.js
git checkout HEAD -- public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js

# Opción 2: Revertir solo el Blade
git checkout HEAD -- resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php

# Opción 3: Rollback completo
git revert HEAD~3..HEAD

# Opción 4: Eliminador de FSM (último recurso)
rm public/js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js
# Descomentar listener del Blade
```

---

**Documento:** Plan de Migración Incremental  
**Fecha:** 2026-02-13  
**Nivel:** Production Ready - Bajo Riesgo
