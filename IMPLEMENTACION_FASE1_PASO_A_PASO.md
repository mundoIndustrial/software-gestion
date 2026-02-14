#  GUÍA DE IMPLEMENTACIÓN FASE 1 - PASO A PASO

##  PRE-REQUISITOS ANTES DE EMPEZAR

- [ ] Backup de la rama actual: `git commit -am "Backup pre-Fase1"`
- [ ] Crear rama feature: `git checkout -b feature/fase1-deduplicacion`
- [ ] Ambiente local de desarrollo funcionando
- [ ] Browser con DevTools lista (Chrome/Firefox)

---

##  PASO 1: CREAR ARCHIVO PROMISE-CACHE

### Acción
Crear nuevo archivo:
```
public/js/modulos/crear-pedido/prendas/promise-cache.js
```

**Verificación:**
```bash
ls -la public/js/modulos/crear-pedido/prendas/promise-cache.js
# Debe retornar: -rw-r--r-- ... promise-cache.js
```

---

##  PASO 2: INCLUIR PROMISE-CACHE EN HTML

### Archivos a modificar
- `resources/views/asesores/pedidos/pedido-crear.blade.php` (o donde cargues scripts)
- Cualquier otra vista que use el modal

### Cambio
Agregar ANTES de `manejadores-variaciones.js`:

```html
<!-- FASE 1: Promise Cache para deduplicación -->
<script src="{{ asset('public/js/modulos/crear-pedido/prendas/promise-cache.js') }}"></script>

<!-- DEBE cargarse DESPUÉS de Promise Cache -->
<script src="{{ asset('public/js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>
```

**Verificación en Browser:**
```javascript
// En console:
typeof PromiseCache
// Debe retornar: "object"

PromiseCache.getStatus()
// Debe retornar: { size: 0, keys: [], timestamp: "..." }
```

---

##  PASO 3: REEMPLAZAR FUNCIÓN cargarCatalogosModal()

### Archivo: `manejadores-variaciones.js`

Ya está hecho. Verificar que el cambio se aplicó:

```bash
grep -n "Promise en flight" public/js/modulos/crear-pedido/prendas/manejadores-variaciones.js
```

**Debe retornar línea con:** `Promise en flight, reutilizando...`

---

##  PASO 4: HACER ASYNC/AWAIT abrirModalAgregarPrendaNueva()

### Archivo: `gestion-items-pedido.js`

Ya está hecho. Verificar:

```bash
grep -n "async abrirModalAgregarPrendaNueva" public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js
```

**Debe retornar:** Línea con `async abrirModalAgregarPrendaNueva()`

---

##  PASO 5: REFORZAR GUARD CLAUSE DragDropManager

### Archivo: `drag-drop-manager.js`

Ya está hecho. Verificar:

```bash
grep -n "Ya inicializado, ignorando" public/js/componentes/prendas-module/drag-drop-manager.js
```

**Debe retornar:** Línea con el nuevo mensaje

---

##  PASO 6: AUDITAR TODOS LOS CALLERS

### Búsqueda 1: Dónde se llama `abrirModalAgregarPrendaNueva()`

```bash
grep -r "abrirModalAgregarPrendaNueva" public/js --include="*.js"
```

**Resultados esperados:** ~5-10 resultados

### Búsqueda 2: Qué archivos hacen `.abrirModalAgregarPrendaNueva()`

```bash
grep -r "\.abrirModalAgregarPrendaNueva\(" public/js --include="*.js"
```

### Acción
Para CADA resultado, verificar si es:

1. **Dentro de una clase:** Agregar `await`
```javascript
//  ANTES
this.gestionItemsUI.abrirModalAgregarPrendaNueva();

//  DESPUÉS
await this.gestionItemsUI.abrirModalAgregarPrendaNueva();
```

2. **Evento onclick:** Agregar async al handler
```html
<!--  ANTES -->
<button onclick="window.gestionItemsUI.abrirModalAgregarPrendaNueva()">

<!--  DESPUÉS -->
<button onclick="(async () => { await window.gestionItemsUI.abrirModalAgregarPrendaNueva(); })()">
```

3. **Llamada global:** Agregar await si en contexto async
```javascript
window.gestionItemsUI.abrirModalAgregarPrendaNueva(); // Funcionará igual (no await)
```

---

## 🧪 TESTING FASE 1

### Test 1: Console debe mostrar deduplicación

