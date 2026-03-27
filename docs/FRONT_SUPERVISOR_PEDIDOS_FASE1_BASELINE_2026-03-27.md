# Frontend Supervisor Pedidos - Baseline Fase 1

Fecha: 2026-03-27

## Metricas iniciales

Fuente: `scripts/auditar-front-supervisor.ps1`

- `index_script_tags`: 61
- `layout_script_tags`: 15
- `index_inline_script_blocks`: 0
- `layout_inline_script_blocks`: 6
- `index_asesores_script_refs`: 7
- `layout_asesores_script_refs`: 0
- `index_asesores_blade_includes`: 12
- `index_ordersjs_refs`: 12
- `index_crear_pedido_refs`: 12

## Cambios ya aplicados (Dia 2 - inicio)

- Se creo entrypoint Vite del modulo:
  - `resources/js/supervisor-pedidos/entry.js`
- Se registro en build:
  - `vite.config.js`
- Se conecto en layout:
  - `resources/views/supervisor-pedidos/layout.blade.php`

## Nota

En esta iteracion no se retiro carga legacy de scripts para minimizar riesgo.
La siguiente iteracion migrara scripts por lotes desde Blade al entrypoint.

## Avance iteracion 2 (migracion lote seguro)

Se movieron al entrypoint (solo en vista indice):

- `js/supervisor-pedidos/modales-acciones.js`
- `js/supervisor-pedidos/tracking-modal-init.js`
- `js/supervisor-pedidos/novedades-galeria.js`
- `js/supervisor-pedidos/realtime-supervisor.js`

Adicional:

- Se elimino carga directa del archivo vacio:
  - `js/supervisor-pedidos/supervisor-pedidos-detail-modal.js`

Resultado de auditoria despues del cambio:

- `index_script_tags`: 56 (antes 61, mejora 5)
- `layout_script_tags`: 15 (sin cambio)
- `index_asesores_script_refs`: 7 (sin cambio)
- `index_asesores_blade_includes`: 12 (sin cambio)

## Avance iteracion 3 (desacople JS de asesores - lote sin DOMContentLoaded)

Se movieron al entrypoint y se retiraron de Blade:

- `js/asesores/pedidos-modal-edit.js`
- `js/asesores/invoice-from-list.js`
- `js/asesores/receipt-manager.js`
- `js/asesores/pedidos-detail-modal.js`
- `js/asesores/pedidos-anular.js`

Se dejaron en Blade por ahora (dependen de `DOMContentLoaded`):

- `js/asesores/pedidos-dropdown-simple.js`
- `js/asesores/observaciones-despacho.js`

Resultado de auditoria despues de iteracion 3:

- `index_script_tags`: 51 (baseline 61, mejora acumulada 10)
- `layout_script_tags`: 15 (sin cambio)
- `index_asesores_script_refs`: 2 (baseline 7, mejora acumulada 5)
- `index_asesores_blade_includes`: 12 (sin cambio)

## Avance iteracion 4 (encapsulacion de includes heredados)

Se creo parcial propio de supervisor para centralizar modales heredados:

- `resources/views/supervisor-pedidos/partials/modales-edicion-compartidos.blade.php`

Se reemplazo en:

- `resources/views/supervisor-pedidos/index.blade.php`

Impacto:

- La vista principal de supervisor ya no referencia `@include('asesores...')` directamente.
- La dependencia sigue existiendo, pero ahora encapsulada en parcial propio para facilitar migracion futura.

Resultado de auditoria despues de iteracion 4:

- `index_script_tags`: 51
- `layout_script_tags`: 15
- `index_asesores_script_refs`: 2
- `index_asesores_blade_includes`: 0 (baseline 12, mejora acumulada 12)

## Avance iteracion 5 (cierre desacople JS de asesores en index)

Se adaptaron scripts de asesores para soportar carga tardia (sin depender solo de `DOMContentLoaded`):

- `public/js/asesores/pedidos-dropdown-simple.js`
  - nuevo init seguro: `initPedidosDropdownSimple()`
  - guard: `window.__pedidosDropdownSimpleInitialized`
