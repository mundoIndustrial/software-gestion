# 🔐 AISLAMIENTO DE COTIZACIONES - ARQUITECTURA MODULAR

##  RESTRICCIÓN CRÍTICA

**La lógica de edición compartida SOLO funciona para:**
-  Crear Pedidos (`/asesores/pedidos-editable/crear-nuevo`)
-  Editar Pedidos (`/asesores/pedidos-editable/{pedido_id}`)

**NO interfiere con:**
-  Cotizaciones
-  Módulo de Cotizaciones
-  Lógica de Cotización Prenda Handler
-  CotizacionEditorService
-  Procesos de cotización

---

## 🏗️ DIAGRAMA DE AISLAMIENTO

```
┌─────────────────────────────────────────────────────────────────┐
│                    APLICACIÓN COMPLETA                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ 🔒 ZONA COTIZACIONES (INDEPENDIENTE)                         │ │
│  │                                                               │ │
│  │ ├─ CotizacionEditorService                                  │ │
│  │ ├─ CotizacionPrendaHandler                                  │ │
│  │ ├─ CotizacionDataService                                    │ │
│  │ ├─ Modal de Cotización                                      │ │
│  │ └─ Lógica de cotización (SIN CAMBIOS)                       │ │
│  │                                                               │ │
│  │ 🚫 NO TOCA SharedPrendaEditorService                         │ │
│  │ 🚫 NO TOCA el servicio compartido                            │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ 🆕 ZONA PEDIDOS (USA NUEVO SERVICIO COMPARTIDO)             │ │
│  │                                                               │ │
│  │ ├─ Crear Pedido (`crear-nuevo`)                             │ │
│  │ │  └─ Usa: SharedPrendaEditorService                        │ │
│  │ │                                                             │ │
│  │ ├─ Editar Pedido (`pedidos-editable/{id}`)                  │ │
│  │ │  └─ Usa: SharedPrendaEditorService                        │ │
│  │ │                                                             │ │
│  │ └─ Lógica compartida (NUEVO)                                │ │
│  │    ├─ SharedPrendaDataService                               │ │
│  │    ├─ SharedPrendaEditorService                             │ │
│  │    ├─ SharedPrendaStorageService                            │ │
│  │    └─ PrendaServiceContainer                                │ │
│  │                                                               │ │
│  │  COMPLETAMENTE AISLADO de cotizaciones                     │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔌 VERIFICACIÓN DE AISLAMIENTO

###  Cotizaciones NUNCA usan servicios compartidos

```javascript
// 🚫 PROHIBIDO en cotizaciones
window.prendasServiceContainer.getService('editor') // 

//  Cotizaciones usan su propio sistema
window.cotizacionEditorService
window.cotizacionPrendaHandler
```

###  Servicios compartidos NUNCA tocan endpoint de cotización

```javascript
// SharedPrendaEditorService NO hace:
class SharedPrendaEditorService {
    //  NO hay lógica de "tipo_cotizacion_id"
    //  NO hay lógica de "origen automático desde cotización"
    //  NO hay métodos de cotización-específicos
    
    //  SOLO tiene lógica genérica de edición de prendas
}
```

###  Endpoints diferentes

```javascript
// Cotizaciones
GET  /api/cotizaciones/{id}/prendas
POST /api/cotizaciones/{id}/prendas
PATCH /api/cotizaciones/{id}/prendas/{prendaId}

