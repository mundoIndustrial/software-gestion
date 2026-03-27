# Performance Frontend - Supervisor Pedidos

Fecha: 2026-03-27
Modulo: `supervisor-pedidos`

## Alcance de medicion

Se midio el costo de carga desde las vistas:

- `resources/views/supervisor-pedidos/layout.blade.php`
- `resources/views/supervisor-pedidos/index.blade.php`

## Hallazgos principales

- Total de archivos locales referenciados (CSS + JS): `44`
  - JS: `32`
  - CSS: `12`
- Scripts CDN detectados: `3`
  - `chart.js`
  - `sweetalert2`
  - `jquery`
- Peso local total aproximado: `737112 bytes` (~`719.84 KB`)
  - JS: `614939 bytes` (~`600.53 KB`)
  - CSS: `122173 bytes` (~`119.31 KB`)

Top pesos (local):

1. `public/js/componentes/prenda-editor-pedidos-adapter.js` (~73.48 KB)
2. `public/js/contador/cotizacion.js` (~71.20 KB)
3. `public/js/modulos/invoice/InvoiceRenderer.js` (~62.20 KB)
4. `public/js/componentes/prenda-form-collector.js` (~47.38 KB)
5. `public/js/bundles/shared-core.js` (~33.25 KB)

## Problemas detectados

1. Se usaba `?v={{ time() }}` en assets de `index`, invalidando cache en cada request.
2. Varias cargas JS no tenian `defer`, bloqueando parse/render sin necesidad.
3. No fue posible correr Lighthouse en esta terminal porque `npm/node` no estan en `PATH` del entorno actual.

## Optimizaciones aplicadas

Archivo modificado:

- `resources/views/supervisor-pedidos/index.blade.php`
- `resources/js/bootstrap.js`

Cambios:

1. Reemplazo de `?v={{ time() }}` por versionado por archivo con `filemtime(public_path(...))` en:
   - `css/tracking-modal.css`
   - `js/supervisor-pedidos/index.js`
   - `js/modulos/invoice/*` (6 archivos)
2. Se agrego `defer` a scripts locales no criticos de `index`.
3. Carga diferida real de realtime:
   - `pusher-js` y `laravel-echo` ya no se importan de forma estatica.
   - ahora se cargan por `import()` dentro de `initializeEcho()`.
   - reduce costo del bundle inicial y mueve dependencias de realtime a carga bajo demanda.

Resultado estructural en `index`:

- `22` tags `<script>` totales
- `21` con `defer`
- `1` `type="module"` (loader de recibos)

## Riesgo residual y siguiente validacion

- Riesgo residual bajo: los scripts quedaron en el mismo orden logico, solo con carga diferida.
- Falta validacion con navegador real (Lighthouse/DevTools) para medir:
  - LCP
  - TBT
  - INP
  - waterfall de red real con cache frio/caliente
