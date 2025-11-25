# Reporte: Campos `numero_cotizacion` y `numero_pedido` en Tablas HTML

## Resumen
Se encontraron **2 vistas Blade** que contienen estos campos dentro de etiquetas `<td>` en tablas HTML.

---

## 1. `numero_cotizacion` en Tablas

### 📄 Archivo: `resources/views/asesores/cotizaciones/index.blade.php`

#### Ubicación en tabla "Cotizaciones Enviadas"
- **Línea exacta:** 264
- **Contexto:** Tabla HTML con encabezados (Fecha, Código, Cliente, Estado, Acción)
- **Contenido HTML:**
  ```blade
  <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
  ```

**Características:**
- ✅ Campo mostrado dentro de `<td>`
- ✅ Tiene fallback: `'Por asignar'` si no existe
- ✅ Formateado con color azul (`#1e40af`)
- ❌ **No hay condicionales por rol** (se muestra siempre)
- ⚠️ **Contexto de tabla:**
  - Fila 263: `<td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y') }}</td>` (Fecha)
  - Fila 264: `<td>...numero_cotizacion...</td>` ✅ (Código)
  - Fila 265: `<td style="padding: 12px; color: #333; font-size: 0.9rem; font-weight: 500;">{{ $cot->cliente ?? 'Sin cliente' }}</td>` (Cliente)

---

## 2. `numero_pedido` en Tablas

### 📄 Archivo: `resources/views/asesores/pedidos/index.blade.php`

#### Ubicación 1: Atributo `data-order-id` en `<tr>`
- **Línea exacta:** 560
- **Contexto:** Atributo HTML de fila de tabla
- **Contenido HTML:**
  ```blade
  <tr class="table-row" data-order-id="{{ $pedido->numero_pedido }}">
  ```

**Características:**
- ✅ Dentro de estructura de tabla (`<tr>`)
- ⚠️ No es un `<td>` directo, sino un atributo de fila
- ❌ No hay fallback
- ❌ No hay condicionales por rol

---

#### Ubicación 2: Dentro de botón en celda de acciones
- **Línea exacta:** 563 (primera llamada)
- **Contexto:** Dentro de `<td>` de acciones, pero NO es el campo visible
- **Contenido HTML:**
  ```blade
  <button class="action-btn detail-btn" onclick="verFactura({{ $pedido->numero_pedido }})"...>
  ```

**Características:**
- ✅ Dentro de `<td>`
- ⚠️ Es un parámetro de JavaScript, no un campo visible en tabla
- ❌ No hay condicionales por rol

---

#### Ubicación 3: Dentro de botón en celda de acciones (Seguimiento)
- **Línea exacta:** 590
- **Contexto:** Similar a ubicación 2
- **Contenido HTML:**
  ```blade
  <button class="action-btn detail-btn" onclick="verSeguimiento({{ $pedido->numero_pedido }})"...>
  ```

**Características:**
- ✅ Dentro de `<td>`
- ⚠️ Es un parámetro de JavaScript, no un campo visible
- ❌ No hay condicionales por rol

---

#### Ubicación 4: Campo visible en tabla (COLUMNA DE NÚMERO)
- **Línea exacta:** 650
- **Contexto:** Dentro de `<td>`, es el campo número del pedido visible en tabla
- **Contenido HTML:**
  ```blade
  <span style="color: var(--primary-color); font-weight: 700; font-size: 13px;">#{{ $pedido->numero_pedido }}</span>
  ```

**Características:**
- ✅ Dentro de `<td>` con contenido visible
- ✅ Formateado con peso 700 y tamaño 13px
- ✅ Color variable `--primary-color`
- ❌ No hay fallback
- ❌ **No hay condicionales por rol** (se muestra siempre)
- ⚠️ **Contexto de tabla (filas anteriores en la misma fila):**
  - Fila 562: Botones de acciones (Ver Factura, Ver Seguimiento)
  - Fila 600+: Estado del pedido
  - Fila 610+: Proceso actual
  - Fila 620+: Día de entrega
  - Fila 650: **`#{{ $pedido->numero_pedido }}`** ✅ (Número del Pedido - VISIBLE)

---

## 3. Otras Ubicaciones (NO en tablas HTML)

### 📄 Archivo: `resources/views/asesores/cotizaciones/show.blade.php`

- **Línea 406-407:** Dentro de condicional en header (NO es tabla)
  ```blade
  @if($cotizacion->numero_cotizacion)
      Cotización: {{ $cotizacion->numero_cotizacion }}
  ```

---

### 📄 Archivo: `resources/views/asesores/pedidos/crear-desde-cotizacion.blade.php`

- **Línea 507:** Campo `input` de formulario (NO es tabla)
- **Línea 527:** Campo `input` de formulario (NO es tabla)

---

### 📄 Archivo: `resources/views/asesores/pedidos/plantilla-erp.blade.php`

- **Línea 289:** Dentro de div de titulo (NO es tabla HTML)
  ```blade
  <div class="numero-pedido">Nº {{ $pedido->numero_pedido }}</div>
  ```

- **Línea 290-292:** Condicional en header (NO es tabla)
  ```blade
  @if($pedido->numero_cotizacion)
      <div>Cotización: {{ $pedido->numero_cotizacion }}</div>
  @endif
  ```

---

## Resumen de Hallazgos

| Campo | Vista | Línea | En `<td>` | Visible | Rol | Fallback |
|-------|-------|-------|-----------|---------|-----|----------|
| `numero_cotizacion` | `index.blade.php` (cotizaciones) | 264 | ✅ | ✅ | ❌ | 'Por asignar' |
| `numero_pedido` | `index.blade.php` (pedidos) | 560 | ⚠️ (atributo) | ❌ | ❌ | ❌ |
| `numero_pedido` | `index.blade.php` (pedidos) | 563 | ⚠️ (script) | ❌ | ❌ | ❌ |
| `numero_pedido` | `index.blade.php` (pedidos) | 590 | ⚠️ (script) | ❌ | ❌ | ❌ |
| `numero_pedido` | `index.blade.php` (pedidos) | 650 | ✅ | ✅ | ❌ | ❌ |

---

## Conclusiones

1. **Campos en tablas HTML visibles:**
   - `numero_cotizacion` (línea 264 en cotizaciones/index.blade.php)
   - `numero_pedido` (línea 650 en pedidos/index.blade.php)

2. **Condicionales por rol:** ❌ **NINGUNO ENCONTRADO**
   - Ambos campos se muestran sin restricciones de rol o permiso
   - No hay `@can` o `@role` directamente en estas columnas

3. **Campos con fallback:**
   - `numero_cotizacion`: Sí ('Por asignar')
   - `numero_pedido`: No (nulo si no existe)

4. **Usos secundarios encontrados:**
   - Parámetros en funciones JavaScript
   - Campos en formularios (no tablas)
   - Headers y títulos de documentos
