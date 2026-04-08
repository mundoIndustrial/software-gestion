##  Sistema de Auto-Carga de Módulos - PrendaEditor

### El Problema
El archivo `prenda-editor.js` depende de varios módulos especializados:
- `PrendaModalManager` → Gestiona el modal
- `PrendaEditorService` → Obtiene datos del servidor
- `PrendaEditor[Basicos|Imagenes|...]` → Cargan datos específicos

Si estos módulos no se cargan ANTES de crear una instancia de `PrendaEditor`, aparece el error:
```
ReferenceError: PrendaModalManager is not defined
```

### La Solución: 3 Niveles de Fallback

####  NIVEL 1: Loader Modular (IDEAL)
```html
<!-- Si incluyes el loader modular, todo carga en orden correcto -->
<script src="/js/lazy-loaders/prenda-editor-loader-modular.js"></script>

<!-- El loader carga automáticamente todos los módulos -->
<!-- Cuando estés listo, úsalo: -->
<script>
  const editor = new PrendaEditor({ modalId: 'mi-modal' });
</script>
```

####  NIVEL 2: Auto-Carga (AUTOMÁTICO)
Si el loader NO está incluido, `PrendaEditor` lo detecta automáticamente y:

```javascript
// 1. Detecta que PrendaModalManager no está definido
// 2. Busca window.PrendaEditorLoader (si fue cargado)
// 3. Si lo encuentra, lo usa
// 4. Como fallback, carga los módulos manualmente via <script> tags
```

**Flujo automático:**
```
new PrendaEditor() → Constructor
  ↓
_garantizarModulosDisponiblesGlobal()
  ├─ ¿PrendaModalManager disponible? → Nada que hacer
  ├─ ¿window.PrendaEditorLoader disponible? → Úsalo
  └─ Como fallback → Inyecta <script> tags en el head
```

#### 🆘 NIVEL 3: Fallback en Tiempo Real
Si `abrirModal()` se llama ANTES de que los módulos carguen:

```javascript
abrirModal(esEdicion) → 
  ├─ ¿PrendaModalManager disponible?
  │  └─ Sí → Úsalo directamente
  └─ No → Fallback inmediato + monitoreo en background
     ├─ Abre modal directamente (sin PrendaModalManager)
     └─ Monitorea cada 100ms hasta que PrendaModalManager esté disponible
        └─ Cuando está listo, actualiza el modal con la funcionalidad completa
```

### Orden de Ejecución

```
ESCENARIO A: Loader modular cargado
===========================================
HTML carga: prenda-editor-loader-modular.js
           ↓
         loader.load() carga módulos en paralelo
           ↓
         Todos los módulos disponibles en window
           ↓
       gestion-items-pedido.js → new PrendaEditor()
           ↓
         PrendaEditor.constructor → _garantizarModulos...
           ↓
         Detecta que todo ya está cargado → No hace nada


ESCENARIO B: Solo prenda-editor.js cargado
===========================================
gestion-items-pedido.js → new PrendaEditor()
           ↓
    PrendaEditor.constructor → _garantizarModulos...
           ↓
    Detecta que PrendaModalManager no existe
           ↓
    Busca window.PrendaEditorLoader
           ├─ NO EXISTE (loader no fue incluido)
           └─ Carga módulos manualmente via <script async>
                    ↓
    Los módulos se cargan en paralelo en el head
           ↓
    abrirModal() es llamado
           ├─ PrendaModalManager aún no disponible
           └─ Usa fallback + monitorea en background
                    ↓
                Cuando los módulos cargan (~500ms)
                    ↓
                Se actualiza automáticamente
```

### Propiedades Estáticas

```javascript
PrendaEditor._modulosEnCarga = false;   // Indica si se están cargando
PrendaEditor._modulosCargados = false;  // Indica si ya están listos
```

Estas propiedades evitan que se intente cargar múltiples veces.

### Secuencia Temporal

```
t=0ms:        constructor → _garantizarModulos...() → Inicia carga async
t=1ms:        abrirModal() sin problemas (usa fallback)
t=100-500ms:  Los módulos se cargan en el head
t=500ms+:     PrendaModalManager detectado → Se actualiza automáticamente
t=520ms:      cargarPrendaEnModal() → Todos los módulos disponibles 
```

### Debugging

Si quieres verificar el estado, abre la consola:

```javascript
// Ver si los módulos están cargados
typeof PrendaModalManager !== 'undefined'

// Ver si el loader está disponible
typeof window.PrendaEditorLoader !== 'undefined'

// Ver el estado
PrendaEditor._modulosCargados
PrendaEditor._modulosEnCarga

// Ver logs en la consola
// Busca líneas como:
// "[PrendaEditor]  Módulos ya disponibles"
// "[PrendaEditor]  Usando PrendaEditorLoader"
// "[PrendaEditor]  Cargando N módulos..."
```

### Recomendaciones de Integración

####  OPCIÓN 1: Incluir el Loader (RECOMENDADO)
```html
<!-- En tu base.html o layout -->
<script src="/js/lazy-loaders/prenda-editor-loader-modular.js"></script>

<!-- Listo, todo funciona -->
```

**Ventajas:**
- Carga rápida y eficiente
- Orden garantizado
- Mejor debugging
- Sin sorpresas

####  OPCIÓN 2: Confiar en el Fallback
Si no incluyes el loader, simplemente:

```html
<!-- Incluye prenda-editor.js como antes -->
<script src="/js/modulos/crear-pedido/prendas/prenda-editor.js"></script>

<!-- Relaja, el fallback automático se encarga del resto -->
```

**Ventajas:**
- Sin cambios en el HTML
- Funciona sin el loader
- Automático y sin configuración

**Desventajas:**
- Ligeramente más lenta (especialmente en conexión lenta)
- Dependencia de que los archivos existan en las rutas correctas

---

**Estado Actual:**  FUNCIONANDO CON FALLBACKS  
**Recomendación:** Incluir `prenda-editor-loader-modular.js` para mejor rendimiento
