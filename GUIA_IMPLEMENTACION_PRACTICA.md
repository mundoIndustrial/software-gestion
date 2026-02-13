# 🚀 GUÍA PRÁCTICA DE IMPLEMENTACIÓN

## 📋 PRE-REQUISITOS

- ✅ Servicios compartidos creados en `/public/js/servicios/shared/`
- ✅ Documentación completa disponible
- ✅ Acceso a modificar `crear-nuevo.html` y `pedidos-editable.html`

---

## FASE 1: VALIDACIÓN PREVIA (2 horas)

### Paso 1.1: Verificar aislamiento

En la consola del navegador en una página que tenga cotizaciones:

```javascript
// Verificar estado ANTES
console.log('ANTES:');
console.log('cotizacionActual:', window.cotizacionActual);
console.log('cotizacionEditor:', window.cotizacionEditorService);

// Inicializar servicios compartidos
const container = window.prendasServiceContainer;
await container.initialize();

// Verificar estado DESPUÉS
console.log('DESPUÉS:');
console.log('cotizacionActual:', window.cotizacionActual);  // Debe ser igual
console.log('cotizacionEditor:', window.cotizacionEditorService); // Debe ser igual
```

✅ **Resultado esperado**: Nada cambió en contexto de cotización

### Paso 1.2: Verificar servicios cargados

```javascript
const container = window.prendasServiceContainer;
console.log(container.getEstadisticas());

// Output esperado:
{
  inicializado: true,
  servicios: ['eventBus', 'formatDetector', 'data', 'storage', 'validation', 'editor'],
  cacheStats: {...},
  editorState: {...}
}
```

✅ **Resultado esperado**: Todos los servicios disponibles

---

## FASE 2: INTEGRACIÓN EN CREAR-NUEVO (3-4 horas)

### Paso 2.1: Actualizar HTML

En `/resources/views/asesores/pedidos/crear-nuevo.blade.php` (o archivo equivalente):

```html
<!-- Agregar ANTES de </body> -->

<!-- 🆕 Servicios compartidos de edición de prendas -->
<script src="/js/servicios/shared/event-bus.js?v=1"></script>
<script src="/js/servicios/shared/format-detector.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-validation-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-data-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-storage-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-editor-service.js?v=1"></script>
<script src="/js/servicios/shared/prenda-service-container.js?v=1"></script>

<!-- Scripts existentes de crear-nuevo -->
<script src="/js/modulos/crear-pedido/..."></script>
```

### Paso 2.2: Inicializar contenedor

En `crear-nuevo.js`, agregar al inicio del módulo:

```javascript
/**
 * Inicialización de servicios de edición de prendas
 */
async function inicializarServiciosPrendas() {
    try {
        console.log('[crear-nuevo] 🚀 Inicializando servicios de prendas...');
        
        const container = window.prendasServiceContainer;
        
        // Configurar debug (cambiar a true si hay problemas)
        container.setDebug(false);
        
        // Inicializar
        await container.initialize();
        
        console.log('[crear-nuevo] ✅ Servicios inicializados');
        
        // Guardar referencia global para fácil acceso
        window.editorPrendas = container.getService('editor');
        
        return container;
    } catch (error) {
        console.error('[crear-nuevo] ❌ Error inicializando servicios:', error);
        throw error;
    }
}

// Llamar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarServiciosPrendas);
} else {
    inicializarServiciosPrendas();
}
```

### Paso 2.3: Actualizar función de abrir editor

En `crear-nuevo.js`, encontrar la función que abre el editor (ej: `abrirEditarPrendaNueva()`):

```javascript
// ❌ ANTES (seguramente algo como):
async function abrirEditarPrendaNueva() {
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    modal.style.display = 'flex';
    // ... más código
}

// ✅ DESPUÉS:
async function abrirEditarPrendaNueva(prendaIndex = null) {
    try {
        console.log('[crear-nuevo] 📖 Abriendo editor de prendas...');
        
        // Obtener servicio
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Servicio de edición no inicializado');
        }
        
        // Preparar datos locales
        const prendaLocal = prendaIndex !== null
            ? window.datosCreacionPedido.prendas[prendaIndex]
            : undefined;
        
        // Abrir editor
        await editor.abrirEditor({
            modo: prendaIndex !== null ? 'editar' : 'crear',
            prendaLocal,
            prendaIndex,
            contexto: 'crear-nuevo',
            
            // Callback cuando usuario guarda
            onGuardar: async (prendaGuardada) => {
                console.log('[crear-nuevo] 💾 Prenda guardada:', prendaGuardada.nombre);
                
                // Actualizar datos locales
                if (prendaIndex !== null) {
                    window.datosCreacionPedido.prendas[prendaIndex] = prendaGuardada;
                } else {
                    window.datosCreacionPedido.prendas.push(prendaGuardada);
                }
                
                // Actualizar tabla/lista visual
                actualizarTablaPrendas();
                
                // Cerrar modal
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) modal.style.display = 'none';
            },
            
            // Callback si cancela
            onCancelar: () => {
                console.log('[crear-nuevo] ❌ Edición cancelada');
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) modal.style.display = 'none';
            }
        });
        
    } catch (error) {
        console.error('[crear-nuevo] ❌ Error abriendo editor:', error);
        alert('Error abriendo editor: ' + error.message);
    }
}
```

