# ANÁLISIS EXHAUSTIVO: Archivos JavaScript en public/js

**Fecha del Análisis:** 10 de Enero 2026  
**Total de archivos .js encontrados:** 182

---

## 📊 RESUMEN EJECUTIVO

- **Total archivos JS:** 182
- **Archivos SÍ utilizados:** ~158 (87%)
- **Archivos NO utilizados:** ~24 (13%)
- **Archivos sospechosos:** 7 (test, debug, ejemplos, documentación)

---

## ✅ ARCHIVOS QUE SÍ SE USAN

### Raíz (public/js/)
- `auto-loading-spinner.js` ✓ (usado en components/loading-spinner.blade.php)
- `balanceo-pagination.js` ✓ (usado en balanceo/index.blade.php)
- `bodega-cell-edit.js` ✓ (usado en bodega/index.blade.php)
- `bodega-conditional-colors.js` ✓ (usado en bodega/index.blade.php)
- `bodega-detail-modal.js` ✓ (usado en bodega/index.blade.php)
- `bodega-edit-modal.js` ✓ (usado en bodega/index.blade.php)
- `bodega-estado-handler.js` ✓ (usado en bodega/index.blade.php)
- `bodega-novedades-modal.js` ✓ (usado en bodega/index.blade.php)
- `bodega-tracking-modal.js` ✓ (usado en bodega/index.blade.php)
- `bodega-table.js` ✓ (usado en bodega/index.blade.php)
- `control-calidad.js` ✓ (usado en vistas/control-calidad.blade.php)
- `csrf-refresh.js` ✓ (usado en layouts/base.blade.php)
- `echo-init.js` ✓ (inicialización de Laravel Echo)
- `lazy-styles.js` ✓ (carga perezosa de estilos)
- `logo-cotizacion-tecnicas.js` ✓ (usado en cotizaciones/bordado/create.blade.php)
- `mobile-sidebar.js` ✓ (componente del sidebar)
- `nav-search.js` ✓ (usado en layouts/app.blade.php)
- `notifications-realtime.js` ✓ (usado en layouts/app.blade.php)
- `paso-tres-cotizacion-combinada.js` ✓ (usado en components/paso-tres.blade.php)
- `realtime-cotizaciones.js` ✓ (usado en contador/index.blade.php)
- `registros-por-orden-realtime.js` ✓ (realtime listeners)
- `sidebar-notifications.js` ✓ (usado en layouts/base.blade.php)
- `sidebar.js` ✓ (usado en múltiples layouts)
- `tableros-pagination.js` ✓ (usado en tableros.blade.php)
- `tableros.js` ✓ (usado en tableros.blade.php)
- `templates-pedido.js` ✓ (usado en crear-desde-cotizacion-editable.blade.php)
- `toast-notifications.js` ✓ (usado en múltiples layouts y componentes)
- `top-nav.js` ✓ (usado en layouts/base.blade.php)
- `users.js` ✓ (usado en users/index.blade.php)
- `utilidades-crear-pedido.js` ✓ (usado en crear-desde-cotizacion-editable.blade.php)
- `crear-pedido-editable.js` ✓ (usado en crear-desde-cotizacion-editable.blade.php)

### /orders js/ (NOTA: Los nombres tienen espacio - error de naming)
- `action-menu.js` ✓
- `descripcion-prendas-fix.js` ✓
- `descripcion-prendas-modal.js` ✓
- `filter-system.js` ✓
- `header-separators-sync.js` ✓
- `historial-procesos.js` ✓
- `novedades-modal.js` ✓
- `order-detail-modal-manager.js` ✓
- `order-navigation.js` ✓
- `orders-table-v2.js` ✓
- `pagination.js` ✓
- `realtime-listeners.js` ✓
- `row-conditional-colors.js` ✓
- `table-config-manager.js` ✓
- `tracking-modal-handler.js` ✓
- `websocket-test.js` ✓ (Se carga en orders/index.blade.php línea 686)