**Pasos:**
1. Abrir DevTools (F12)
2. Ir a pestaña Console
3. Hacer clic en botón "Agregar nueva prenda"
4. Esperar a que se abra el modal
5. Hacer clic en el botón RÁPIDAMENTE otra vez

**Resultado esperado:**
```
[PromiseCache] Promise guardada { key: 'catalogs:telas-colores', size: 1 }
[Catálogos] Iniciando carga de catálogos...
[Telas] Iniciando carga de telas disponibles...
[Telas] Respuesta de API...
[Colores] Iniciando carga de colores disponibles...
[Colores] Respuesta de API...
[Catálogos]  Ambos catálogos cargados
[PromiseCache] Promise limpiada automáticamente
```

Si haces 2 clics rápidos, deberías ver:
```
[PromiseCache] Promise guardada
[Catálogos] Iniciando carga...
[PromiseCache] Promise en flight, reutilizando... ← AQUÍ, segunda llamada
[Catálogos]  Ambos catálogos cargados
```

### Test 2: Network debe mostrar 1 fetch, no 2

**Pasos:**
1. Abrir DevTools → Network
2. Hacer clic "Agregar nueva prenda"
3. Observar requests

**Resultado esperado:**
- `/api/public/telas` aparece 1 vez
- `/api/public/colores` aparece 1 vez
- **NO DUPLICADOS**

### Test 3: DragDropManager initialization guard

**Pasos:**
1. Console clear
2. Abrir modal
3. Buscar logs de DragDropManager

**Resultado esperado:**
```
[DragDropManager] Iniciando inicialización...
[DragDropManager]  Sistema de drag & drop inicializado correctamente
```

Si abres el modal 2 veces:
```
[DragDropManager]  Ya inicializado, ignorando llamada duplicada ← ESTO en segunda apertura
```

### Test 4: Modal abre correctamente

**Pasos:**
1. Abrir modal
2. Verificar que los inputs están vacíos (creación) o llenos (edición)
3. Verificar que las datalist de telas y colores tienen opciones
4. Verificar que no hay errores en console

**Resultado esperado:**
-  Modal se abre
-  Formulario vacío (creación) o con datos (edición)
-  Autocomplete de telas y colores funciona
-  Sin errores en console

---

## 🚨 ROLLBACK IMMEDIATO SI...

Si ves CUALQUIERA de esto, hacer rollback:

```
 Modal no abre
 Catálogos cargan múltiples veces (2+ en Network)
 Errors en console tipo "is not a function"
 Modal abre sin catálogos (dropdown vacío)
 Drag & drop no funciona
```

**Comando rollback:**
```bash
git reset --hard HEAD~1
# Luego: cierra y abre el navegador
```

---

## 📝 CREAR ISSUE PARA FASE 2

Una vez que Fase 1 esté estable en producción (24h de monitoreo), crear issue:

```
Fase 2: Control de Listeners
- Elemento: ModalListenerRegistry
- Objetivo: Limpiar listeners en cada cierre
- Archivos: modal-cleanup.js, modal-listener-registry.js
- Riesgo: BAJO
- Estimado: 2-3 días
```

---

## 📊 MÉTRICAS A MONITOREAR

### Antes de Fase 1
```
API Calls: 2x por apertura de modal (bug)
Memory: Crece con cada apertura (leak leve)
Performance: 800ms para abrir modal
```

### Después de Fase 1 (esperado)
```
API Calls: 1x por primera apertura, 0x en reapertura (gracias a dedup)
Memory: Estable
Performance: 400-500ms para primera apertura, 100ms en reapertura
```

---

##  CHECKLIST FINAL

- [ ] promise-cache.js creado y cargado
- [ ] manejadores-variaciones.js refactorizado
- [ ] gestion-items-pedido.js asincronizado
- [ ] drag-drop-manager.js guard clause reforzado
- [ ] TODOS los callers auditados y actualizados
- [ ] Test 1 (Console dedup) 
- [ ] Test 2 (Network 1x) 
- [ ] Test 3 (DragDropManager guard) 
- [ ] Test 4 (Modal funcional) 
- [ ] 0 errores en console
- [ ] Modal abre/cierra 10 veces sin problemas
- [ ] Código pusheado a rama feature/fase1-deduplicacion
- [ ] Pull request creado para review

---

**Duración estimada:** 2-4 horas  
**Riesgo:** 🟢 BAJO  
**Reversibilidad:** 5 minutos con rollback
