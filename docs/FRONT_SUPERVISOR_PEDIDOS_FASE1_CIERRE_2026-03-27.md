# Frontend Supervisor Pedidos - Cierre Fase 1

Fecha de cierre: 2026-03-27
Alcance: modulo `supervisor-pedidos` (Blade + Vite + JS vainilla)

## Resultado

Fase 1 completada con reduccion fuerte de acoplamiento en la vista principal y limpieza casi total de inline scripts en layout.

## Metricas (antes vs despues)

Fuente: `scripts/auditar-front-supervisor.ps1`

- `index_script_tags`: 61 -> 22
- `layout_script_tags`: 15 -> 14
- `index_inline_script_blocks`: 0 -> 0
- `layout_inline_script_blocks`: 6 -> 1
- `index_asesores_script_refs`: 7 -> 0
- `index_asesores_blade_includes`: 12 -> 0
- `index_ordersjs_refs`: 12 -> 0
- `index_crear_pedido_refs`: 12 -> 0

## Entregables principales

- Entrypoint Vite del modulo:
  - `resources/js/supervisor-pedidos/entry.js`
- Auditoria estructural:
  - `scripts/auditar-front-supervisor.ps1`
- Limpieza progresiva del index:
  - `resources/views/supervisor-pedidos/index.blade.php`
- Extraccion de inline scripts del layout:
  - `public/js/supervisor-pedidos/layout/jquery-bootstrap-loader.js`
  - `public/js/supervisor-pedidos/layout/echo-ready.js`
  - `public/js/supervisor-pedidos/layout/nav-protector.js`
  - `public/js/supervisor-pedidos/layout/notifications-and-filters.js`

## Estado final de arquitectura (Fase 1)

- `index` ya no referencia directamente:
  - scripts de `asesores`
  - bloque `ordersjs/tracking`
  - bloque `modulos/crear-pedido`
- `layout` mantiene un solo inline:
  - bootstrap de `window.usuarioAutenticado` (esperado por datos server-side).

## Continuacion aplicada (2026-03-27)

Se agrego una capa `shared` para desacoplar a `supervisor-pedidos` del namespace `asesores` en Blade.

- Nuevo puente compartido:
  - `resources/views/shared/pedidos/modales-edicion-compartidos.blade.php`
- Wrapper de supervisor actualizado:
  - `resources/views/supervisor-pedidos/partials/modales-edicion-compartidos.blade.php`
- Auditoria extendida:
  - nueva metrica `supervisor_asesores_blade_includes_recursive`
  - nueva metrica `shared_pedidos_bridge_includes`

Resultado de auditoria luego del cambio inicial:

- `supervisor_asesores_blade_includes_recursive`: 0
- `shared_pedidos_bridge_includes`: 12

Actualizacion de avance (misma fecha):

- Se clonaron 9 modales pequenos/medianos a `shared.pedidos.*`.
- `shared_pedidos_bridge_includes` bajo de `12` a `3`.
- Dependencias restantes (modales grandes):
  - `asesores.pedidos.modals.modal-agregar-prenda-nueva`
  - `asesores.pedidos.modals.modal-agregar-editar-epp`
  - `asesores.pedidos.components.modal-editar-epp`

Actualizacion final (misma fecha):

- Se migraron los 3 modales grandes restantes a `shared.pedidos.*`.
- Se migro tambien dependencia interna:
  - `shared.pedidos.modals.modal-asignar-colores-por-talla`
- Auditoria final:
  - `supervisor_asesores_blade_includes_recursive`: `0`
  - `shared_pedidos_bridge_includes`: `0`

## Checklist de mantenimiento (obligatorio en PR)

1. Ejecutar auditoria:
   - `powershell -ExecutionPolicy Bypass -File .\scripts\auditar-front-supervisor.ps1`
2. Verificar que no suban estas metricas:
   - `index_asesores_script_refs`
   - `index_asesores_blade_includes`
   - `index_ordersjs_refs`
   - `index_crear_pedido_refs`
3. No agregar nuevos bloques `<script>...</script>` inline en `layout`.
4. Si se agrega funcionalidad de supervisor:
   - primero en `resources/js/supervisor-pedidos/entry.js`
   - luego en archivos del modulo `public/js/supervisor-pedidos/*` o `public/js/shared/*`.
5. Evitar dependencias cruzadas directas a `asesores` desde vistas de supervisor.

## Regla de oro para proximas fases

Todo nuevo comportamiento de `supervisor-pedidos` debe entrar por entrypoint del modulo.
Si algo es compartido entre modulos, moverlo a `shared` en lugar de consumir otro modulo directamente.
