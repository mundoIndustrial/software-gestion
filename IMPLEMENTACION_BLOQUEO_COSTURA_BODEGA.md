# Implementación: Bloqueo del Recibo COSTURA-BODEGA en supervisor-pedidos

## Descripción del Problema
En la vista `supervisor-pedidos`, cuando se abre un modal para ver los recibos de una prenda que tiene `de_bodega == 1`, el sistema mostraba el recibo de "COSTURA-BODEGA". Según la solicitud, este recibo NO debe aparecer en esta vista, incluyendo:
1. El modal de selector de procesos
2. El modal de recibos principal
3. La navegación entre procesos

## Solución Implementada

Se agregó lógica en TRES puntos estratégicos para excluir completamente el recibo de COSTURA-BODEGA en la vista de supervisor-pedidos:

### 1. **ReceiptBuilder.js** - Construcción de Lista de Recibos (Módulo ES6)
**Archivo:** `/public/js/modulos/pedidos-recibos/utils/ReceiptBuilder.js`

Se agregó una condición que detecta si estamos en la vista `supervisor-pedidos` y excluye el recibo base si es COSTURA-BODEGA:

```javascript
// CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA-BODEGA en supervisor-pedidos
const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
const excluirCosturaBodega = esSupervisorPedidos && prenda.de_bodega == 1;

if (excluirCosturaBodega) {
    console.log('📋 [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda:', prenda.nombre);
}

if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
    // ... código para agregar recibo base
}
```

**Efecto:** El recibo base de COSTURA-BODEGA NO se incluirá en la lista de recibos cuando la prenda tenga `de_bodega == 1`.

### 2. **PedidosRecibosModule.js** - Validación Extra en abrirRecibo
**Archivo:** `/public/js/modulos/pedidos-recibos/PedidosRecibosModule.js`

Se agregó una validación adicional que bloquea la apertura directa de recibos tipo `costura-bodega` en supervisor-pedidos:

```javascript
// VALIDACIÓN: Bloquear COSTURA-BODEGA en supervisor-pedidos
const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
if (esSupervisorPedidos && tipoRecibo === 'costura-bodega') {
    console.warn('🚫 [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA en supervisor-pedidos - BLOQUEADO');
    return;
}
```

**Efecto:** Incluso si por alguna razón se intenta abrir el recibo directamente, será bloqueado.

### 3. **recibos-process-selector.blade.php** - Exclusión en Selector de Procesos
**Archivo:** `/resources/views/components/modals/recibos-process-selector.blade.php`

Se agregó la misma lógica de exclusión en la función `renderizarPrendasEnSelector`:

```javascript
// CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA-BODEGA en supervisor-pedidos
const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
const excluirCosturaBodega = esSupervisorPedidos && prenda.de_bodega == 1;

if (excluirCosturaBodega) {
    console.log('📋 [renderizarPrendasEnSelector] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda:', prenda.nombre);
}

if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
    // ... código para agregar recibo base
}
```

**Efecto:** El selector de recibos NO mostrará la opción de COSTURA-BODEGA en supervisor-pedidos.

## Flujo Bloqueado

```
[Usuario en supervisor-pedidos hace clic en una prenda]
         ↓
[Se abre selector de recibos]
         ↓
[Selector renderiza prendas y procesos]
         ↓
✅ BLOQUEO #1: COSTURA-BODEGA NO aparece en selector (recibos-process-selector.blade.php)
         ↓
[Usuario selecciona un proceso diferente]
         ↓
[Se intenta abrir ese recibo]
         ↓
✅ BLOQUEO #2: Si de alguna manera intenta acceder a COSTURA-BODEGA directamente, será bloqueado (PedidosRecibosModule.js)
         ↓
✅ BLOQUEO #3: En la lista de recibos disponibles, COSTURA-BODEGA no estará (ReceiptBuilder.js)
```

## Comportamiento Esperado

### ANTES:
- Modal selector se abre
- Se muestra "Bodega" (COSTURA-BODEGA) en la lista de procesos
- Usuario puede seleccionar ese recibo
- Se abre el modal de "RECIBO DE COSTURA-BODEGA"

### DESPUÉS:
- Modal selector se abre
- Se muestran SOLO los procesos adicionales (bordado, estampado, DTF, etc.)
- El recibo base de "Bodega" (COSTURA-BODEGA) NO aparece en la lista
- En la consola aparecen logs de exclusión
- Si se intenta acceder directamente, se ve el warning de bloqueo

## Debug/Logs Disponibles

Cuando abras la consola del navegador (F12) en supervisor-pedidos, verás logs como:

```
📋 [renderizarPrendasEnSelector] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda: CAMIS DRILL
📋 [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda: CAMIS DRILL
```

Si se intenta abrir directamente (y se bloquea):
```
🚫 [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA en supervisor-pedidos - BLOQUEADO
```

## Archivos Modificados

1. [ReceiptBuilder.js](public/js/modulos/pedidos-recibos/utils/ReceiptBuilder.js)
2. [PedidosRecibosModule.js](public/js/modulos/pedidos-recibos/PedidosRecibosModule.js)
3. [recibos-process-selector.blade.php](resources/views/components/modals/recibos-process-selector.blade.php)

## Compatibilidad

- ✅ No afecta otras vistas (registros, visualizador-logo, etc.)
- ✅ No afecta prendas sin `de_bodega == 1`
- ✅ Los procesos adicionales siguen viéndose normalmente
- ✅ Validación en tres niveles para máxima seguridad

## Notas Técnicas

- La detección de `supervisor-pedidos` se realiza usando `window.location.pathname.includes('/supervisor-pedidos')`
- La exclusión solo afecta prendas donde `de_bodega == 1`
- La lógica es idéntica en los 3 puntos para mantener consistencia
- Cada punto tiene logs de debug para facilitar troubleshooting

