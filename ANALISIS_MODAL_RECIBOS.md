# Análisis: Cómo Funciona la Apertura de Modales en Supervisor-Pedidos

## Resumen Ejecutivo
En `supervisor-pedidos`, cuando haces clic en "Ver Recibos", se abre un modal que muestra los recibos de producción. Este sistema usa un patrón de **lazy loading** que carga módulos bajo demanda para optimizar el rendimiento.

---

## Flujo de Ejecución en Supervisor-Pedidos

### 1. **Click en "Ver Recibos"**
```javascript
// En supervisor-pedidos/index.js
window.abrirSelectorRecibos(pedidoId);
```

### 2. **Sistema de Lazy Loading**
El archivo `InvoiceLazyLoader.js` intercepta la llamada:

```javascript
// InvoiceLazyLoader.js
window.abrirSelectorRecibos = async function(pedidoId) {
    // Carga los módulos necesarios bajo demanda
    await this.cargarModulosRecibos();
    
    // Una vez cargados, ejecuta la función real
    if (window.invoiceFromListOrchestrator) {
        return window.invoiceFromListOrchestrator.abrirSelectorRecibos(pedidoId);
    }
};
```

### 3. **Módulos Cargados Dinámicamente**
Los siguientes módulos se cargan solo cuando se necesitan:
- `InvoiceDataFetcher.js` - Obtiene datos del servidor
- `ReceiptsModalManager.js` - Gestiona el modal
- `ComponentLoader.js` - Carga componentes
- `LoadingManager.js` - Muestra spinner de carga
- `NotificationManager.js` - Muestra notificaciones
- `InvoiceFromListOrchestrator.js` - Orquesta todo

### 4. **Obtención de Datos**
```javascript
// InvoiceDataFetcher.js
async obtenerDatosRecibos(pedidoId) {
    const response = await fetch(`/pedidos-public/${pedidoId}/recibos-datos`);
    return await response.json();
}
```

### 5. **Renderización del Modal**
```javascript
// ReceiptsModalManager.js o similar
crearModalRecibos(datos) {
    // Renderiza el modal con los datos obtenidos
    // Muestra prendas, procesos, recibos, etc.
}
```

---

## Componentes Clave

### Modal HTML
```html
<!-- En supervisor-pedidos/index.blade.php -->
<button type="button" id="modal-overlay" 
        style="position: fixed; inset: 0px; background: rgba(0, 0, 0, 0.5); 
               z-index: 9997; display: none;" 
        onclick="closeModalOverlay()">
</button>

<div id="order-detail-modal-wrapper" 
     style="position: fixed; top: 50%; left: 50%; 
             transform: translate(-50%, -50%); z-index: 9998; display: none;">
    <x-orders-components.order-detail-modal />
</div>
```

### Componente de Recibos
```html
<!-- En components/modals/recibos-process-selector.blade.php -->
<div id="recibos-process-selector-overlay" style="display: none; ..."></div>
<div id="recibos-process-selector-modal" style="display: none; ...">
    <!-- Contenido del modal -->
</div>
```

---

## Ventajas del Sistema de Lazy Loading

1. **Rendimiento Optimizado**: Los módulos se cargan solo cuando se necesitan
2. **Precarga Inteligente**: Detecta si la página necesita factura/recibos y precarga módulos
3. **Reutilizable**: El mismo sistema funciona en múltiples vistas
4. **Mantenible**: Módulos desacoplados y especializados
5. **Fallback**: Si falla la carga, intenta inicialización manual

---

## Implementación en Visualizador-Logo

### Cambios Realizados

1. **Agregado el componente de recibos**:
```blade
@include('components.modals.recibos-process-selector')
```

2. **Actualizado openPedidoRecibos()**:
```javascript
function openPedidoRecibos(pedidoId) {
    if (typeof window.abrirSelectorRecibos === 'function') {
        window.abrirSelectorRecibos(pedidoId);
    } else {
        alert('El modal de recibos no está disponible. Por favor recarga la página.');
    }
}
```

3. **Filtrado de Procesos**:
En `recibos-process-selector.blade.php`, se agregó lógica para excluir recibos de costura en prendas de bodega:
```javascript
// No mostrar recibo de COSTURA en prendas de bodega
const esCostura = String(tipoProceso || '').toUpperCase() === 'COSTURA';
if ((esSupervisorPedidos || esRegistros) && prenda.de_bodega == 1 && esCostura) {
    return; // Skip costura en prendas de bodega
}
```

---

## Flujo Completo en Visualizador-Logo

```
1. Usuario hace clic en "Ver Recibos"
   ↓
2. Se llama openPedidoRecibos(pedidoId)
   ↓
3. Se verifica si window.abrirSelectorRecibos existe
   ↓
4. Si no existe, el lazy loader la carga automáticamente
   ↓
5. Se obtienen datos de /pedidos-public/{pedidoId}/recibos-datos
   ↓
6. Se renderizan las prendas y procesos en el modal
   ↓
7. Se filtran procesos de costura en prendas de bodega
   ↓
8. Modal se muestra al usuario
```

---

## Archivos Involucrados

### Frontend
- `resources/views/visualizador-logo/pedidos-visualizacion.blade.php` - Vista principal
- `resources/views/components/modals/recibos-process-selector.blade.php` - Modal de recibos
- `public/js/modulos/invoice/InvoiceLazyLoader.js` - Cargador lazy
- `public/js/modulos/invoice/InvoiceFromListOrchestrator.js` - Orquestador
- `public/js/modulos/invoice/InvoiceDataFetcher.js` - Obtención de datos

### Backend
- Ruta: `/pedidos-public/{pedidoId}/recibos-datos` - Endpoint que retorna datos de recibos

---

## Notas Importantes

1. **El modal se reutiliza**: El mismo modal se usa en supervisor-pedidos, registros y visualizador-logo
2. **Lazy loading automático**: Si `abrirSelectorRecibos` no existe, se carga automáticamente
3. **Filtrado de procesos**: Las prendas de bodega no muestran recibos de costura
4. **Componente reutilizable**: El componente `recibos-process-selector.blade.php` es agnóstico a la vista

---

## Debugging

Para verificar que todo funciona correctamente:

```javascript
// En la consola del navegador
window.estadoModulosLazy() // Ver estado de módulos cargados
window.precargarTodosModulos() // Precargar todos los módulos
window.limpiarModulosLazy() // Limpiar módulos (para testing)
```

---

## Conclusión

El sistema de supervisor-pedidos usa un patrón de lazy loading muy eficiente que permite cargar módulos bajo demanda. La vista de visualizador-logo ahora usa el mismo sistema, lo que garantiza consistencia y reutilización de código.
