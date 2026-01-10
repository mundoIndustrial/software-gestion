# Análisis Detallado de Referencias de Archivos JS

## Mapeo de Vistas → Archivos JavaScript

Este documento muestra exactamente dónde se carga cada archivo JS.

---

## 📍 LAYOUTS BASE

### layouts/base.blade.php
```javascript
js/toast-notifications.js (línea 87, defer)
js/sidebar.js (línea 156)
js/csrf-refresh.js (línea 159)
js/sidebar-notifications.js (línea 162, defer)
js/top-nav.js (línea 163, defer)
```
**Uso:** Componentes comunes en la mayoría de vistas

### layouts/app.blade.php
```javascript
js/notifications-realtime.js (línea 159)
js/nav-search.js (línea 160)
js/contador/busqueda-header.js (línea 162)
```
**Uso:** Layout principal de la aplicación

### layouts/asesores.blade.php
```javascript
js/toast-notifications.js
js/sidebar.js
js/asesores/notifications.js
js/asesores/sidebar-responsive.js
```

### layouts/supervisor-asesores.blade.php
```javascript
js/asesores/layout.js (línea 29)
js/asesores/notifications.js (línea 30)
```

### layouts/contador.blade.php
```javascript
js/contador/editar-tallas.js (línea 117)
js/contador/editar-tallas-personalizado.js (línea 118)
js/contador/cotizacion.js (línea 119)
js/contador/contador.js (línea 120)
js/contador/notifications.js (línea 121)
js/contador/modal-calculo-costos.js (línea 122)
js/contador/visor-costos.js (línea 123)
js/contador/lightbox-imagenes.js (línea 124)
js/contador/busqueda-header.js (línea 125)
```

### layouts/insumos.blade.php
```javascript
js/insumos/layout.js (línea 24)
```

### layouts/insumos/app.blade.php
```javascript
js/insumos/layout.js (línea 200)
```

### operario/layout.blade.php
```javascript
js/toast-notifications.js (línea 188)
js/operario/layout.js (línea 189)
```

### asesores/layout.blade.php
```javascript
js/toast-notifications.js (línea 174)
js/sidebar.js (línea 175)
js/asesores/notifications.js (línea 176)
js/asesores/sidebar-responsive.js (línea 177)
```

---

## 📍 VISTAS ESPECÍFICAS

### orders/index.blade.php (MÚLTIPLES SCRIPTS)
```javascript
js/orders js/order-detail-modal-manager.js (línea 642)
js/orders js/novedades-modal.js (línea 645)
js/orders js/modules/formatting.js (línea 648)
js/orders js/modules/storageModule.js (línea 649)
js/orders js/modules/notificationModule.js (línea 650)
js/orders js/modules/rowManager.js (línea 651)
js/orders js/modules/updates.js (línea 652)
js/orders js/modules/dropdownManager.js (línea 653)
js/orders js/modules/diaEntregaModule.js (línea 654)
js/orders js/modules/cellEditModal.js (línea 657)
js/orders js/modules/cellClickHandler.js (línea 658)
js/orders js/descripcion-prendas-fix.js (línea 661)
js/orders js/orders-table-v2.js (línea 665)
js/asesores/pedidos-detail-modal.js (línea 668)
js/orders js/descripcion-prendas-modal.js (línea 669)
js/orders js/order-navigation.js (línea 670)
js/orders js/pagination.js (línea 671)
js/orders js/historial-procesos.js (línea 672)
js/orders js/realtime-listeners.js (línea 673)
js/orders-scripts/image-gallery-zoom.js (línea 674)
js/orders js/action-menu.js (línea 677)
js/orders js/filter-system.js (línea 680)
js/orders js/row-conditional-colors.js (línea 683)
js/orders js/websocket-test.js (línea 686) ⚠️ DEBUG
js/order-tracking/modules/dateUtils.js (línea 689)
js/order-tracking/modules/holidayManager.js (línea 690)
js/order-tracking/modules/areaMapper.js (línea 691)
js/order-tracking/modules/trackingService.js (línea 692)
js/order-tracking/modules/trackingUI.js (línea 693)
js/order-tracking/modules/apiClient.js (línea 694)
js/order-tracking/modules/processManager.js (línea 695)
js/order-tracking/modules/tableManager-orders-compat.js (línea 697)
js/order-tracking/modules/dropdownManager.js (línea 698)
js/order-tracking/orderTracking-v2.js (línea 699)
js/orders js/tracking-modal-handler.js (línea 702)
js/debug-sidebar.js (línea 705) ⚠️ DEBUG
```
**Nota:** Esta es la vista más pesada con 37 archivos JS

