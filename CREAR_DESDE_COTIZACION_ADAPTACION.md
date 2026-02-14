# 🔗 ADAPTACIÓN PARA CREAR PEDIDO DESDE COTIZACIÓN

**Requisito:** El sistema debe soportar crear pedidos a partir de prendas que vienen de una cotización existente.

**URL:** `http://localhost:8000/asesores/pedidos-editable/crear-desde-cotizacion`

**Contexto:** Distinto a crear-nuevo y pedidos-editable

---

##  FLUJO ACTUAL

```
1. Usuario selecciona una COTIZACIÓN existente
2. Sistema carga las prendas de esa cotización
3. Usuario ELIGE qué prendas agregar al nuevo PEDIDO
4. Usuario EDITA esas prendas ANTES de agregar
5. Usuario GUARDA el PEDIDO (distinto de cotización)

┌─────────────┐
│  COTIZACIÓN │ (BD original - NO debe modificarse)
└─────────────┘
       │
       │ (solo LECTURA)
       ▼
    ┌──────────────────────────────────┐
    │ CargadorPrendasCotizacion        │
    │ cargarPrendaCompletaDesdeCotizacion()
    └──────────────────────────────────┘
       │
       │ (copia para editar)
       ▼
    ┌──────────────────────────────────┐
    │ EDITOR COMPARTIDO (nuevo)        │
    │ editor.abrirEditor({             │
    │   modo: 'crear',                 │
    │   contexto: 'crear-desde-cotizacion'
    │   prendaLocal: {copia},          │
    │   origenCotizacion: {id, datos}  │
    │ })                               │
    └──────────────────────────────────┘
       │
       │ (edita COPIA, no original)
       ▼
    ┌──────────────────────────────────┐
    │ NUEVO PEDIDO                     │
    │ (datos modificados de cotización)│
    └──────────────────────────────────┘
```

---

## 🎯 REQUISITOS DE AISLAMIENTO

###  PROHIBIDO
- Modificar datos de cotización en BD
- Actualizar endpoints de cotización (`/api/cotizaciones/*`)
- Usar datos de cotización para validaciones
- Referenciar servicios de cotización

###  PERMITIDO
- **LEER** datos de cotización (una sola vez, al cargar)
- Hacer una COPIA de los datos
- Editar la **COPIA** libremente
- Guardar como nuevo PEDIDO

---

##  ADAPTACIÓN DEL SERVICIO DE EDICIÓN

### Parámetros nuevos para `abrirEditor()`

```javascript
// NUEVO: Soporte para crear-desde-cotizacion
await editor.abrirEditor({
    modo: 'crear',
    contexto: 'crear-desde-cotizacion',  // ← NUEVO CONTEXTO
    
    // Identificadores de origen
    cotizacionId: 123,                     // ← NUEVO
    prendaCotizacionId: 456,               // ← NUEVO (ID en cotización)
    
    // Datos locales copiados (NO referencia directa)
    prendaLocal: {
        nombre: 'Camisa',
        ... // copia de datos
    },
    
    // Metadatos de origen (para auditoría)
    origenCotizacion: {
        id: 123,
        numero: 'COT-2026-001',
        cliente: 'Empresa X'
    },
    
    // Callbacks
    onGuardar: (prendaModificada) => {
        // Se guardará como NUEVO ITEM en pedido
        // NO modifica la cotización
        agregarPrendaAlPedido(prendaModificada);
    },
    
    onCancelar: () => {
        // Descartar cambios
    }
});
```

### Flujo en servicios