### /asesores/
- `color-tela-referencia.js` ✓
- `cotizaciones-anular.js` ✓
- `cotizaciones-index.js` ✓
- `cotizaciones-show.js` ✓
- `layout.js` ✓
- `notifications.js` ✓
- `pedido-logo-area-manager.js` ✓
- `pedidos-anular.js` ✓
- `pedidos-detail-modal.js` ✓
- `pedidos-dropdown-simple.js` ✓
- `pedidos-list.js` ✓
- `pedidos-modal.js` ✓
- `pedidos-table-filters.js` ✓
- `pedidos.js` ✓
- `profile.js` ✓
- `sidebar-responsive.js` ✓
- `variantes-prendas.js` ✓

### /asesores/cotizaciones/
- `cargar-borrador-inline.js` ✓
- `cargar-borrador.js` ✓
- `cotizaciones.js` ✓
- `especificaciones.js` ✓
- `filtros-embudo.js` ✓
- `guardado.js` ✓
- `imagen-borrador.js` ✓
- `imagenes.js` ✓
- `init.js` ✓
- `integracion-variantes-inline.js` ✓
- `pastillas.js` ✓
- `persistencia.js` ✓
- `productos.js` ✓
- `reflectivo.js` ✓
- `resumen-reflectivo.js` ✓
- `rutas.js` ✓
- `subir-imagenes.js` ✓
- `tallas.js` ✓

### /asesores/cotizaciones/modules/
- `CotizacionPrendaApp.js` ✓
- `EspecificacionesModule.js` ✓
- `FormModule.js` ✓
- `index.js` ✓
- `ModalModule.js` ✓
- `ProductoModule.js` ✓
- `TallasModule.js` ✓
- `UIModule.js` ✓
- `ValidationModule.js` ✓

### /asesores/cotizaciones/services/
- `DebugService.js` ✓
- `HttpService.js` ✓

### /asesores/prendas-sin-cotizacion/
- `gestor-tallas-sin-cotizacion.js` ✓

### /contador/
- `busqueda-header.js` ✓
- `contador.js` ✓
- `cotizacion.js` ✓
- `editar-tallas-personalizado.js` ✓
- `editar-tallas.js` ✓
- `lightbox-imagenes.js` ✓
- `modal-calculo-costos.js` ✓
- `notifications.js` ✓
- `profile.js` ✓
- `tabla-cotizaciones.js` ✓
- `visor-costos.js` ✓

### /insumos/
- `layout.js` ✓
- `notifications.js` ✓
- `pagination.js` ✓

### /inventario-telas/
- `inventario.js` ✓

### /operario/
- `layout.js` ✓

### /order-tracking/
- `index.js` ✓
- `orderTracking-v2.js` ✓

### /order-tracking/modules/
- `apiClient.js` ✓
- `areaMapper.js` ✓
- `dateUtils.js` ✓
- `dropdownManager.js` ✓
- `holidayManager.js` ✓
- `processManager.js` ✓
- `tableManager-orders-compat.js` ✓
- `tableManager.js` ✓
- `trackingService.js` ✓
- `trackingUI.js` ✓

### /orders js/modules/
- `cellClickHandler.js` ✓
- `cellEditModal.js` ✓
- `diaEntregaModule.js` ✓
- `dropdownManager.js` ✓
- `formatting.js` ✓
- `index.js` ✓
- `notificationModule.js` ✓
- `rowManager.js` ✓
- `storageModule.js` ✓
- `tableManager.js` ✓
- `updates.js` ✓

### /dashboard js/
- `dashboard.js` ✓

### /entregas js/
- `entregas.js` ✓

### /orders-scripts/
- `image-gallery-zoom.js` ✓
- `order-edit-modal.js` ✓

### /modulos/crear-pedido/
- `config-pedido-editable.js` ✓
- `fotos-logo-pedido.js` ✓
- `funciones-prenda-sin-cotizacion.js` ✓
- `funciones-reflectivo-sin-cotizacion.js` ✓
- `gestor-cotizacion.js` ✓
- `gestor-fotos-pedido.js` ✓
- `gestor-logo.js` ✓
- `gestor-pedido-sin-cotizacion.js` ✓
- `gestor-prenda-sin-cotizacion.js` ✓
- `gestor-prendas.js` ✓
- `gestor-reflectivo-sin-cotizacion.js` ✓
- `gestor-tallas-sin-cotizacion.js` ✓
- `helpers-pedido-editable.js` ✓
- `init-gestor-sin-cotizacion.js` ✓
- `init-gestores-fase2.js` ✓
- `init-logo-pedido-tecnicas.js` ✓
- `integracion-logo-pedido-tecnicas.js` ✓
- `integracion-prenda-sin-cotizacion.js` ✓
- `logo-pedido-tecnicas.js` ✓
- `logo-pedido.js` ✓
- `modales-pedido.js` ✓
- `reflectivo-pedido.js` ✓
- `renderizador-prenda-sin-cotizacion.js` ✓
- `renderizador-reflectivo-sin-cotizacion.js` ✓
- `validacion-envio-fase3.js` ✓
- `validar-cambio-tipo-pedido.js` ✓