### supervisor-asesores/pedidos/index.blade.php
```javascript
js/asesores/pedidos-list.js (línea 1192)
js/asesores/pedidos.js (línea 1193)
js/asesores/pedidos-modal.js (línea 1194)
js/asesores/pedidos-dropdown-simple.js (línea 1195)
js/orders js/order-detail-modal-manager.js (línea 1196)
js/asesores/pedidos-detail-modal.js (línea 1197)
js/asesores/pedidos-table-filters.js (línea 1198)
js/order-tracking/modules/dateUtils.js (línea 1199)
js/order-tracking/modules/holidayManager.js (línea 1200)
js/order-tracking/modules/areaMapper.js (línea 1201)
js/order-tracking/modules/trackingService.js (línea 1202)
js/order-tracking/modules/trackingUI.js (línea 1203)
js/order-tracking/modules/apiClient.js (línea 1204)
js/order-tracking/modules/processManager.js (línea 1205)
js/order-tracking/modules/tableManager.js (línea 1206)
js/order-tracking/modules/dropdownManager.js (línea 1207)
js/order-tracking/orderTracking-v2.js (línea 1208)
```

### supervisor-asesores/cotizaciones/index.blade.php
```javascript
js/asesores/cotizaciones/filtros-embudo.js (línea 248)
js/asesores/cotizaciones-index.js (línea 249)
```

### supervisor-pedidos/index.blade.php
```javascript
js/supervisor-pedidos/supervisor-pedidos-detail-modal.js (línea 727)
js/supervisor-pedidos/edit-pedido.js (línea 728)
```

### tableros.blade.php
```javascript
js/tableros.js (línea 20)
js/tableros-pagination.js (línea 2482)
```

### bodega/index.blade.php
```javascript
js/order-tracking/modules/dateUtils.js (línea 281)
js/order-tracking/modules/holidayManager.js (línea 282)
js/order-tracking/modules/areaMapper.js (línea 283)
js/order-tracking/modules/trackingService.js (línea 284)
js/order-tracking/modules/trackingUI.js (línea 285)
js/order-tracking/modules/apiClient.js (línea 286)
js/order-tracking/modules/processManager.js (línea 287)
js/order-tracking/modules/tableManager.js (línea 288)
js/order-tracking/modules/dropdownManager.js (línea 289)
js/order-tracking/orderTracking-v2.js (línea 290)
js/orders js/row-conditional-colors.js (línea 293)
js/orders js/filter-system.js (línea 294)
js/bodega-table.js (línea 297)
js/bodega-detail-modal.js (línea 298)
js/bodega-edit-modal.js (línea 299)
js/bodega-cell-edit.js (línea 300)
js/bodega-tracking-modal.js (línea 301)
js/bodega-conditional-colors.js (línea 302)
js/bodega-estado-handler.js (línea 303)
js/orders js/novedades-modal.js (línea 306)
js/bodega-novedades-modal.js (línea 307)
```

### contador/index.blade.php
```javascript
js/contador/tabla-cotizaciones.js (línea 325)
js/contador/cotizacion.js (línea 328)
js/realtime-cotizaciones.js (línea 331)
```

### contador/aprobadas.blade.php
```javascript
js/contador/tabla-cotizaciones.js (línea 334)
js/contador/cotizacion.js (línea 337)
```

### contador/todas.blade.php
```javascript
js/contador/cotizacion.js (línea 177)
js/contador/tabla-cotizaciones.js (línea 180)
```

### contador/por-revisar.blade.php
```javascript
js/contador/tabla-cotizaciones.js (línea 232)
```

### contador/profile.blade.php
```javascript
js/contador/profile.js (línea 265)
```

### asesores/profile.blade.php
```javascript
js/asesores/profile.js (línea 265)
```