// Pedidos (Servicios compartidos)
GET  /api/prendas/{id}
POST /api/prendas
PATCH /api/prendas/{id}
```

---

##  CHECKLIST DE AISLAMIENTO

### En `SharedPrendaEditorService`
- [ ]  NO tiene referencia a `cotizacionActual`
- [ ]  NO tiene lógica de `tipo_cotizacion_id`
- [ ]  NO tiene `aplicarOrigenAutomaticoDesdeCotizacion`
- [ ]  NO toca `window.cotizacionActual`
- [ ]  NO llama métodos de `CotizacionEditorService`

### En `SharedPrendaDataService`
- [ ]  NO tiene endpoints de `/api/cotizaciones`
- [ ]  NO maneja transformación específica de cotización
- [ ]  Endpoints apuntan SOLO a `/api/prendas`

### En aplicación
- [ ]  Cotizaciones siguen usando su propio `CotizacionEditorService`
- [ ]  Crear-nuevo usa `SharedPrendaEditorService`
- [ ]  Editar pedido usa `SharedPrendaEditorService`
- [ ]  Nunca hay import cruzado entre servicios

---

## 🎯 ESTRUCTURA DE CARPETAS (CON AISLAMIENTO)

```
/public/js/
├── servicios/
│   ├── shared/                      ← NUEVO: Servicios reutilizables (SOLO para pedidos)
│   │   ├── event-bus.js
│   │   ├── format-detector.js
│   │   ├── shared-prenda-data-service.js
│   │   ├── shared-prenda-editor-service.js
│   │   ├── shared-prenda-storage-service.js
│   │   ├── shared-prenda-validation-service.js
│   │   ├── shared-prenda-ui-service.js
│   │   └── prenda-service-container.js
│   │
│   └── cotizaciones/                ← AISLADO: Solo cotizaciones
│       ├── cotizacion-editor-service.js
│       ├── cotizacion-prenda-handler.js
│       ├── cotizacion-data-service.js
│       └── ... (otros servicios de cotización)
│
├── modulos/
│   ├── crear-pedido/               ← USA servicios compartidos
│   │   ├── crear-nuevo.js
│   │   └── ... (otros archivos)
│   │
│   └── editar-pedido/              ← USA servicios compartidos
│       ├── pedidos-editable.js
│       └── ... (otros archivos)
```

---

## 💡 CONFIGURACIÓN DE SERVICIOS

### Crear-Nuevo (Usa Servicios Compartidos)

```javascript
// crear-nuevo.js
async function inicializar() {
    console.log('[crear-nuevo] Inicializando...');

    //  Usar servicios compartidos
    const container = window.prendasServiceContainer;
    await container.initialize();

    const editor = container.getService('editor');
    window.editorPrendas = editor;

    //  NUNCA usar cotización
    // No hay acceso a: window.cotizacionEditorService
}
```

### Cotizaciones (Aisladas Completamente)

```javascript
// cotizacion-editor-service.js
class CotizacionEditorService {
    constructor() {
        //  Su propio sistema, completamente aislado
        this.cotizacionActual = null;
        this.prendaHandler = new CotizacionPrendaHandler();
        
        //  NO referencia a servicios compartidos
        // No hace: window.prendasServiceContainer
    }

    abrirEditorPrenda(prenda) {
        // Lógica específica de cotización
        // Ejemplo: aplicarOrigenAutomaticoDesdeCotizacion()
        // Esto es SOLO para cotizaciones
    }
}

// Exportar
window.cotizacionEditorService = new CotizacionEditorService();
```

---

## 🚫 COLISIONES A EVITAR

###  NO HACER EN SERVICIOS COMPARTIDOS

```javascript
//  NUNCA esto:
class SharedPrendaEditorService {
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        //  PROHIBIDO - esto es solo para cotizaciones
    }

    cargarTelasDesdeCtizacion(prenda) {
        //  PROHIBIDO - esto es solo para cotizaciones
    }

    detectarTipoCotizacion() {
        //  PROHIBIDO - esto es solo para cotizaciones
    }
}
```

###  HACER EN SERVICIOS DE COTIZACIÓN

```javascript
//  AQUÍ SÍ:
class CotizacionPrendaHandler {
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        //  CORRECTO - solo aquí
        const esReflectivo = this.cotizacionActual?.tipo_cotizacion_id === 4;
        if (esReflectivo) {
            prenda.origen = 'bodega'; // Origen automático por tipo
        }
    }

    cargarTelasDesdeCtizacion() {
        //  CORRECTO - solo aquí
    }
}
```

---

## 📊 MATRIZ DE SERVICIOS

| Servicio | Ubicación | Usa Compartidos | Independiente | Modifica |
|----------|-----------|-----------------|---------------|----------|
| `CotizacionEditorService` | `/servicios/cotizaciones/` |  |  | Cotizaciones |
| `SharedPrendaEditorService` | `/servicios/shared/` | - |  | Pedidos |
| `crear-nuevo.js` | `/modulos/crear-pedido/` |  | - | Pedidos |
| `pedidos-editable.js` | `/modulos/editar-pedido/` |  | - | Pedidos |
| `CotizacionPrendaHandler` | `/servicios/cotizaciones/` |  |  | Cotizaciones |

---

## 🔗 IMPORTACIONES EXPLÍCITAS (Sin Contaminación Cruzada)

### En `crear-nuevo.html` (CORRECTO)

```html
<!--  Solo servicios compartidos para pedidos -->
<script src="/js/servicios/shared/event-bus.js"></script>
<script src="/js/servicios/shared/format-detector.js"></script>
<script src="/js/servicios/shared/shared-prenda-data-service.js"></script>
<script src="/js/servicios/shared/shared-prenda-editor-service.js"></script>
<script src="/js/servicios/shared/prenda-service-container.js"></script>

