# 🎯 GUÍA DE IMPLEMENTACIÓN PASO A PASO - MUY CONCRETA

## Resumen Ejecutivo para el Equipo

**Problema:** Doble ejecución de funciones en modal-agregar-prenda-nueva  
**Causa:** Lógica dispersa entre Blade + JavaScript sin coordinación  
**Solución:** 3 cambios quirúrgicos + 1 archivo nuevo  
**Tiempo:** 2-3 horas incluyendo testing  
**Risk:** 🟢 BAJO - Cambios aislados, reversibles  

---

## PASO 1: Verificar estado actual (30 minutos)

### Paso 1.1: Reproducir el problema

1. Abrir navegador (Chrome/Firefox)
2. Abrir Dev Tools (F12)
3. Ir a la pestaña "Crear Pedido"
4. Hacer clic en "Agregar nueva prenda"
5. **Observar la consola:**

```
[Telas] Iniciando carga...
[abrirModalAgregarPrendaNueva] CREACIÓN...  ← Llamada 1
[Telas] Respuesta de API... ← Fetch 1
[Telas] Respuesta de API... ← Fetch 2 (DUPLICADO)
[DragDropManager] Sistema ya inicializado
[DragDrop] Sistema inicializado... (continúa igual)
```

**Confirmación:** Si ves "Respuesta de API..." dos veces, tienes el problema.

### Paso 1.2: Ejecutar comando de auditoría

En la consola del navegador:

```javascript
// Copiar/pegar esto en consola:
console.log('=== AUDITORÍA MODAL ===');
console.log('FSM disponible:', !!window.__MODAL_FSM__);
console.log('DragDropManager disponible:', !!window.DragDropManager);
console.log('DragDropManager.inicializado:', window.DragDropManager?.inicializado);
console.log('window.telasDisponibles:', window.telasDisponibles?.length || 0);
console.log('window.coloresDisponibles:', window.coloresDisponibles?.length || 0);
console.table({
    'FSM cargado': !!window.__MODAL_FSM__,
    'DragDropManager cargado': !!window.DragDropManager,
    'Catálogos cargados': !!window.telasDisponibles
});
```

**Esperado:** FSM no existe todavía, DragDropManager existe.

---

## PASO 2: Crear archivo FSM (15 minutos)

### Paso 2.1: Crear la carpeta si no existe

```bash
mkdir -p c:\Users\Usuario\Documents\mundoindustrial\public\js\modulos\crear-pedido\prendas\core
```

### Paso 2.2: Crear el archivo `/public/js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js`

**Ya lo creamos en los pasos anteriores.**

Verificar que existe:
```bash
ls -la c:\Users\Usuario\Documents\mundoindustrial\public\js\modulos\crear-pedido\prendas\core\modal-mini-fsm.js
```

### Paso 2.3: Verificar contenido del archivo

El archivo debe contener:
- Constructor de `ModalMiniFSM`
- Método `cambiarEstado()`
- Método `puedeAbrir()`
- Método `onStateChange()`
- Singleton en `window.__MODAL_FSM__`

Si no está, crear según modelo en `CODIGO_INTEGRACION_FSM.md`.

---

## PASO 3: Cargar FSM en el Blade (10 minutos)

### Paso 3.1: Abrir el archivo del modal Blade

Ruta:
```
resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php
```

### Paso 3.2: Ir al final del archivo (última línea)

Encontrar: `</script>` (debe estar cerca de la línea 900+)

### Paso 3.3: Agregar ANTES de `</script>`:

```html
<!-- ====================================== -->
<!-- FSM PARA CONTROL DE CICLO DE VIDA     -->
<!-- ====================================== -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js') }}"></script>
```

**Orden correcto de scripts en el Blade:**
1. Dependencias (UIHelperService, etc)
2. Handlers (PrendaDragDropHandler, TelaDragDropHandler, etc)
3. DragDropManager
4. **modal-mini-fsm.js** ← AQUÍ
5. Otros scripts

### Paso 3.4: Verificar carga en navegador

1. Refrescar página (Ctrl+F5)
2. Abrir consola (F12)
3. Ejecutar:
```javascript
console.log('FSM disponible:', window.__MODAL_FSM__ ? '' : '');
```

**Esperado:**  (Si aparece , verificar ruta del script)

---

## PASO 4: Modificar GestionItemsUI.abrirModalAgregarPrendaNueva() (45 minutos)

### Paso 4.1: Abrir archivo

