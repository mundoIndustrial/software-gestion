# Implementación: Bloqueo del Recibo COSTURA-BODEGA en supervisor-pedidos

## Descripción del Problema
En la vista `supervisor-pedidos`, cuando se abre un modal para ver los recibos de una prenda que tiene `de_bodega == 1`, el sistema mostraba el recibo de "COSTURA-BODEGA". Según la solicitud, este recibo NO debe aparecer en esta vista.

## Solución Implementada

Se agregó lógica en DOS puntos estratégicos para excluir el recibo de COSTURA-BODEGA en la vista de supervisor-pedidos:

### 1. **ReceiptBuilder.js** - Construcción de Lista de Recibos
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

## Comportamiento Esperado

### ANTES:
- Modal se abre
- Se muestra "RECIBO DE COSTURA-BODEGA"
- Usuario puede ver el recibo

### DESPUÉS:
- Modal se abre
- Se muestra SOLO los procesos adicionales (bordado, estampado, DTF, etc.)
- El recibo base de COSTURA-BODEGA NO aparece
- En la consola aparece: `📋 [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda: NOMBRE_PRENDA`

## Debug/Logs Disponibles

Cuando abras la consola del navegador (F12) en supervisor-pedidos, verás logs como:

```
📋 [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO en supervisor-pedidos para prenda: CAMIS DRILL
```

Si se intenta abrir directamente (y se bloquea):
```
🚫 [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA en supervisor-pedidos - BLOQUEADO
```

## Archivos Modificados

1. `/public/js/modulos/pedidos-recibos/utils/ReceiptBuilder.js`
2. `/public/js/modulos/pedidos-recibos/PedidosRecibosModule.js`

## Pruebas Realizadas

✅ Cambios implementados en los archivos JS
✅ Lógica de exclusión agregada en dos puntos estratégicos
✅ Logs de debug agregados para validación

## Notas Técnicas

- La detección de `supervisor-pedidos` se realiza usando `window.location.pathname.includes('/supervisor-pedidos')`
- La exclusión solo afecta prendas donde `de_bodega == 1`
- Los procesos adicionales (bordado, estampado, etc.) SEGUIRÁN viéndose normalmente
- El cambio es específico a la vista de `supervisor-pedidos` y NO afecta otras vistas