```
SharedPrendaEditorService.abrirEditor()
    │
    ├─ Detectar contexto: 'crear-desde-cotizacion'
    │
    ├─ IMPORTANTE: No hacer fetch de `/api/cotizaciones/*`
    │
    ├─ Usar prendaLocal (ya copiada por caller)
    │
    ├─ Determinar endpoint de guardado:
    │   - contexto === 'crear-desde-cotizacion'
    │   → POST /api/prendas (nuevo pedido)
    │   → NO POST /api/cotizaciones/{id}/prendas
    │
    └─ Callback onGuardar con datos limpios
       (sin referencias a cotización)
```

---

## 📝 CAMBIOS EN SharedPrendaEditorService

### Nuevo parámetro de config

```javascript
// app/publicjs/servicios/shared/shared-prenda-editor-service.js

class SharedPrendaEditorService {
    async abrirEditor(config) {
        // Validar contextos permitidos
        const CONTEXTOS_VALIDOS = [
            'crear-nuevo',
            'pedidos-editable',
            'crear-desde-cotizacion'  // ← NUEVO
        ];
        
        if (!CONTEXTOS_VALIDOS.includes(config.contexto)) {
            throw new Error(`Contexto inválido: ${config.contexto}`);
        }
        
        // ... resto del código
        
        // IMPORTANTE: Para crear-desde-cotizacion,
        // NUNCA hacer fetch a /api/cotizaciones
        if (config.contexto === 'crear-desde-cotizacion') {
            // Verificar que no estamos intentando acceder a cotizaciones
            const dataService = this.dataService;
            
            // Override temporal del endpoint
            const endpointOriginal = dataService.apiBaseUrl;
            dataService.apiBaseUrl = '/api/prendas';  // Asegurar endpoint correcto
            
            // ... al finalizar, restaurar
        }
        
        // Guardar metadata de origen para auditoría
        this.currentEditorState = {
            contexto: config.contexto,
            origenCotizacion: config.origenCotizacion || null,
            cotizacionId: config.cotizacionId || null,
            prendaCotizacionId: config.prendaCotizacionId || null
        };
    }
}
```

### Lógica de guardado

```javascript
guardarCambios() {
    // ... validación, procesamiento de imágenes ...
    
    // Determinar endpoint y formato según contexto
    if (this.currentEditorState.contexto === 'crear-desde-cotizacion') {
        // Crear NUEVO ítem en pedido
        // Nunca actualizar cotización
        return this.dataService.guardarPrenda({
            ...prendaModificada,
            // IMPORTANTE: Limpiar referencias a cotización
            cotizacion_id: undefined,  // No guardar ID de origen
            
            // NUEVO: Guardar como "copiada desde"
            copiada_desde_cotizacion_id: this.currentEditorState.cotizacionId,
            copiada_desde_prenda_cotizacion_id: this.currentEditorState.prendaCotizacionId
        });
    }
    
    // Para otros contextos, lógica existente
    return this.dataService.guardarPrenda(prendaModificada);
}
```

---

## 🔐 AISLAMIENTO DE COTIZACIONES

### Validación de endpoints

**En SharedPrendaDataService:**

```javascript
guardarPrenda(data) {
    //  PROHIBIDO
    if (this.apiBaseUrl.includes('/api/cotizaciones')) {
        throw new Error(' VIOLACIÓN DE AISLAMIENTO: Intent to access cotizaciones API');
    }
    
    //  PROHIBIDO
    if (data.tabla_origen === 'cotizaciones') {
        throw new Error(' VIOLACIÓN: Guardando en tabla de cotizaciones');
    }
    
    //  PERMITIDO
    if (!this.apiBaseUrl.includes('/api/prendas')) {
        console.warn(' Endpoint inusual:', this.apiBaseUrl);
    }
    
    // POST a /api/prendas (crearemos nuevo producto)
    return fetch(`${this.apiBaseUrl}`, {
        method: 'POST',
        headers: {...},
        body: JSON.stringify(data)
    });
}
```

### En cada servicio

```javascript
// shared-prenda-data-service.js
const ENDPOINTS_PERMITIDOS = [
    '/api/prendas',
    '/api/storage/prendas'
];

const ENDPOINTS_PROHIBIDOS = [
    '/api/cotizaciones',
    '/api/cotizaciones/prendas',
    '/storage/cotizaciones'
];

class SharedPrendaDataService {
    constructor(config) {
        // Validar endpoint al inicializar
        if (ENDPOINTS_PROHIBIDOS.some(ep => 
            (config.apiBaseUrl || '').includes(ep))) {
            throw new Error('Endpoint prohibido para servicios compartidos');
        }
    }
}
```

---

## 📱 USO EN HTML Y JS

### En `crear-pedido-desde-cotizacion.blade.php`

```html
<!-- Cargar servicios compartidos (iguales en todos lados) -->
<script src="/js/servicios/shared/event-bus.js?v=1"></script>
<script src="/js/servicios/shared/format-detector.js?v=1"></script>
<!-- ... (resto de servicios) -->
<script src="/js/servicios/shared/prenda-service-container.js?v=1"></script>
```

### En JavaScript (`crear-pedido-editable.js`)

```javascript
// Función que ya existe: cargarPrendaCompletaDesdeCotizacion()
// (en CargadorPrendasCotizacion)
async function abrirEditorPrendaDesdeCotizacion(
    cotizacionId, 
    prendaCotizacionId, 
    datosPrenda
) {
    try {
        // 1. Obtener servicios
        const container = window.prendasServiceContainer;
        const editor = container.getService('editor');
        
        // 2. Hacer COPIA de datos (importante)
        const prendaCopia = JSON.parse(JSON.stringify(datosPrenda));
        
        // 3. Abrir editor en contexto especial
        await editor.abrirEditor({
            modo: 'crear',
            contexto: 'crear-desde-cotizacion',
            
            // IDs de origen (para auditoría/tracking)
            cotizacionId,
            prendaCotizacionId,
            
            // Datos locales copiados
            prendaLocal: prendaCopia,
            
            // Metadatos
            origenCotizacion: {
                id: cotizacionId,
                numero: window.cotizacionActual?.numero,
                cliente: window.cotizacionActual?.cliente
            },
            
            // Callback cuando usuario guarda
            onGuardar: async (prendaModificada) => {
                console.log('[crear-desde-cotizacion]  Prenda modificada');
                console.log('  - Nombre:', prendaModificada.nombre);
                console.log('  - Origen:', `Cotización ${cotizacionId}`);
                console.log('  - Se guardará como nuevo item en pedido');
                
                // Agregar al formulario de pedido (NO a cotización)
                agregarPrendaAlPedido(prendaModificada);
                
                // Cerrar modal
                cerrarModalEditor();
            },
            
            onCancelar: () => {
                console.log('[crear-desde-cotizacion]  Edición cancelada');
                cerrarModalEditor();
            }
        });
        
    } catch (error) {
        console.error('[abrirEditorPrendaDesdeCotizacion] Error:', error);
        alert('Error abriendo editor: ' + error.message);
    }
}
```

---

## 🧪 TESTING PARA ESTE FLUJO

### Test 1: Cargador de Prendas (no se modifica)

```javascript
// Ya existe: CargadorPrendasCotizacion.cargarPrendaCompletaDesdeCotizacion()
const loader = new CargadorPrendasCotizacion();
const datosPrenda = await loader.cargarPrendaCompletaDesdeCotizacion(
    123,  // cotizacionId
    456   // prendaId en cotización
);

console.log('Prenda cargada:', datosPrenda.nombre);
// Output: "Camisa"
```

### Test 2: Edición en contexto especial

```javascript
// En consola de navegador
const editor = window.prendasServiceContainer.getService('editor');

await editor.abrirEditor({
    modo: 'crear',
    contexto: 'crear-desde-cotizacion',
    cotizacionId: 123,
    prendaCotizacionId: 456,
    prendaLocal: {
        nombre: 'Camisa Original',
        tallas: [{talla: 'M', cantidad: 5}]
    },
    onGuardar: (prenda) => {
        console.log(' Guardado:', prenda.nombre);
        // Verificar que NO está en /api/cotizaciones
    }
});
```

### Test 3: Verificación de aislamiento

```javascript
// Verificar que nunca se accede a /api/cotizaciones
let cotizacionAccessAttempt = false;

const interceptFetch = window.fetch;
window.fetch = function(...args) {
    const url = args[0];
    if (typeof url === 'string' && url.includes('/api/cotizaciones')) {
        cotizacionAccessAttempt = true;
        console.error(' VIOLACIÓN: Intento de acceder a /api/cotizaciones');
    }
    return interceptFetch.apply(this, args);
};

// ... ejecutar flujo ...

console.assert(
    !cotizacionAccessAttempt,
    ' No se accedió a /api/cotizaciones'
);
```

---

## 🔄 INTEGRACIÓN CON CÓDIGO EXISTENTE

### Archivo: `crear-pedido-editable.js`

**Línea ~290-310:** Ya detecta contexto `crear-desde-cotizacion`

```javascript
//  YA EXISTE
if (window.location.pathname.includes('crear-desde-cotizacion')) {
    console.log('[cargarPrendasDesdeCotizacion] Flujo desde cotización detectado');
    return;  // No cargar masivamente, agregar individualmente
}
```

**Necesita:** Agregar función de edición:

```javascript
// NUEVO: Agregar después de cargarPrendasDesdeCotizacion()
async function editarPrendaDesdeCotizacion(
    cotizacionId, 
    prendaCotizacionId,
    datosPrenda
) {
    // Usar servicios compartidos
    const editor = window.editorPrendas;  // Inicializado al cargar
    
    const prendaCopia = JSON.parse(JSON.stringify(datosPrenda));
    
    return editor.abrirEditor({
        modo: 'crear',
        contexto: 'crear-desde-cotizacion',
        cotizacionId,
        prendaCotizacionId,
        prendaLocal: prendaCopia,
        origenCotizacion: {
            id: cotizacionId,
            numero: document.getElementById('cotizacion_search_editable')?.value
        },
        onGuardar: (prenda) => {
            // Agregar al pedido (NO a cotización)
            const gestorUI = window.gestionItemsUI;
            if (gestorUI) {
                gestorUI.agregarItemAPedido(prenda);
            }
        }
    });
}
```

---

## 📊 MATRIZ DE COMPATIBILIDAD

| Aspecto | crear-nuevo | pedidos-editable | crear-desde-cotizacion |
|---------|-------------|------------------|------------------------|
| **Origen datos** | Usuario entra libre | BD (pedido) | BD (cotización) |
| **Copia datos** | N/A | Edita original | Sí, COPIA |
| **Modifica BD** | Crea nuevo | Actualiza pedido | Crea en NEW pedido |
| **Endpoint** | `/api/prendas` | `/api/prendas` | `/api/prendas` |
| **Cotización** | No toca | No toca | Solo LEE |
| **Aislamiento** |  Seguro |  Seguro |  Seguro |

---

##  CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Actualización del Servicio (1-2 horas)

- [ ] Actualizar `SharedPrendaEditorService` con nuevo contexto
- [ ] Agregar validación de endpoints en DataService
- [ ] Documentar nuevo parámetro `contexto: 'crear-desde-cotizacion'`
- [ ] Agregar metadatos de origen (para auditoría)

### Fase 2: Integración en HTML (30 min)

- [ ] Cargar scripts de servicios en `crear-pedido-desde-cotizacion.blade.php`
- [ ] Inicializar `prendasServiceContainer`
- [ ] Guardar referencia en `window.editorPrendas`

### Fase 3: Integración en JS (1-2 horas)

- [ ] Agregar `editarPrendaDesdeCotizacion()` en `crear-pedido-editable.js`
- [ ] Conectar clic en botón "Editar" de cada prenda
- [ ] Implementar callback `onGuardar` para agregar al pedido
- [ ] Testing de flujo completo

### Fase 4: Testing de Aislamiento (1 hora)

- [ ] Verificar que NO se accede a `/api/cotizaciones`
- [ ] Verificar que NO se modifica cotización original
- [ ] Editar 5 prendas, guardar, verificar en BD
- [ ] Recargar cotización original, verificar intacta

---

## 🎯 RESULTADO FINAL

```
crear-nuevo          → Edita → Crea NUEVO pedido vacío
                      ↓
pedidos-editable     → Edita → Actualiza pedido existente
                      ↓
crear-desde-cotizacion → Edita (COPIA) → Crea NUEVO pedido con datos de cotización
                                          (cotización NO se modifica)
```

**Todos los flujos usan el MISMO servicio compartido.**
**Cotización completamente protegida.**

---

## 📞 REFERENCIA RÁPIDA

**Archivo actualizar:** `shared-prenda-editor-service.js`
**Parámetro nuevo:** `contexto: 'crear-desde-cotizacion'`
**Endpoint:** SIEMPRE `/api/prendas`, NUNCA `/api/cotizaciones`
**Aislamiento:** Validado automáticamente
**Testing:** Verificar Network tab (solo prendas, no cotizaciones)

¡Listo para adaptación! 🚀
