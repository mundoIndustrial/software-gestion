# Migracion API Asesores - Estado Actual

Fecha de actualizacion: 2026-03-30
Proyecto: `mundoindustrial`

## Resumen ejecutivo

La migracion de `asesores` hacia enfoque API-first avanza bien.

Estado actual:
- Endpoints de lectura/escritura clave de `asesores` ya estan publicados en `/api/asesores/*`.
- Varias rutas JSON legacy en `routes/asesores.php` ya se retiraron.
- Frontend principal de supervisor/asesores (pendientes, notificaciones, observaciones, catalogos, prendas/epp) ya consume rutas API.
- Se mantienen rutas web para vistas Blade y navegacion del modulo.

## Lo que ya quedo migrado

### 1. Autenticacion API (base ya operativa)
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `GET /api/v1/auth/csrf`

### 2. Endpoints API canónicos de asesores

Ya activos en `routes/api-asesores.php`:

- Dashboard/pendientes:
  - `GET /api/asesores/dashboard-data`
  - `GET /api/asesores/pendientes-asesor`
  - `GET /api/asesores/conteo-pendientes-asesor`
  - `GET /api/asesores/pendientes/{id}/notas`
  - `GET /api/asesores/pedidos/next-pedido`
  - `GET /api/asesores/pedidos/listar`
  - `GET /api/asesores/pedidos-api-listar` (compatibilidad)
  - `POST /api/asesores/pedidos/crear`
  - `POST /api/asesores/pedidos/validar`
  - `POST /api/asesores/pedidos/borrador`
  - `PUT|POST /api/asesores/pedidos/{pedidoId}/borrador`
  - `PUT|POST /api/asesores/pedidos/{pedidoId}/actualizar` (legacy compat)
  - `PUT /api/asesores/pedidos/{id}`
  - `DELETE /api/asesores/pedidos/borradores/{id}`
  - `POST /api/asesores/pedidos/{id}/confirmar-correccion`

- Cotizaciones:
  - `GET /api/asesores/cotizaciones/filtros/valores`
  - `POST /api/asesores/cotizaciones`
  - `GET /api/asesores/cotizaciones/{id}`
  - `PUT /api/asesores/cotizaciones/{id}`
  - `DELETE /api/asesores/cotizaciones/{id}`
  - `DELETE /api/asesores/cotizaciones/{id}/borrador`
  - `POST /api/asesores/cotizaciones/{id}/anular`
  - `POST /api/asesores/cotizaciones/{id}/imagenes`
  - `DELETE /api/asesores/cotizaciones/{id}/imagenes`
  - `DELETE /api/asesores/cotizaciones/imagenes/prenda/{id}`
  - `DELETE /api/asesores/cotizaciones/imagenes/tela/{id}`
  - `DELETE /api/asesores/cotizaciones/imagenes/logo/{id}`
  - `POST /api/asesores/fotos/eliminar` (compatibilidad)
  - `POST /api/asesores/cotizaciones-epp`

- Notificaciones:
  - `GET /api/asesores/notificaciones`
  - `POST /api/asesores/notificaciones/marcar-todas-leidas`
  - `POST /api/asesores/notificaciones/{notificationId}/marcar-leida`

- Observaciones de despacho:
  - `POST /api/asesores/pedidos/observaciones-despacho/resumen`
  - `GET /api/asesores/pedidos/{id}/observaciones-despacho`
  - `POST /api/asesores/pedidos/{id}/observaciones-despacho`
  - `PUT /api/asesores/pedidos/{id}/observaciones-despacho/{observacionId}`
  - `DELETE /api/asesores/pedidos/{id}/observaciones-despacho/{observacionId}`
  - `POST /api/asesores/pedidos/{id}/observaciones-despacho/marcar-leidas`
  - `POST /api/asesores/pedidos/{id}/observaciones-despacho/marcar-bodega-vistas`

- Catalogos:
  - `GET /api/asesores/telas`
  - `GET /api/asesores/colores`
  - `GET /api/asesores/prendas/autocomplete`