### Paso 2.4: Testing en crear-nuevo

En la consola, ejecutar:

```javascript
// 1️⃣ Abrir editor para crear
abrirEditarPrendaNueva();

// 2️⃣ Hacer cambios en el formulario (nombre, tallas, etc)

// 3️⃣ Guardar
// Debe ejecutarse el callback onGuardar
console.log('Prendas en datosCreacionPedido:', window.datosCreacionPedido.prendas);

// 4️⃣ Abrir editor para editar (si hay prendas)
abrirEditarPrendaNueva(0);  // Editar la primera prenda
```

✅ **Resultado esperado**: Editor abre, se pueden editar datos, se guardan correctamente

---

## FASE 3: INTEGRACIÓN EN EDITAR-PEDIDO (3-4 horas)

### Paso 3.1: Actualizar HTML

En `/resources/views/asesores/pedidos/pedidos-editable.blade.php`:

```html
<!-- Mismo que en crear-nuevo, agregar scripts compartidos -->
<script src="/js/servicios/shared/event-bus.js?v=1"></script>
<script src="/js/servicios/shared/format-detector.js?v=1"></script>
<!-- ... resto de scripts -->
```

### Paso 3.2: Inicializar (igual que en crear-nuevo)

```javascript
async function inicializarServiciosPrendas() {
    const container = window.prendasServiceContainer;
    await container.initialize();
    window.editorPrendas = container.getService('editor');
}

document.addEventListener('DOMContentLoaded', inicializarServiciosPrendas);
```

### Paso 3.3: Adaptar para EDITAR desde BD

En `pedidos-editable.js`, adaptar función de editar:

```javascript
async function editarPrendaPedidoExistente(prendaId, prendaIndex) {
    try {
        console.log('[pedidos-editable] Editando prenda:', prendaId);
        
        const editor = window.editorPrendas;
        if (!editor) throw new Error('Editor no inicializado');
        
        // Abrir editor en modo EDITAR (carga desde BD)
        await editor.abrirEditor({
            modo: 'editar',
            prendaId,  // Backend cargará los datos
            contexto: 'pedidos-editable',
            
            onGuardar: async (prendaGuardada) => {
                console.log('[pedidos-editable] Prenda actualizada:', prendaGuardada.nombre);
                
                // Actualizar en tabla local
                const index = window.datosEdicionPedido.prendas.findIndex(
                    p => p.id === prendaGuardada.id || p.prenda_pedido_id === prendaGuardada.id
                );
                
                if (index >= 0) {
                    window.datosEdicionPedido.prendas[index] = prendaGuardada;
                }
                
                // Actualizar visual
                actualizarTablaPrendas();
                
                // Cerrar modal
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) modal.style.display = 'none';
            }
        });
        
    } catch (error) {
        console.error('[pedidos-editable] Error:', error);
        alert('Error: ' + error.message);
    }
}
```

### Paso 3.4: Testing en editar-pedido

```javascript
// 1️⃣ Verificar que hay prendas
console.log('Prendas en BD:', window.datosEdicionPedido.prendas);

// 2️⃣ Editar la primera prenda
const prenda = window.datosEdicionPedido.prendas[0];
editarPrendaPedidoExistente(prenda.id, 0);

// 3️⃣ Hacer cambios y guardar

// 4️⃣ Verificar que se actualizó
console.log('Prenda actualizada:', window.datosEdicionPedido.prendas[0]);
```

✅ **Resultado esperado**: Se carga desde BD, se puede editar, se guarda correctamente

---

## FASE 3+: INTEGRACIÓN EN CREAR-DESDE-COTIZACIÓN (2-3 horas)

**Nuevo flujo:** Crear pedidos a partir de prendas existentes en cotizaciones
**URL:** `http://localhost:8000/asesores/pedidos-editable/crear-desde-cotizacion`
**Requisito:** No modificar la cotización original (completamente aislado)

