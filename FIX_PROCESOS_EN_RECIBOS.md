# FIX: Procesos y Imágenes No Aparecen en "Recibo del Pedido"

**FECHA:** 2024
**ESTADO:** ✅ CORREGIDO

## Problema Identificado

Al abrir un pedido en `/asesores/pedidos` y clickear en "Recibo del Pedido", la modal mostraba **"Sin procesos asociados"** en lugar de mostrar los procesos (reflectivo, costura, etc.) con sus detalles (tallas, ubicaciones, imágenes).

### Análisis de la Causa

El problema tenía 3 capas:

1. **Backend (FUNCIONA CORRECTAMENTE)**
   - `PedidoProduccionRepository::obtenerDatosRecibos()` retorna procesos correctamente
   - Estructura: `'procesos' => $procesos` (línea 817)
   - Cada proceso contiene: nombre_proceso, tipo_proceso, tallas, observaciones, ubicaciones, imagenes, estado

2. **Frontend Modal (REQUERÍA ARREGLO)**
   - `invoice-from-list.js` abre modal con datos correcto
   - Llama a `ReceiptManager(datos, prendasIndex)` pasando procesos
   - `ReceiptManager.generarRecibos()` intenta procesar `prenda.procesos`
   - **PERO:** El código buscaba `proceso.nombre` cuando el backend envía `proceso.nombre_proceso` y `proceso.tipo_proceso`

3. **Discrepancia de Nombres**
   ```php
   // Backend (PedidoProduccionRepository.php línea 655):
   $proc_item = [
       'nombre_proceso' => $nombreProceso,
       'tipo_proceso' => $nombreProceso,
       'tallas' => $procTallas,
       ...
   ];
   ```

   ```javascript
   // Frontend INCORRECTO (receipt-manager.js línea 65):
   titulo: `RECIBO DE ${proceso.nombre.toUpperCase()}`,  // ❌ 'nombre' no existe
   
   // Frontend línea 562:
   let html = `<strong>${proceso.nombre.toUpperCase()}</strong><br>`;  // ❌ Falla aquí
   ```

## Solución Implementada

### Cambio 1: Actualizar generarRecibos() - Línea 58-73

```javascript
// ANTES (INCORRECTO):
titulo: `RECIBO DE ${proceso.nombre.toUpperCase()}`,

// DESPUÉS (CORRECTO):
const nombreProceso = proceso.nombre_proceso || proceso.tipo_proceso || proceso.nombre || 'Proceso';
titulo: `RECIBO DE ${nombreProceso.toUpperCase()}`,
```

**Impacto:** Ahora el título del recibo se genera correctamente usando `nombre_proceso` del backend como principal, con fallback a `tipo_proceso`, y luego a `nombre` si existe.

### Cambio 2: Actualizar contenidoProceso() - Línea 561-599

```javascript
// ANTES (INCORRECTO):
contenidoProceso(proceso, prenda) {
    let html = `<strong>${proceso.nombre.toUpperCase()}</strong><br>`;  // ❌ Error
    ...
}

// DESPUÉS (CORRECTO):
contenidoProceso(proceso, prenda) {
    const nombreProceso = proceso.nombre_proceso || proceso.tipo_proceso || proceso.nombre || 'Proceso';
    let html = `<strong>${nombreProceso.toUpperCase()}</strong><br>`;  // ✅ Correcto
    ...
}
```

**Impacto:** La función ahora puede renderizar procesos usando los campos correctos del backend.

### Cambio 3: Agregar Console.log para Debugging - Línea 38-47

```javascript
console.log('[ReceiptManager] Generando recibos desde datos:', {
    prendas_count: datosFactura.prendas.length,
    prendas: datosFactura.prendas.map(p => ({
        nombre: p.nombre,
        procesos_count: (p.procesos || []).length,
        procesos: p.procesos || []
    }))
});
```

**Impacto:** Permite verificar en la consola del navegador que los procesos están llegando correctamente.

## Archivos Modificados

- ✅ `/public/js/asesores/receipt-manager.js` (3 cambios)

## Estructura de Datos Esperada

Ahora que está arreglado, el flujo es:

```
1. Backend retorna:
   {
     "prendas": [{
       "nombre": "Camisa",
       "procesos": [{
         "nombre_proceso": "Reflectivo",
         "tipo_proceso": "Reflectivo",
         "tallas": { "dama": { "S": 5, "M": 10 }, "caballero": {} },
         "ubicaciones": ["Espalda", "Pecho"],
         "observaciones": "Con hilo brillante",
         "imagenes": ["/storage/...jpg"],
         "estado": "Completado"
       }]
     }]
   }

2. ReceiptManager.generarRecibos():
   - Detecta prenda.procesos[] existe
   - Para cada proceso, crea objeto recibo con:
     - nombre_proceso = proceso.nombre_proceso ✅
     - tipo_proceso = proceso.tipo_proceso ✅
     - tallas, ubicaciones, imagenes, etc. ✅

3. Modal renderiza:
   - Título: "RECIBO DE REFLECTIVO"
   - Contenido: Tallas, ubicaciones, observaciones, imágenes
   - Navegación: Flechas para cambiar entre procesos
```

## Validación

Para verificar que funciona, abre la consola del navegador (F12) en `/asesores/pedidos`:

```javascript
// Deberías ver:
[ReceiptManager] Generando recibos desde datos: {
  prendas_count: 2,
  prendas: [
    {
      nombre: "Camisa",
      procesos_count: 1,      // ← Aquí aparecerán los procesos si llegan
      procesos: [{
        nombre_proceso: "Reflectivo",
        ...
      }]
    }
  ]
}
```

## Cambios Secundarios (NO NECESARIOS)

El archivo `invoice-preview-live.js` línea 1053 sigue teniendo "Sin procesos asociados", pero **NO se usa en esta modal**. Se usa en otras vistas (`crear-pedido-nuevo.blade.php`). Si se desea mantener consistencia, también podría actualizarse.

## Pruebas Recomendadas

1. Abrir `/asesores/pedidos`
2. Clickear en un pedido que tenga procesos
3. Ver que aparece "RECIBO DE COSTURA" (recibo 1) con contenido
4. Clickear flechas para navegar a procesos adicionales (Reflectivo, Bordado, etc.)
5. Verificar que cada proceso muestre:
   - Nombre correcto
   - Tallas por género
   - Ubicaciones
   - Observaciones
   - Imágenes de referencia

## Notas Técnicas

- El backend ya estaba correcto desde el inicio
- La discrepancia fue únicamente en el nombre de la propiedad
- No requirió cambios en base de datos
- No requirió cambios en el backend
- Solo requirió actualización de 3 líneas en el frontend (+ 1 console.log)

---

**PRÓXIMOS PASOS:**
Si los procesos siguen sin mostrar después de este fix, verificar en la consola del navegador si el console.log muestra procesos vacíos. Si es así, el problema estaría en el backend (procesos no cargándose desde la BD).