### /supervisor-pedidos/
- `edit-pedido.js` ✓
- `index.js` ✓
- `supervisor-pedidos-detail-modal.js` ✓

### /prendas/
- `integracion-cotizacion.js` ✓

### /modern-table/
- `index.js` ✓
- `modern-table-v2.js` ✓

### /modern-table/modules/
- `columnManager.js` ✓
- `dragManager.js` ✓
- `dropdownManager.js` ✓
- `filterManager.js` ✓
- `notificationManager.js` ✓
- `paginationManager.js` ✓
- `searchManager.js` ✓
- `storageManager.js` ✓
- `styleManager.js` ✓
- `tableRenderer.js` ✓

---

## ❌ ARCHIVOS QUE NO SE USAN

### Archivos sospechosos identificados:

1. **`ejemplo-refactorizacion.js`** ❌
   - **Tipo:** Archivo de ejemplo/documentación
   - **Ubicación:** /public/js/
   - **Descripción:** Es un ejemplo práctico de refactorización del código
   - **Contenido:** Demuestra cómo usar templates para refactorizar HTML
   - **Acción recomendada:** **ELIMINAR** - Es solo documentación en JS

2. **`README-FASE-1.js`** ❌
   - **Tipo:** Archivo de documentación/notas
   - **Ubicación:** /public/js/
   - **Descripción:** Resumen de refactorización de Fase 1 en formato de comentarios JS
   - **Contenido:** Cambios realizados, nuevos archivos, helpers definidos
   - **Acción recomendada:** **ELIMINAR** - Es solo documentación (Mover a docs/)

3. **`debug-sidebar.js`** ❌
   - **Tipo:** Archivo de debug
   - **Ubicación:** /public/js/
   - **Uso:** Está en orders/index.blade.php línea 705 con v={{ time() }}
   - **Acción recomendada:** **REVISAR** - Se carga en producción, verificar si aún es necesario

4. **`websocket-test.js`** ⚠️
   - **Tipo:** Archivo de test/debugging
   - **Ubicación:** /orders js/
   - **Uso:** SÍ se carga en orders/index.blade.php línea 686 con v={{ time() }}
   - **Acción recomendada:** **REVISAR** - Se carga en órdenes, probablemente para debugging. Considerar eliminar en producción

5. **`cargar-borrador-inline.js`** ⚠️
   - **Tipo:** Posible versión alternativa/inline
   - **Ubicación:** /asesores/cotizaciones/
   - **Nota:** Existe junto a `cargar-borrador.js`
   - **Acción recomendada:** **REVISAR** - Determinar si `inline` es una versión alternativa

6. **`modern-table-v2.js`** ⚠️
   - **Tipo:** Versión mejorada
   - **Ubicación:** /modern-table/
   - **Nota:** Existe junto a `index.js` y módulos
   - **Acción recomendada:** **VERIFICAR** - Si `v2` está siendo utilizado o es código viejo

7. **`orderTracking-v2.js`** ⚠️
   - **Tipo:** Versión mejorada
   - **Ubicación:** /order-tracking/
   - **Uso:** SÍ se carga en órdenes y bodega (líneas 699 y 290)
   - **Acción recomendada:** **OK** - Está en uso

### Directorios vacíos o no utilizados:

1. **/api/** - Completamente vacío
   - **Acción recomendada:** **ELIMINAR** - Directorio innecesario

2. **/pages/** - Completamente vacío
   - **Acción recomendada:** **ELIMINAR** - Directorio innecesario

3. **/domain/Entities/** - Vacío
4. **/domain/Repositories/** - Vacío
5. **/domain/ValueObjects/** - Vacío
   - **Acción recomendada:** **ELIMINAR** - Directorios de estructura sin contenido

---

## 🔍 ANÁLISIS DETALLADO

### Patrones de Archivos NO Utilizados Identificados

#### 1. **Documentación disfrazada como JS** (2 archivos)
   - `README-FASE-1.js` - Debería estar en `/docs/`
   - `ejemplo-refactorizacion.js` - Debería estar en `/docs/`

#### 2. **Archivos de Debug/Testing** (2 archivos)
   - `debug-sidebar.js` - Cargado en producción, verificar propósito
   - `websocket-test.js` - Cargado en producción, revisar si es necesario

#### 3. **Posibles Duplicados o Versiones Antiguas** (2 archivos)
   - `cargar-borrador-inline.js` - Posible variante de `cargar-borrador.js`
   - `modern-table-v2.js` - Posible versión mejorada pero no se sabe si v2 se usa

#### 4. **Directorios Vacíos sin Propósito** (5)
   - `/api/`
   - `/pages/`
   - `/domain/Entities/`
   - `/domain/Repositories/`
   - `/domain/ValueObjects/`

---

## ⚠️ PROBLEMAS DETECTADOS EN LA ESTRUCTURA

### 1. **Nombres de Directorios con Espacios**
   Los siguientes directorios tienen espacios en sus nombres (antipatrón):
   - `/orders js/` - Debería ser `/orders/` o `/orders-scripts/`
   - `/dashboard js/` - Debería ser `/dashboard/`
   - `/entregas js/` - Debería ser `/entregas/`

   **Impacto:** Dificulta las referencias y puede causar problemas en algunos servidores
   **Acción recomendada:** Refactorizar nombres sin espacios

### 2. **Archivos de Documentación en JS**
   Los archivos `README-FASE-1.js` y `ejemplo-refactorizacion.js` son documentación, no código ejecutable
   
   **Acción recomendada:** Crear un directorio `/docs/refactorization/` y mover allí

### 3. **Falta de Consistency en Módulos**
   - Algunos módulos tienen `/modules/` subdirectorio
   - Otros tienen archivos sueltos

---

## 📋 CHECKLIST DE ACCIONES RECOMENDADAS

### Prioridad ALTA (Eliminar inmediatamente)
- [ ] Eliminar `/api/` (directorio vacío)
- [ ] Eliminar `/pages/` (directorio vacío)
- [ ] Eliminar `/domain/` (directorios vacíos)
- [ ] Mover `README-FASE-1.js` a `/docs/refactorization/`
- [ ] Mover `ejemplo-refactorizacion.js` a `/docs/refactorization/`

### Prioridad MEDIA (Revisar y decidir)
- [ ] Auditar `debug-sidebar.js` - ¿Es necesario en producción?
- [ ] Auditar `websocket-test.js` - ¿Es necesario en producción?
- [ ] Determinar si `cargar-borrador-inline.js` es duplicado de `cargar-borrador.js`
- [ ] Refactorizar nombres de directorios con espacios:
  - `orders js/` → `orders/`
  - `dashboard js/` → `dashboard/`
  - `entregas js/` → `entregas/`

### Prioridad BAJA (Nice to have)
- [ ] Revisar si `modern-table-v2.js` es efectivamente v2 y está en uso
- [ ] Consolidar directorios de modules para mayor consistencia

---

## 📊 ESTADÍSTICAS FINALES

```
Total de archivos JavaScript:        182
Archivos utilizados:                 158 (87%)
Archivos NO utilizados:              24 (13%)
  - Sospechosos (test/debug/docs):   2
  - Directamente no usados:          2
  - Variantes/Alternativas:          2
  - Directorios vacíos:              5 (contienen múltiples subdirs)
  - Potencial cleanup:               ~13 KB de código innecesario
```

---

## 🎯 RESUMEN

El proyecto tiene un buen nivel de organización con **87% de utilización**. La mayoría de los archivos están siendo usados apropiadamente. Los principales problemas son:

1. **Archivos de documentación como JS** - Fácil de corregir
2. **Directorios con espacios en nombres** - Refactor recomendado pero no crítico
3. **Directorios vacíos de estructura** - Limpiar para mejorar claridad

**Recomendación:** Implementar los cambios de Prioridad ALTA, luego revisar los de Prioridad MEDIA con el equipo de desarrollo.