### Paso 3+.1: Actualizar HTML

En `/resources/views/asesores/pedidos/crear-pedido-desde-cotizacion.blade.php`:

```html
<!-- Cargar servicios compartidos ANTES de otros scripts -->
<script src="/js/servicios/shared/event-bus.js?v=1"></script>
<script src="/js/servicios/shared/format-detector.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-validation-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-data-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-storage-service.js?v=1"></script>
<script src="/js/servicios/shared/shared-prenda-editor-service.js?v=1"></script>
<script src="/js/servicios/shared/prenda-service-container.js?v=1"></script>

<!-- Scripts existentes de crear-desde-cotizacion -->
<script src="/js/crear-pedido-editable.js?v={{ time() }}"></script>
<!-- ... resto de scripts -->
```

### Paso 3+.2: Inicializar servicios

En `crear-pedido-editable.js`, al inicio:

```javascript
async function inicializarServiciosPrendas() {
    try {
        console.log('[crear-desde-cotizacion] 🚀 Inicializando servicios de prendas...');
        
        const container = window.prendasServiceContainer;
        container.setDebug(false);  // Cambiar a true si hay problemas
        
        await container.initialize();
        
        window.editorPrendas = container.getService('editor');
        
        console.log('[crear-desde-cotizacion] ✅ Servicios inicializados');
        
        return container;
    } catch (error) {
        console.error('[crear-desde-cotizacion] ❌ Error:', error);
        throw error;
    }
}

// Llamar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarServiciosPrendas);
} else {
    inicializarServiciosPrendas();
}
```

### Paso 3+.3: Crear función para editar prendas de cotización

En `crear-pedido-editable.js`, agregar función:

```javascript
/**
 * Abrir editor para una prenda cargada desde cotización
 * IMPORTANTE: Hace una COPIA de los datos (NO modifica cotización original)
 */
async function editarPrendaDesdeCotizacion(
    cotizacionId, 
    prendaCotizacionId,
    datosPrenda  // Datos ya cargados del loader
) {
    try {
        console.log('[crear-desde-cotizacion] 📖 Abriendo editor de prenda desde cotización');
        console.log('  - Cotización:', cotizacionId);
        console.log('  - Prenda:', prendaCotizacionId);
        
        // Obtener editor
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Servicio de edición no inicializado');
        }
        
        // ⚠️ IMPORTANTE: Hacer COPIA profunda de datos
        // Esto previene que cambios afecten la cotización original
        const prendaCopia = JSON.parse(JSON.stringify(datosPrenda));
        
        // Abrir editor
        await editor.abrirEditor({
            modo: 'crear',  // Crear NUEVO item en pedido
            contexto: 'crear-desde-cotizacion',  // Contexto especial
            
            // Datos copiados (NO referencia)
            prendaLocal: prendaCopia,
            
            // Identificadores de origen (para auditoría)
            cotizacionId,
            prendaCotizacionId,
            origenCotizacion: {
                id: cotizacionId,
                numero: document.getElementById('cotizacion_search_editable')?.value || 'N/A',
                cliente: window.cotizacionActual?.cliente || 'N/A'
            },
            
            // Callback cuando guarda
            onGuardar: async (prendaModificada) => {
                console.log('[crear-desde-cotizacion] 💾 Prenda guardada');
                console.log('  - Nombre:', prendaModificada.nombre);
                console.log('  - Se añadirá como nuevo item en pedido');
                
                // Agregar al listado de prendas del pedido
                agregarPrendaAlPedido(prendaModificada);
                
                // Cerrar modal del editor
                cerrarModalEditor();
            },
            
            // Callback si cancela
            onCancelar: () => {
                console.log('[crear-desde-cotizacion] ❌ Edición cancelada');
                cerrarModalEditor();
            }
        });
        
    } catch (error) {
        console.error('[crear-desde-cotizacion] ❌ Error abriendo editor:', error);
        alert('Error abriendo editor: ' + error.message);
    }
}
```

### Paso 3+.4: Conectar al flujo de cargador

El cargador ya existe (`CargadorPrendasCotizacion`). Solo necesita conectarlo:

```javascript
// En el callback del cargador, al usuario hacer clic en "Editar"
// Ejemplo (ubicado en tu HTML actual):

button.addEventListener('click', async () => {
    try {
        // Cargar datos completos de la prenda
        const loader = new CargadorPrendasCotizacion();
        const datosPrenda = await loader.cargarPrendaCompletaDesdeCotizacion(
            cotizacionId,
            prendaId
        );
        
        // Abrir editor con los datos cargados
        await editarPrendaDesdeCotizacion(
            cotizacionId,
            prendaId,
            datosPrenda
        );
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    }
});
```