<!--  NUNCA incluir esto aquí -->
<!-- NO <script src="/js/servicios/cotizaciones/cotizacion-editor-service.js"></script> -->
```

### En `cotizaciones.html` (CORRECTO)

```html
<!--  Solo servicios de cotización -->
<script src="/js/servicios/cotizaciones/cotizacion-editor-service.js"></script>
<script src="/js/servicios/cotizaciones/cotizacion-prenda-handler.js"></script>

<!--  NUNCA incluir esto aquí -->
<!-- NO <script src="/js/servicios/shared/prenda-service-container.js"></script> -->
```

---

## 🛡️ GUARDRAILS DE AISLAMIENTO

### Guard 1: No compartir instancias

```javascript
//  NUNCA hacer esto:
class SharedPrendaEditorService {
    constructor() {
        //  MALO - acoplamiento
        this.cotizacionService = window.cotizacionEditorService;
    }
}

//  HACER esto:
class SharedPrendaEditorService {
    constructor(dependencies) {
        // Solo inyectar lo que necesita (nada de cotización)
        this.dataService = dependencies.dataService;
        this.eventBus = dependencies.eventBus;
    }
}
```

### Guard 2: Namespaces separados

```javascript
// Cotizaciones
window.CotizacionServices = {
    editor: CotizacionEditorService,
    handler: CotizacionPrendaHandler,
    data: CotizacionDataService
};

// Pedidos
window.PrendaServices = {
    container: PrendaServiceContainer
    // No hay overlap!
};
```

### Guard 3: Event buses separados

```javascript
//  NUNCA compartir eventBus entre servicios no relacionados
const eventBusCotizaciones = new EventBus(); // Solo cotizaciones
const eventBusPrendas = new EventBus();      // Solo pedidos

// Cada uno con sus eventos
eventBusCotizaciones.on('cotizacion:prenda-agregada', ...);
eventBusPrendas.on('prenda:guardada', ...);
```

---

## 🔍 VALIDACIÓN DE AISLAMIENTO

### Test 1: Cotización no afectada

```javascript
// Cargar cotización
const cotizacion = await obtenerCotizacion(1);

// Inicializar servicios compartidos
const container = window.prendasServiceContainer;
await container.initialize();

// Editar prenda en PEDIDO
await container.getService('editor').abrirEditor({
    modo: 'crear',
    contexto: 'crear-nuevo'
});

// Verificar: cotización NO debe cambiar
console.assert(cotizacion.id === 1, 'Cotización modificada ');
console.assert(window.cotizacionActual === undefined, 'Contexto contaminado ');
```

### Test 2: Servicios no se interfieren

```javascript
//  Verificar que no comparten estado
const editorPedido = window.prendasServiceContainer.getService('editor');
const editorCotizacion = window.cotizacionEditorService;

console.assert(
    editorPedido.constructor.name !== editorCotizacion.constructor.name,
    'Misma clase = problema '
);
```

---

## 📝 NOTAS IMPORTANTES

1. **Cotizaciones NUNCA cambiarán**: El módulo de cotizaciones sigue funcionando exactamente igual
2. **Aislamiento completo**: No hay importaciones, referencias ni dependencias cruzadas
3. **Ignorar cotización en shared**: Todos los servicios compartidos IGNORAN completamente las cotizaciones
4. **Endpoints distintos**: Los servicios compartidos usan `/api/prendas`, no `/api/cotizaciones`

---

## 🎯 RESUMEN

**Servicios compartidos = SOLO para pedidos (crear-nuevo + editar)**
**Cotizaciones = Sistema INDEPENDIENTE sin cambios**

Sin contaminación cruzada. Sin acoplamiento. Sin sorpresas. 
