# Checklist QA Detalle Pedido Mobile

## Precondiciones
- El build de assets termina correctamente (`npm run build`).
- Abrir una página que renderice `order-detail-modal-mobile.blade.php`.

## Flujos Principales
- `COSTURA`: título, número, descripción, tallas y observaciones.
- `REFLECTIVO`: datos del proceso y número de recibo correctos.
- `PARCIAL`: hidratación correcta de datos parciales y valores mostrados.
- `BODEGA`: campos ocultos se comportan correctamente (asesora/cliente/forma de pago).
- `CONTROL CALIDAD`: navegación de procesos y proceso seleccionado correctos.
- `OPERARIO`: navegación de proceso/prenda y filtrado de procesos correctos.

## Visual/Interacción
- La galería abre y carga imágenes.
- El visor de imágenes abre/cierra y funcionan siguiente/anterior.
- El número de recibo se actualiza al cambiar proceso/prenda.
- La fecha se renderiza correctamente (día/mes/año).

## Red/API
- No hay errores JS en consola.
- El cargador dinámico responde y re-renderiza sin romper estado.
- Las observaciones de proceso se anexan correctamente cuando existen.
- El endpoint fallback de ancho/metraje funciona cuando el principal responde `404`.

## Control de Regresión
- No hay referencias a los parciales antiguos de scripts inline Blade.
- El entry de Vite se carga desde Blade vía `@vite('resources/js/orders/mobile/order-detail-mobile-modal.js')`.
