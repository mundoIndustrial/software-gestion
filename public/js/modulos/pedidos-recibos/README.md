# Módulo Pedidos-Recibos

Módulo modular para la gestión de recibos dinámicos en el sistema de pedidos. Proporciona componentes reutilizables para abrir, renderizar y navegar entre recibos (costura, bordado, estampado, etc.).

## Estructura

```
pedidos-recibos/
├── components/
│   ├── ModalManager.js          # Gestión de apertura/cierre de modales
│   ├── CloseButtonManager.js    # Botón X de cierre
│   ├── NavigationManager.js     # Navegación entre procesos (flechas)
│   ├── GalleryManager.js        # Galería de imágenes
│   └── ReceiptRenderer.js       # Renderizado del contenido
├── utils/
│   ├── ReceiptBuilder.js        # Construcción de lista de recibos
│   └── Formatters.js            # Formateo de descripciones y datos
├── PedidosRecibosModule.js      # Módulo principal (orquestador)
├── index.js                     # Punto de entrada
└── README.md                    # Este archivo
```

## Uso

### Instalación

Incluir el módulo en tu HTML:

```html
<script type="module">
    import { PedidosRecibosModule } from '/js/modulos/pedidos-recibos/PedidosRecibosModule.js';
</script>
```

### API Principal

#### Abrir un recibo

```javascript
window.pedidosRecibosModule.abrirRecibo(
    pedidoId,      // number: ID del pedido
    prendaId,      // number: ID de la prenda
    tipoRecibo,    // string: tipo ("costura", "bordado", etc.)
    prendaIndex    // number (opcional): índice de la prenda
);
```

#### Versión legacy (compatible)

```javascript
// Mantiene compatibilidad con código anterior
openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo, prendaIndex);
```

#### Cerrar recibo

```javascript
window.pedidosRecibosModule.cerrarRecibo();
// O versión legacy
cerrarModalRecibos();
```

#### Abrir galería

```javascript
await window.pedidosRecibosModule.abrirGaleria();
```

#### Obtener estado

```javascript
const estado = window.pedidosRecibosModule.getEstado();
// {
//   pedidoId,
//   prendaId,
//   tipoProceso,
//   datosCompletos,
//   procesosActuales,
//   procesoActualIndice,
//   prendaPedidoId,
//   imagenesActuales
// }
```

## Componentes

### ModalManager
- Gestiona el estado del modal
- Controla visibilidad y z-index
- Mantiene estado global sincronizado

### CloseButtonManager
- Crea dinámicamente el botón X
- Configura MutationObserver para auto-limpieza
- Maneja eventos de cierre

### NavigationManager
- Configura flechas anterior/siguiente
- Navega entre procesos de una prenda
- Actualiza visibilidad dinámicamente

### GalleryManager
- Obtiene imágenes desde el servidor
- Renderiza grid de imágenes
- Integra con modal de imagen grande

### ReceiptRenderer
- Renderiza el contenido del recibo
- Llena datos básicos (fecha, cliente, etc.)
- Elige formato según tipo (costura vs proceso)

## Utilidades

### ReceiptBuilder
- `construirListaRecibos(prenda)` - Crea array base + procesos
- `encontrarReceibo(recibos, tipo)` - Busca índice por tipo

### Formatters
- `construirDescripcionCostura(prenda)` - Formato de costura
- `construirDescripcionProceso(prenda, proceso)` - Otros procesos
- `parsearFecha(fechaStr)` - Normaliza fechas
- `formatearFecha(fecha)` - Retorna {day, month, year}

## Ejemplos

### Ejemplo 1: Abrir recibo de costura

```javascript
window.pedidosRecibosModule.abrirRecibo(
    12345,      // Pedido ID
    15,         // Prenda ID
    'costura',  // Tipo
    0           // Índice
);
```

### Ejemplo 2: Abrir recibo de bordado

```javascript
window.pedidosRecibosModule.abrirRecibo(
    12345,
    15,
    'bordado',
    0
);
```

### Ejemplo 3: Mostrar galería del recibo actual

```javascript
await window.pedidosRecibosModule.abrirGaleria();
```

### Ejemplo 4: Verificar si hay recibos pendientes

```javascript
const estado = window.pedidosRecibosModule.getEstado();
if (estado.procesosActuales.length > 1) {
    console.log('Hay más de un proceso para esta prenda');
}
```

## Integración con HTML

El módulo requiere estos elementos en el HTML:

```html
<!-- Modal wrapper -->
<div id="order-detail-modal-wrapper">
    <div class="order-detail-modal-container">
        <!-- Contenedor de la tarjeta -->
        <div class="order-detail-card">
            <!-- Título del recibo -->
            <div class="receipt-title"></div>

            <!-- Información básica -->
            <div id="cliente-value"></div>
            <div id="asesora-value"></div>
            <div id="forma-pago-value"></div>

            <!-- Fecha -->
            <div class="day-box"></div>
            <div class="month-box"></div>
            <div class="year-box"></div>

            <!-- Descripción -->
            <div id="descripcion-text"></div>

            <!-- Encargado y prendas -->
            <div id="encargado-value"></div>
            <div id="prendas-entregadas-value"></div>
        </div>

        <!-- Números de pedido -->
        <div class="pedido-number"></div>
    </div>

    <!-- Navegación -->
    <div class="arrow-container">
        <button id="prev-arrow"></button>
        <button id="next-arrow"></button>
    </div>
</div>

<!-- Overlay -->
<div id="modal-overlay"></div>

<!-- Botones toggle -->
<button id="btn-factura"></button>
<button id="btn-galeria"></button>
```

## Notas de compatibilidad

- Mantiene la API antigua (`openOrderDetailModalWithProcess`, `cerrarModalRecibos`)
- Intercepta `window.toggleGaleria` para usar la nueva galería cuando corresponda
- Expone `window.modalManager` como singleton
- Expone `window.pedidosRecibosModule` como singleton

## Ventajas de la refactorización

✅ **Componentes separados**: cada componente tiene una responsabilidad clara  
✅ **Reutilizable**: componentes pueden usarse independientemente  
✅ **Testeable**: lógica separada facilita unit tests  
✅ **Mantenible**: código organizado en carpetas por tipo  
✅ **Escalable**: fácil agregar nuevos componentes o utilidades  
✅ **Documentado**: cada componente tiene comentarios claros  

## Debugging

El módulo incluye logging extenso con colores:

```javascript
// Ver logs en la consola
// Colores:
// - Verde (#10b981): Operaciones exitosas
// - Rojo: Errores
// - Amarillo: Advertencias
```

Filtrar en DevTools por `[PedidosRecibosModule]` o componentes específicos.