- Edicion de pedidos/prendas/epp:
  - `GET /api/asesores/pedidos-produccion`
  - `GET /api/asesores/pedidos-produccion/obtener-datos-cotizacion/{cotizacionId}`
  - `GET /api/asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}`
  - `GET /api/asesores/pedidos-produccion/{id}`
  - `PUT /api/asesores/pedidos-produccion/{pedidoId}/prendas/{prendaId}` (endpoint puente legacy del modal simple)
  - `GET /api/asesores/pedidos/{id}/editar-datos`
  - `GET /api/asesores/pedidos/{id}/factura-datos`
  - `GET /api/asesores/pedidos/{id}/recibos-datos`
  - `GET /api/asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos`
  - `GET /api/asesores/pedidos-produccion/{pedidoId}/datos-edicion`
  - `DELETE /api/asesores/pedidos-produccion/{id}`
  - `DELETE /api/asesores/pedidos/{id}`
  - `POST /api/asesores/pedidos/{pedidoId}/agregar-prenda-simple`
  - `POST /api/asesores/pedidos/{id}/agregar-prenda`
  - `POST /api/asesores/pedidos/{id}/actualizar-prenda`
  - `POST /api/asesores/pedidos/{id}/eliminar-prenda`
  - `POST /api/asesores/pedidos/{id}/eliminar-epp`
  - `POST /api/asesores/pedidos/{id}/homologar-epp`
  - `PUT /api/asesores/pedidos/{pedidoId}/prendas/{prendaId}/variante`

- Realtime polling endpoint:
  - `GET /api/asesores/realtime/pedidos`

### 3. Limpieza de legacy (ya hecho)

Se retiraron rutas JSON duplicadas de `routes/asesores.php` que ahora viven en API:
- `dashboard-data`
- `notifications/*` (asesores)
- `pendientes/{id}/notas`
- `pedidos/next-pedido`
- `pedidos-api-listar`
- bloque de `observaciones-despacho`
- `realtime/pedidos`
- varios endpoints de edicion/prendas/epp/recibos
- comandos de `pedidos` (crear/validar/guardar borrador/actualizar/anular) en `routes/asesores.php` ahora se consumen por `/api/asesores/*`

Nota:
- `routes/asesores.php` sigue siendo valido para vistas Blade y navegacion web (esto es esperado en estrategia de migracion gradual con Blade + Vite).

## Pruebas y validacion

Validaciones ejecutadas:
- Sintaxis de rutas (`php -l`) en archivos modificados.
- Verificacion de rutas publicadas con `php artisan route:list --path=api/asesores`.
- Test feature:
  - `tests/Feature/Http/Controllers/Api/AsesoresApiControllerTest.php` -> PASS.

## Estado funcional actual

Con lo ya migrado:
- Login API funciona.
- Flujo de notificaciones de asesores se consume por API.
- Pendientes y conteos de asesores se consumen por API.
- Observaciones despacho (CRUD + badges + marcas) se consume por API.
- Catalogos de telas/colores/autocomplete se consumen por API.
- Gran parte de la edicion de prendas/EPP en pedidos apunta a API.
- Consulta de factura de pedido (`factura-datos`) para vistas de asesores apunta a API.
- Flujos de cotizaciones (filtros, anular, borrar borrador, subir/eliminar imagenes) apuntan a API.

## Pendientes recomendados (siguiente fase)

1. Cerrar bloque `pedidos-produccion/*` residual en otros modulos JS
- Hay consumidores legacy todavia en archivos antiguos que apuntan a `/asesores/pedidos-produccion/...` fuera del flujo ya tocado.
2. Migrar endpoints web legacy de pedido/logo que aun no tienen equivalente API en `api-asesores`
- `/asesores/pedidos/guardar-logo-pedido`
- `/asesores/pedidos-logo/areas/disponibles`
- `/asesores/pedidos-logo/{logoPedidoId}/cambiar-area`
- `/asesores/pedidos-logo/{logoPedidoId}/historial`
- `/asesores/pedidos/cotizaciones/{cotId}/epp-items`

2. Estandarizar nombres y metodos HTTP
- Mantener consistencia REST (por ejemplo, revisar where aplica `POST` vs `PATCH/PUT/DELETE`).

3. Documentar contrato de errores API
- Unificar estructura de errores (`success`, `message`, `errors`, `error_code`) para frontend.

4. Coleccion Postman oficial del modulo asesores
- Dejar coleccion por carpetas:
  - auth
  - pendientes
  - notificaciones
  - observaciones
  - prendas/epp
  - catalogos

5. Plan de deprecacion final
- Marcar y eliminar definitivamente rutas web JSON restantes una vez no tengan consumidores.

## Criterio de “migracion completa” para asesores

Se considerara completo cuando:
- Todo consumo de datos del frontend de asesores use `/api/asesores/*`.
- `routes/asesores.php` solo contenga rutas de vistas/render.
- No existan endpoints JSON duplicados entre web y api.
- Se tenga cobertura de test minima para endpoints criticos de lectura/escritura.

## Archivos clave de referencia

- `routes/api-asesores.php`
- `routes/asesores.php`
- `public/js/asesores/notifications.js`
- `public/js/asesores/observaciones-despacho.js`
- `resources/views/asesores/pedidos/pendientes.blade.php`
- `resources/views/asesores/pedidos/pendientes-detalle.blade.php`
- `tests/Feature/Http/Controllers/Api/AsesoresApiControllerTest.php`