### Paso 3+.5: Testing en crear-desde-cotización

```javascript
// En la consola del navegador

// 1️⃣ Seleccionar una cotización
// (Click en dropdown de cotizaciones)

// 2️⃣ Esperar a que se carguen las prendas

// 3️⃣ Hacer clic en "Editar" para una prenda
// (Debería abrirse el editor)

// 4️⃣ Hacer cambios (nombre, tallas, etc)

// 5️⃣ Guardar
// (Debe agregarse a la tabla de ítems)

// 6️⃣ Verificar que cotización original NO fue modificada
// Recargar la cotización y verificar

console.log('Cotización original:', window.cotizacionActual);
// Debe estar intacta
```

✅ **Resultado esperado**: 
- Prendas se editan correctamente
- Se agregan al pedido como nuevos items
- Cotización original intacta
- Sin acceso a `/api/cotizaciones`

---

## FASE 4: TESTING COMPLETO (2-3 horas)

### Test 1: Crear pedido (flujo completo)

```
1. Ir a /asesores/pedidos-editable/crear-nuevo
2. Agregar una prenda nueva
3. Editar esa prenda
4. Agregar otra
5. Guardar pedido completo
6. Verificar en BD que se guardó correctamente
```

###Test 2: Editar pedido (flujo completo)

```
1. Ir a /asesores/pedidos-editable/123
2. Editar prenda existente
3. Cambiar nombre, tallas, telas
4. Guardar
5. Refrescar página y verificar que cambios persisten
```

### Test 3: Verificar aislamiento

```javascript
// En una página con cotizaciones
console.log('Antes:', window.cotizacionActual);

// Inicializar servicios
await window.prendasServiceContainer.initialize();

console.log('Después:', window.cotizacionActual);
// Debe ser igual
```

---

## POSIBLES PROBLEMAS Y SOLUCIONES

### Problema: "prendasServiceContainer is undefined"

**Solución:**
```javascript
// Verificar que los scripts se cargaron
console.log('EventBus:', typeof EventBus);
console.log('Container:', typeof PrendaServiceContainer);

// Si están undefined, verificar orden de scripts en HTML
```

### Problema: "FormatDetector not defined"

**Solución:**
```html
<!-- Asegurar que format-detector.js se carga ANTES que data-service -->
<script src="/js/servicios/shared/format-detector.js"></script>
<script src="/js/servicios/shared/shared-prenda-data-service.js"></script>
```

### Problema: "Editor not initialized"

**Solución:**
```javascript
// Asegurar que initialize() terminó antes de usar el servicio
const container = window.prendasServiceContainer;
await container.initialize();  // ESPERAR el await
const editor = container.getService('editor');
```

### Problema: Eventos no se disparan

**Solución:**
```javascript
// Habilitar debug
const container = window.prendasServiceContainer;
container.setDebug(true);

// Ver logs en consola
// Los eventos deberían mostrarse
```

---

## 🎯 CHECKLIST DE COMPLETITUD

### Crear-nuevo
- [ ] Scripts de servicios cargados en HTML
- [ ] `inicializarServiciosPrendas()` llamado
- [ ] `abrirEditarPrendaNueva()` usando nuevo editor
- [ ] Testing completo
- [ ] Callback onGuardar actualiza tabla
- [ ] Modal se cierra después de guardar

### Editar-pedido
- [ ] Scripts de servicios cargados en HTML
- [ ] `inicializarServiciosPrendas()` llamado
- [ ] Función de editar usa modo='editar'
- [ ] Carga desde BD correctamente
- [ ] Testing completo
- [ ] Cambios persisten en BD

### Aislamiento
- [ ] Cotizaciones no son afectadas
- [ ] `/api/cotizaciones` NO es llamado
- [ ] window.cotizacionActual sigue igual
- [ ] Event buses independientes

---

## 🎓 CONCLUSIÓN

Después de completar estos 4 pasos:

✅ Servicios compartidos funcionando
✅ Crear-nuevo integrado
✅ Editar-pedido integrado
✅ Cotizaciones protegidas
✅ Sistema listo para producción

---

## 📞 DEBUGGING

Si algo no funciona:

```javascript
// Habilitar modo debug completo
window.prendasServiceContainer.setDebug(true);

// Ver estadísticas
console.log(window.prendasServiceContainer.getEstadisticas());

// Ver eventos disparados
const eventBus = window.prendasServiceContainer.getService('eventBus');
eventBus.enableDebug(true);
```

¡Listo para implementar! 🚀