### cotizaciones/prenda/create.blade.php (COTIZADOR PRINCIPAL)
```javascript
js/asesores/cotizaciones/modules/ValidationModule.js (línea 1203)
js/asesores/cotizaciones/modules/TallasModule.js (línea 1204)
js/asesores/cotizaciones/modules/EspecificacionesModule.js (línea 1205)
js/asesores/cotizaciones/modules/ProductoModule.js (línea 1206)
js/asesores/cotizaciones/services/HttpService.js (línea 1208)
js/asesores/cotizaciones/services/DebugService.js (línea 1209)
js/asesores/cotizaciones/modules/FormModule.js (línea 1210)
js/asesores/cotizaciones/modules/UIModule.js (línea 1211)
js/asesores/cotizaciones/modules/ModalModule.js (línea 1212)
js/asesores/cotizaciones/modules/CotizacionPrendaApp.js (línea 1213)
js/asesores/cotizaciones/modules/index.js (línea 1214)
js/asesores/cotizaciones/tallas.js (línea 1217)
js/asesores/cotizaciones/persistencia.js (línea 1218)
js/asesores/cotizaciones/rutas.js (línea 1219)
js/asesores/cotizaciones/cotizaciones.js (línea 1220)
js/asesores/cotizaciones/productos.js (línea 1221)
js/asesores/cotizaciones/imagenes.js (línea 1222)
js/asesores/cotizaciones/especificaciones.js (línea 1223)
js/asesores/cotizaciones/guardado.js (línea 1224)
js/asesores/cotizaciones/cargar-borrador.js (línea 1225)
js/asesores/cotizaciones/imagen-borrador.js (línea 1226)
js/asesores/variantes-prendas.js (línea 1227)
js/asesores/color-tela-referencia.js (línea 1228)
js/asesores/cotizaciones/integracion-variantes-inline.js (línea 1229)
```
**Nota:** Este es el cotizador principal con 23 archivos

### cotizaciones/bordado/create.blade.php
```javascript
js/asesores/cotizaciones/persistencia.js (línea 539)
js/logo-cotizacion-tecnicas.js (línea 1312)
```

### cotizaciones/pendientes.blade.php
```javascript
js/contador/visor-costos.js (línea 948)
```

### cotizaciones/index.blade.php
```javascript
js/asesores/cotizaciones/subir-imagenes.js (línea 5)
```

### asesores/pedidos/create-friendly.blade.php
```javascript
js/asesores/cotizaciones/rutas.js (línea 129)
js/asesores/cotizaciones/pastillas.js (línea 130)
js/asesores/cotizaciones/tallas.js (línea 133)
js/asesores/cotizaciones/cotizaciones.js (línea 134)
js/asesores/cotizaciones/productos.js (línea 135)
js/asesores/cotizaciones/imagenes.js (línea 136)
js/asesores/cotizaciones/especificaciones.js (línea 137)
js/asesores/cotizaciones/reflectivo.js (línea 138)
js/asesores/cotizaciones/resumen-reflectivo.js (línea 139)
js/asesores/cotizaciones/guardado.js (línea 140)
```

### asesores/pedidos/crear-desde-cotizacion-editable.blade.php (FORMA MÁS PESADA)
```javascript
js/modulos/crear-pedido/config-pedido-editable.js (línea 694)
js/modulos/crear-pedido/helpers-pedido-editable.js (línea 695)
js/modulos/crear-pedido/gestor-fotos-pedido.js (línea 696)
js/utilidades-crear-pedido.js (línea 698)
js/modulos/crear-pedido/modales-pedido.js (línea 700)
js/modulos/crear-pedido/gestor-cotizacion.js (línea 702)
js/modulos/crear-pedido/gestor-prendas.js (línea 703)
js/modulos/crear-pedido/gestor-logo.js (línea 704)
js/modulos/crear-pedido/init-gestores-fase2.js (línea 706)
js/modulos/crear-pedido/validacion-envio-fase3.js (línea 708)
js/modulos/crear-pedido/gestor-pedido-sin-cotizacion.js (línea 710)
js/modulos/crear-pedido/init-gestor-sin-cotizacion.js (línea 711)
js/modulos/crear-pedido/gestor-prenda-sin-cotizacion.js (línea 713)
js/modulos/crear-pedido/renderizador-prenda-sin-cotizacion.js (línea 714)
js/modulos/crear-pedido/gestor-tallas-sin-cotizacion.js (línea 715)
js/modulos/crear-pedido/funciones-prenda-sin-cotizacion.js (línea 716)
js/modulos/crear-pedido/integracion-prenda-sin-cotizacion.js (línea 717)
js/modulos/crear-pedido/gestor-reflectivo-sin-cotizacion.js (línea 719)
js/modulos/crear-pedido/renderizador-reflectivo-sin-cotizacion.js (línea 720)
js/modulos/crear-pedido/funciones-reflectivo-sin-cotizacion.js (línea 721)
js/modulos/crear-pedido/reflectivo-pedido.js (línea 723)
js/modulos/crear-pedido/logo-pedido.js (línea 725)
js/modulos/crear-pedido/fotos-logo-pedido.js (línea 727)
js/modulos/crear-pedido/logo-pedido-tecnicas.js (línea 729)
js/modulos/crear-pedido/integracion-logo-pedido-tecnicas.js (línea 731)
js/modulos/crear-pedido/init-logo-pedido-tecnicas.js (línea 733)
js/templates-pedido.js (línea 735)
js/modulos/crear-pedido/validar-cambio-tipo-pedido.js (línea 737)
js/crear-pedido-editable.js (línea 739)
```
**Nota:** El formulario de crear pedido carga 29 archivos JS

