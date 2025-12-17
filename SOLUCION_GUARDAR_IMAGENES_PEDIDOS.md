# RESUMEN: Correcciones para Guardar Imágenes en Pedidos

## ✅ CAMBIOS REALIZADOS

### 1. Modelos Creados

#### `PrendaFotoPedido.php`
- **Tabla:** `prenda_fotos_pedido`
- **Función:** Guardar fotos de prendas en pedidos
- **Campos:** `prenda_pedido_id`, `ruta_original`, `ruta_webp`, `ruta_miniatura`, `orden`, `ancho`, `alto`, `tamaño`

#### `PrendaFotoLogoPedido.php`
- **Tabla:** `prenda_fotos_logo_pedido`
- **Función:** Guardar fotos de logos en pedidos
- **Campos:** `prenda_pedido_id`, `ruta_original`, `ruta_webp`, `ruta_miniatura`, `orden`, `ancho`, `alto`, `tamaño`, `ubicacion`

### 2. Servicio Actualizado

#### `CopiarImagenesCotizacionAPedidoService.php`

**Cambios:**

1. **Activó copiar fotos de prendas** (antes estaba comentado)
   - Copia desde `prenda_fotos_cot` 
   - Guarda en `prenda_fotos_pedido`

2. **Ya estaba activo: copiar fotos de telas**
   - Copia desde `prenda_tela_fotos_cot`
   - Guarda en `prenda_fotos_tela_pedido`

3. **Agregó copiar fotos de logos** (NUEVO)
   - Obtiene logos de la cotización desde `logo_cotizaciones`
   - Copia las fotos desde `logo_fotos_cot`
   - Guarda en `prenda_fotos_logo_pedido`
   - Se copia una sola vez (para la primera prenda)

### 3. Flujo Actualizado

Cuando se crea un pedido desde una cotización, el `CrearPedidoProduccionJob` llama a:

```
CopiarImagenesCotizacionAPedidoService::copiarImagenesCotizacionAPedido()
  ├─ copiarFotosPrenda()    → prenda_fotos_pedido ✅
  ├─ copiarFotosTela()       → prenda_fotos_tela_pedido ✅
  └─ copiarLogos()           → prenda_fotos_logo_pedido ✅
```

## 📊 TABLAS DE DESTINO (donde se guardan las imágenes)

| Tabla | Registros | Estado |
|-------|-----------|--------|
| `prenda_fotos_pedido` | ✅ Se inserta | Fotos de prendas |
| `prenda_fotos_tela_pedido` | ✅ Se inserta | Fotos de telas |
| `prenda_fotos_logo_pedido` | ✅ Se inserta | Fotos de logos |

## 🔧 SIN CAMBIOS

- No se modificó `prendas_pedido`
- No se borró nada de la BD
- Solo se agregó código para guardar imágenes
- Las tablas ya existían, solo se poblaron correctamente

## 🚀 PRÓXIMO PASO

Las imágenes ahora se copian automáticamente cuando se convierte una cotización aprobada a pedido de producción.
