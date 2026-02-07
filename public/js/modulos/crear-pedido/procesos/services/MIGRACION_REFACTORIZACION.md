# Refactorización de PrendaEditor - Guía de Uso

## 📋 Resumen de Cambios

### Arquitectura Anterior (Acoplada)
```
PrendaEditor 
├── Lógica de negocio (mezclada)
├── Acceso directo al DOM (50+ getElementById)
├── Llamadas fetch directas
└── Dependencias globales (window.*)
```

### Nueva Arquitectura (Desacoplada)
```
PrendaEditor (Orquestador)
├── PrendaEditorService (Lógica de negocio)
├── PrendaDOMAdapter (Acceso a DOM)
├── PrendaAPI (Llamadas HTTP)
├── PrendaEventBus (Eventos/Comunicación)
└── Inyección de dependencias
```

---

## 🚀 Cómo Usar

### **1. Inicialización Básica**

```javascript
// Opción A: Con valores por defecto
const editor = new PrendaEditor();

// Opción B: Con dependencias personalizadas (RECOMENDADO)
const eventBus = new PrendaEventBus();
const api = new PrendaAPI('/base-url-api');
const domAdapter = new PrendaDOMAdapter('#modal-id');
const service = new PrendaEditorService({ api, eventBus });

const editor = new PrendaEditor({
    api,
    eventBus,
    domAdapter,
    service,
    notificationService: miServicioNotificaciones
});
```

### **2. Abrir Modal para Nueva Prenda**

```javascript
editor.abrirModal(
    false,                    // No es edición
    null,                     // Sin índice
    cotizacionSeleccionada    // Cotización (opcional)
);
```

### **3. Cargar Prenda para Edición**

```javascript
editor.cargarPrendaEnModal(miPrenda, indexPrenda);
```

### **4. Cargar Múltiples Prendas desde Cotización**

```javascript
const prendasProcesadas = editor.cargarPrendasDesdeCotizacion(
    arrayPrendas,
    datosCotizacion
);
```

### **5. Escuchar Eventos**

```javascript
// Suscribirse a eventos
editor.eventBus.on(PrendaEventBus.EVENTOS.PRENDA_CARGADA, (datos) => {
    console.log('Prenda cargada:', datos);
});

editor.eventBus.on(PrendaEventBus.EVENTOS.TELAS_DESDE_COTIZACION, (telas) => {
    console.log('Telas desde cotización:', telas);
});

editor.eventBus.on(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, (error) => {
    console.error('Error:', error.mensaje);
});
```

---

## 📦 Archivos Creados

| Archivo | Responsabilidad | Dependencias |
|---------|-----------------|--------------|
| `prenda-event-bus.js` | Sistema de eventos (pub/sub) | Ninguna |
| `prenda-api.js` | Abstracción de API/HTTP | Ninguna |
| `prenda-dom-adapter.js` | Adaptador de acceso al DOM | Ninguna |
| `prenda-editor-service.js` | Lógica de negocio | `api`, `eventBus` |
| `prenda-editor-refactorizado.js` | Orquestador principal | TOD@S |

---

## 🔧 Características Principales

### PrendaEventBus
```javascript
// Emitir evento
eventBus.emit('nombre-evento', datos);

// Suscribirse
const unsubscribe = eventBus.on('nombre-evento', (datos) => {
    // La función unsubscribe() desinscribe automáticamente
});

// Suscribirse una única vez
eventBus.once('nombre-evento', (datos) => {
    // Se ejecuta solo una vez
});

// Eventos estándar disponibles
PrendaEventBus.EVENTOS.PRENDA_CARGADA
PrendaEventBus.EVENTOS.TELAS_CARGADAS
PrendaEventBus.EVENTOS.PROCESOS_CARGADOS
PrendaEventBus.EVENTOS.ERROR_OCURRIDO
// ... y muchos más
```

### PrendaAPI
```javascript
// Todos los endpoints están abstraídos
await api.obtenerTiposManga();
await api.cargarTelasDesdeCotizacion(cotizacionId, prendaId);
await api.obtenerTallasDisponibles(generoId);
await api.procesarProcesos(procesoId);

// Fácil cambiar endpoints sin tocar el código que usa la API
```

### PrendaDOMAdapter
```javascript
// Acceso seguro al DOM sin selectors hardcoded
domAdapter.establecerNombrePrenda('Mi Prenda');
domAdapter.obtenerOrigen();
domAdapter.establecerOrigen('bodega');
domAdapter.marcarVariacion('manga', true);
domAdapter.limpiarCache(); // Limpiar caché de elementos
```

### PrendaEditorService
```javascript
// Toda la lógica de negocio
service.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
service.procesarProcesos(procesos);
service.validarPrenda(datosPrenda);
service.prepararDatosParaGuardar(datos);
service.obtenerEstado(); // Debugging
```

---

## 🎯 Casos de Uso

### Caso 1: Integración Mínima
```javascript
// Si solo necesitas el orquestador
const editor = new PrendaEditor();

// Seguir usando como antes
editor.abrirModal();
editor.cargarPrendaEnModal(prenda, index);
```

### Caso 2: Integración Completa (Recomendado)
```javascript
// Crear instancias personalizadas
const eventBus = new PrendaEventBus();
eventBus.setDebug(true); // Para logging detallado

const api = new PrendaAPI('/api');
const domAdapter = new PrendaDOMAdapter();
const service = new PrendaEditorService({ api, eventBus });

const editor = new PrendaEditor({
    api, eventBus, domAdapter, service,
    notificationService: globalNotificationService
});

// Escuchar eventos importantes
eventBus.on(PrendaEventBus.EVENTOS.TELAS_CARGADAS, (telas) => {
    console.log('Telas procesadas:', telas.length);
});

// Usar editor
editor.abrirModal();
```