```
public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js
```

### Paso 4.2: Encontrar la función (línea ~309)

Buscar en VSCode: `Ctrl+F` → `async abrirModalAgregarPrendaNueva()`

### Paso 4.3: Reemplazar el cuerpo de la función

**ANTES (línea 309-350):**
```javascript
async abrirModalAgregarPrendaNueva() {
    try {
        console.log('[abrirModalAgregarPrendaNueva] INICIO - Abriendo modal de prenda');
        
        if (typeof window.cargarCatalogosModal === 'function') {
            console.log('[abrirModalAgregarPrendaNueva] Cargando catálogos...');
            await window.cargarCatalogosModal();
            console.log('[abrirModalAgregarPrendaNueva]  Catálogos cargados correctamente');
        }
        
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        
        if (esEdicion) {
            // ... resto del código
        } else {
            // ... resto del código
        }
    } catch (error) {
        // ... error handling
    }
}
```

**DESPUÉS (usando el código de integración):**

Copiar desde `CODIGO_INTEGRACION_FSM.md` el método `abrirModalAgregarPrendaNueva()` completo y pegarlo aquí.

### Paso 4.4: Agregar método auxiliar

Después de `abrirModalAgregarPrendaNueva()`, agregar:

```javascript
/**
 * Auxiliar: Esperar a que el modal sea visible
 * @private
 */
async _esperarModalVisible(timeoutMs = 1500) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        
        if (!modal) {
            console.warn('[_esperarModalVisible] Modal no encontrado en DOM.');
            resolve();
            return;
        }

        const startTime = Date.now();
        const intervalo = setInterval(() => {
            const isVisible = 
                modal.style.display !== 'none' && 
                modal.style.display !== '' &&
                modal.offsetHeight > 0;
            
            if (isVisible) {
                clearInterval(intervalo);
                console.log(`[_esperarModalVisible] Modal visible`);
                resolve();
                return;
            }

            if (Date.now() - startTime > timeoutMs) {
                clearInterval(intervalo);
                console.warn(`[_esperarModalVisible] Timeout`);
                resolve();
            }
        }, 50);
    });
}
```

### Paso 4.5: Verificar sintaxis

En VSCode:
1. Posicionar cursor en la función
2. Verificar que NO haya subrayado en rojo (no debe haber errores)
3. Usar `Ctrl+Shift+B` para auto-format (si está configurado)

---

## PASO 5: Comentar listener del Blade (Fase 3 - opcional por ahora)

**IMPORTANTE:** Este paso es OPCIONAL en primeras pruebas. Hazlo SOLO cuando Fase 1+2 funcionen perfecto.

### Paso 5.1: Abrir Blade

```
resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php
```

### Paso 5.2: Encontrar líne a ~683 (búsqueda: "DragDropManager")

Si existe:
```javascript
if (window.DragDropManager) {
    try {
        window.DragDropManager.inicializar();
```

**Comentar toda esa sección:**
```javascript
/*
if (window.DragDropManager) {
    try {
        window.DragDropManager.inicializar();
        // ... resto
    }
}
*/
```

### Paso 5.3: Encontrar MutationObserver (línea ~830)

Si existe:
```javascript
const observer = new MutationObserver(...)
observer.observe(modal, ...)
```

**Comentar:**
```javascript
/*
const observer = new MutationObserver(...)
observer.observe(modal, ...)
*/
```

---

## PASO 6: Testing en desarrollo (30 minutos)

### Paso 6.1: Arrancar servidor

```bash
cd c:\Users\Usuario\Documents\mundoindustrial
php artisan serve
```

O si es en producción, asegurar que está corriendo.

### Paso 6.2: Abrir navegador

```
http://localhost:8000/asesores/crear-pedido
```

### Paso 6.3: PRUEBA 1 -Abrimiento básico

1. Hacer clic en "Agregar nueva prenda"
2. **Observar logs esperados:**

```
[ModalFSM]  Transición: CLOSED → OPENING
[abrirModalAgregarPrendaNueva] FASE 1: Iniciando apertura
[abrirModalAgregarPrendaNueva] FASE 2: Cargando catálogos...
[Telas] Iniciando carga de telas disponibles...
[Telas] Respuesta de API... ← SOLO UNA VEZ
[Telas] Telas cargadas: 48  ← Solo una vez
[abrirModalAgregarPrendaNueva]  Catálogos cargados correctamente
[abrirModalAgregarPrendaNueva] FASE 4: Esperando visible...
[_esperarModalVisible] Modal visible
[abrirModalAgregarPrendaNueva] FASE 5: Inicializando DragDropManager...
[DragDropManager]  Ya inicializado, ignorando... ← O solo "Sistema inicializado"
[abrirModalAgregarPrendaNueva]  DragDropManager inicializado
[ModalFSM]  Transición: OPENING → OPEN
[abrirModalAgregarPrendaNueva]  ÉXITO
```