### insumos/materiales/index.blade.php
```javascript
js/orders js/order-detail-modal-manager.js (línea 2388)
js/asesores/pedidos-detail-modal.js (línea 2389)
js/orders-scripts/image-gallery-zoom.js (línea 2391)
js/insumos/pagination.js (línea 2392)
```

### insumos/layout.blade.php
```javascript
js/toast-notifications.js (línea 23)
js/insumos/layout.js (línea 25)
```

### inventario-telas/index.blade.php
```javascript
js/inventario-telas/inventario.js (línea 100)
```

### balanceo/index.blade.php
```javascript
js/balanceo-pagination.js (línea 316)
```

### entrega/index.blade.php
```javascript
js/entregas js/entregas.js (línea 191)
```

### users/index.blade.php
```javascript
js/users.js (línea 261)
```

### vistas/control-calidad.blade.php
```javascript
js/control-calidad.js (línea 164)
```

### dashboard.blade.php
```javascript
js/dashboard js/dashboard.js (línea 49)
```

### components/loading-spinner.blade.php
```javascript
js/auto-loading-spinner.js (línea 314)
```

### components/paso-tres.blade.php
```javascript
js/paso-tres-cotizacion-combinada.js (línea 126)
```

### components/orders-components/order-edit-modal.blade.php
```javascript
js/orders-scripts/order-edit-modal.js (línea 786)
```

---

## 📊 Estadísticas de Carga

### Vistas con mayor número de scripts
1. **asesores/pedidos/crear-desde-cotizacion-editable.blade.php** - 29 archivos
2. **orders/index.blade.php** - 37 archivos
3. **cotizaciones/prenda/create.blade.php** - 23 archivos
4. **bodega/index.blade.php** - 20 archivos
5. **supervisor-asesores/pedidos/index.blade.php** - 17 archivos

### Archivos compartidos (más de una vista)
- `js/toast-notifications.js` - 5+ vistas
- `js/sidebar.js` - 3+ vistas
- `js/order-tracking/*` - 3 vistas (orders, bodega, supervisor-asesores)
- `js/asesores/*` - Múltiples vistas de asesores
- `js/contador/*` - Múltiples vistas de contador

### Archivos únicos (solo una vista)
- Mayoría de archivos específicos de módulo
- Archivos de componentes específicos

---

## ⚠️ Problemas Detectados en Referencias

1. **Directorio con espacios:** `js/orders js/` debería ser `js/orders/`
   - Aparece en ~40 referencias
   
2. **Debug files en producción:**
   - `js/debug-sidebar.js` en orders/index.blade.php
   - `js/orders js/websocket-test.js` en orders/index.blade.php

3. **Posibles duplicados:**
   - `cargar-borrador.js` vs `cargar-borrador-inline.js`
   - `modern-table-v2.js` y `index.js` en modern-table

---

## 📝 Notas de Importancia

- El sistema carga archivos JS adicionales en producccion con `v={{ time() }}`
- Los archivo de módulos son muy especializados (buena práctica de separación)
- El cotizador y el creador de pedidos tienen arquitecturas muy modulares
- El sistema de seguimiento de órdenes está bien separado en módulos