### Caso 3: Testeo Unitario
```javascript
// Mock de dependencias para pruebas
const mockApi = {
    cargarTelasDesdeCotizacion: jest.fn().mockResolvedValue({...})
};

const mockEventBus = new PrendaEventBus();
const mockDomAdapter = new PrendaDOMAdapter();

const service = new PrendaEditorService({
    api: mockApi,
    eventBus: mockEventBus
});

// Ahora puedes testear sin dependencias reales
expect(service.aplicarOrigenAutomaticoDesdeCotizacion({...})).toEqual({...});
```

---

## ⚙️ Compatibilidad con Sistema Anterior

El código refactorizado maniene compatibilidad parcial con scripts globales:

```javascript
// Sigueasignando a window para compatibilidad
window.procesosSeleccionados = service.procesosSeleccionados;
window.telasAgregadas = service.telasAgregadas;
window.tallasRelacionales = service.tallasRelacionales;

// Pero RECOMENDAMOS acceder mediante el servicio:
editor.obtenerServicio().procesosSeleccionados;
```

---

## 📝 Migración del Código Existente

### Antes (Acoplado)
```javascript
class PrendaEditor {
    constructor() {
        this.prendas = [];
        this.modal = document.getElementById('modal'); // ❌ Acoplado
    }
    
    cargarPrenda(prenda) {
        // Mezcla lógica + DOM + API
        const origen = prenda.origen || 'confeccion';
        document.getElementById('origen').value = origen; // ❌ DOM directo
        
        fetch('/api/telas/...') // ❌ API directa
            .then(r => r.json())
            .then(d => {
                window.telas = d; // ❌ Global
            });
    }
}
```

### Después (Desacoplado)
```javascript
// Los mismos métodos públicos pero sin acoplamiento
class PrendaEditor {
    constructor(opciones = {}) {
        this.service = opciones.service; // ✅ Inyectado
        this.domAdapter = opciones.domAdapter; // ✅ Inyectado
        this.api = opciones.api; // ✅ Inyectado
    }
    
    async cargarPrenda(prenda) {
        // Delegación clara
        const prendaProcesada = this.service.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
        this.domAdapter.establecerOrigen(prendaProcesada.origen);
        
        const telas = await this.api.cargarTelasDesdeCotizacion(...);
        this.service.procesarTelas(telas);
    }
}
```

---

## 🐛 Debugging

### Habilitar modo debug en EventBus
```javascript
eventBus.setDebug(true);
// Ahora verás todos los eventos: [EventBus] Emitiendo evento: prenda:cargada { ... }
```

### Ver historial de eventos
```javascript
const historial = eventBus.obtenerHistorial(10); // Últimos 10 eventos
console.table(historial);
```

### Obtener estado actual
```javascript
const estado = editor.obtenerEstado();
console.log('Estado completo:', estado);
// {
//   prendaActual: {...},
//   cotizacionActual: {...},
//   telasAgregadas: [...],
//   procesosSeleccionados: {...},
//   // ...
// }
```

---

## ✅ Ventajas de la Refactorización

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Testabilidad** | ❌ Imposible | ✅ Completa |
| **Reutilización** | ❌ Monolítica | ✅ Componible |
| **Mantenibilidad** | ❌ Difícil | ✅ Clara separación |
| **Debugging** | ❌ Spaghetti | ✅ Trazas claras |
| **Cambios de API** | ❌ Afecta todo | ✅ Solo API.js |
| **Cambios de DOM** | ❌ Afecta todo | ✅ Solo Adapter.js |
| **Escalabilidad** | ❌ Limitada | ✅ Sin límites |

---

## 📞 Próximos Pasos

1. **Incluir scripts** en tu HTML en este orden:
   ```html
   <script src="/js/modulos/crear-pedido/procesos/services/prenda-event-bus.js"></script>
   <script src="/js/modulos/crear-pedido/procesos/services/prenda-api.js"></script>
   <script src="/js/modulos/crear-pedido/procesos/services/prenda-dom-adapter.js"></script>
   <script src="/js/modulos/crear-pedido/procesos/services/prenda-editor-service.js"></script>
   <script src="/js/modulos/crear-pedido/procesos/services/prenda-editor-refactorizado.js"></script>
   ```

2. **Inicializar** donde usas PrendaEditor:
   ```javascript
   const editor = new PrendaEditor({
       notificationService: tuServicioNotificaciones
   });
   ```

3. **Migrar** gradualmente desde `prenda-editor.js` (antiguo) a `prenda-editor-refactorizado.js`

4. **Escribir tests** usando las dependencias inyectables

5. **Opcionalmente** crear adapters adicionales (ej: `prenda-api-mock.js` para tests)

---

## 🎓 Conceptos Aplicados

- **Inyección de Dependencias**: Sin dependencias globales
- **Separación de Responsabilidades**: Cada clase hace una cosa bien
- **Patrón Observer**: EventBus para comunicación desacoplada
- **Adapter Pattern**: DOM y API abstraídos
- **Service Layer**: Lógica de negocio independiente

---

**Creado**: Febrero 7, 2026  
**Versión**: 1.0 Refactorización Completa