- `public/js/asesores/observaciones-despacho.js`
  - nuevo init seguro: `initObservacionesDespacho()`
  - guard: `window.__observacionesDespachoInitialized`

Se movieron al entrypoint de supervisor:

- `js/asesores/pedidos-dropdown-simple.js`
- `js/asesores/observaciones-despacho.js`

Se retiraron de:

- `resources/views/supervisor-pedidos/index.blade.php`

Resultado de auditoria despues de iteracion 5:

- `index_script_tags`: 49 (baseline 61, mejora acumulada 12)
- `layout_script_tags`: 15 (sin cambio)
- `index_asesores_script_refs`: 0 (baseline 7, mejora acumulada 7)
- `index_asesores_blade_includes`: 0 (baseline 12, mejora acumulada 12)

## Avance iteracion 6 (cierre bloque ordersjs + crear-pedido en index)

Se movio al entrypoint de supervisor:

- Bloque completo `ordersjs/tracking*` (incluyendo `tracking-modal-handler.js` como `type=module`).
- Bloque `modulos/crear-pedido/*` usado en supervisor index.
- Scripts relacionados de procesos:
  - `js/componentes/procesos-imagenes-storage.js`
  - `js/componentes/manejo-imagenes-proceso.js`
  - `js/componentes/manejador-imagen-proceso-con-indice.js`

Se retiraron las referencias directas equivalentes en:

- `resources/views/supervisor-pedidos/index.blade.php`

Resultado de auditoria despues de iteracion 6:

- `index_script_tags`: 22 (baseline 61, mejora acumulada 39)
- `layout_script_tags`: 15 (sin cambio)
- `index_asesores_script_refs`: 0
- `index_asesores_blade_includes`: 0
- `index_ordersjs_refs`: 0 (baseline 12, mejora acumulada 12)
- `index_crear_pedido_refs`: 0 (baseline 12, mejora acumulada 12)

## Avance iteracion 7 (extraccion de inline scripts en layout)

Se extrajeron bloques inline del layout a archivos externos:

- `public/js/supervisor-pedidos/layout/jquery-bootstrap-loader.js`
  - fallback jQuery + carga de bootstrap bundle.
- `public/js/supervisor-pedidos/layout/echo-ready.js`
  - `waitForEcho` / `notifyEchoReady`.
- `public/js/supervisor-pedidos/layout/nav-protector.js`
  - protector de `top-nav` + `limpiarParametrosVacios`.

Se cablearon en:

- `resources/views/supervisor-pedidos/layout.blade.php`

Resultado de auditoria despues de iteracion 7:

- `index_script_tags`: 22
- `layout_script_tags`: 14
- `layout_inline_script_blocks`: 2 (baseline 6, mejora acumulada 4)
- `index_asesores_script_refs`: 0
- `index_asesores_blade_includes`: 0
- `index_ordersjs_refs`: 0
- `index_crear_pedido_refs`: 0

## Avance iteracion 8 (extraccion bloque grande layout: notificaciones y filtros)

Se extrajo el bloque inline principal del layout (menu usuario/notificaciones, badge sidebar, filtros flotantes):

- Nuevo archivo:
  - `public/js/supervisor-pedidos/layout/notifications-and-filters.js`

Se reemplazo el inline en:

- `resources/views/supervisor-pedidos/layout.blade.php`
  - ahora carga: `js/supervisor-pedidos/layout/notifications-and-filters.js`

Ajuste tecnico:

- Se removio dependencia a `{{ route(...) }}` dentro del JS externo y se uso endpoint absoluto:
  - `/supervisor-pedidos/ordenes-pendientes-count`

Resultado de auditoria despues de iteracion 8:

- `index_script_tags`: 22
- `layout_script_tags`: 14
- `layout_inline_script_blocks`: 1 (baseline 6, mejora acumulada 5)
- `index_asesores_script_refs`: 0
- `index_asesores_blade_includes`: 0
- `index_ordersjs_refs`: 0
- `index_crear_pedido_refs`: 0

Inline restante en layout:

- Bootstrap de `window.usuarioAutenticado` (se mantiene en Blade por datos server-side).