** ÉXITO SI:**
- `[Respuesta de API...]` aparece 1 sola vez (no 2)
- `[ModalFSM]` muestra transiciones ordenadas
- No hay errores en rojo

### Paso 6.4: PRUEBA 2 - Doble clic rápido

1. Hacer clic en "Agregar prenda"
2. **INMEDIATAMENTE** (en menos de 1 segundo) hacer clic de nuevo
3. **Observar:**

```
[ModalFSM]  CLOSED → OPENING
[abrirModalAgregarPrendaNueva] FASE 1...
[ModalFSM] Modal no puede abrir ahora (estado: OPENING)
[abrirModalAgregarPrendaNueva]  Modal ya está en estado: OPENING. Ignorando...
```

** ÉXITO SI:**
- La segunda llamada es ignorada silenciosamente
- No hay doble fetch
- Modal no se abre dos veces

### Paso 6.5: PRUEBA 3 - Drag & Drop funciona

1. Abrir modal
2. En la zona de "Fotos de Prenda" (morado), hacer drag/drop de una imagen
3. **Esperado:** Imagen se carga sin errores

### Paso 6.6: PRUEBA 4 - Edición de prenda

1. Si hay prendas guardadas, hacer clic en "Editar"
2. **Esperado:** Modal abre con datos precargados, drag/drop funciona

---

## PASO 7: Desplegar a producción (con precaución)

### Paso 7.1: Backup

```bash
git status
git add -A
git commit -m "WIP: Implementación FSM Phase 1"
git tag backup-antes-fsm
```

### Paso 7.2: Deploy

```bash
git push origin main
# En servidor producción:
git pull origin main
# Si hay cambios en assets:
php artisan cache:clear
npm run build  # Si es necesario
```

### Paso 7.3: Monitoreo

1. **Primeros 30 minutos:**
   - Refresh de navegador
   - Abrir/cerrar modal 5 veces
   - Observar consola para errores

2. **Primera hora:**
   - Prueba en navegadores diferentes (Chrome, Firefox, Edge)
   - Prueba en mobile

3. **Primeras 24 horas:**
   - Monitorar logs de servidor
   - Monitorar error rate
   - Si usuarios reportan problemas, ejecutar rollback inmediato

### Paso 7.4: Rollback si es necesario

```bash
git revert HEAD
git push origin main
# En servidor:
git pull
php artisan cache:clear
```

---

## CHECKLIST FINAL

- [ ] Archivo `/public/js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js` creado
- [ ] Script en Blade carga correctamente (verificar en `<head>`)
- [ ] `window.__MODAL_FSM__` disponible en consola
- [ ] Método `abrirModalAgregarPrendaNueva()` reemplazado
- [ ] Método `_esperarModalVisible()` agregado
- [ ] **PRUEBA 1:** Abrimiento básico - telas cargan 1 sola vez 
- [ ] **PRUEBA 2:** Doble clic activa guard clause 
- [ ] **PRUEBA 3:** Drag & Drop funciona 
- [ ] **PRUEBA 4:** Edición funciona 
- [ ] **PRODUCCIÓN:** Monitoreo primeras 24 horas 

---

## 🚨 PROBLEMAS COMUNES & SOLUCIONES

| Problema | Causa | Solución |
|----------|-------|----------|
| "FSM no cargado" en consola | Script no se incluyó en Blade | Verificar ruta en asset() |
| Modal no abre | prendaEditor no disponible | Verificar que PrendaEditor.js esté cargado |
| Catálogos cargan x2 | FSM no está guardando estado | Verificar que window.__MODAL_FSM__ existe |
| "Modal no encontrado" warning | Blade no tiene #modal-agregar-prenda-nueva | Verificar HTML del modal |
| DragDrop no funciona | _esperarModalVisible timed out | Aumentar timeoutMs a 2000ms |

---

**Status:** Implementación Lista para Deploy  
**Última actualización:** 2026-02-13  
**Autor:** Senior Architect  
