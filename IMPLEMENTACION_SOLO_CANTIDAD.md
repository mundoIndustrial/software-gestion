# Implementación: Opción "SOLO CANTIDAD" en Agregar Prenda Nueva

## Resumen
Se ha agregado una nueva opción **"SOLO CANTIDAD"** en la sección de tallas y cantidades del modal de agregar prenda nueva. Esta opción permite ingresar una cantidad total de prendas sin especificar género o tallas.

---

## Cambios Realizados

### 1. Frontend - HTML (modal-agregar-prenda-nueva.blade.php)

**Nuevo Botón "SOLO CANTIDAD":**
- Agregado un 4to botón en la fila de géneros: `btn-genero-solo-cantidad`
- Estilo visual consistente con los otros botones
- Ícono de carrito de compras

**Nuevo Formulario de Entrada:**
- Sección `seccion-solo-cantidad` con campo de input para la cantidad
- Botones "Agregar" y "Cancelar"
- Validación de cantidad mínima 1

**Tarjeta de Visualización:**
- Sección `tarjeta-solo-cantidad` que muestra la cantidad agregada
- Texto descriptivo: "Sin especificar talla ni género"
- Botón para eliminar la cantidad

### 2. Frontend - JavaScript

#### En modal-agregar-prenda-nueva.blade.php:
Se agregaron 5 funciones globales:

```javascript
// Abre el campo de entrada para "SOLO CANTIDAD"
window.abrirOpcionalSoloCantidad()

// Agrega la cantidad ingresada
window.agregarSoloCantidad()

// Cancela la entrada
window.cancelarSoloCantidad()

// Elimina la cantidad seleccionada
window.eliminarSoloCantidad()

// Actualiza el total de prendas (incluyendo SOLO CANTIDAD)
window.actualizarTotalPrendas()
```

#### En prenda-editor-modal.js:
Se agregó limpieza de "SOLO CANTIDAD" al cerrar el modal (PASO 5.5):
- Resetea `window.cantidadSoloSeleccionada`
- Oculta y limpia los elementos del DOM
- Recarga el estado inicial

#### En prenda-form-collector.js:
Se agregó lógica para capturar "SOLO CANTIDAD" y agregarlo a `cantidad_talla`:
- Si hay `window.cantidadSoloSeleccionada`, se crea una entrada especial
- Género: **"GENERICO"**
- Talla: **"SIN_ESPECIFICAR"**
- Cantidad: El valor ingresado

#### En gestion-items-pedido.js:
Se modificó la validación en `agregarPrendaNueva()`:
- Ahora acepta **tallas OR solo cantidad** (antes solo tallas)
- Valida que haya al menos una: `tieneTallas || tieneSoloCantidad`
- Agrega `prendaData.cantidad_solo` si está presente

### 3. Base de Datos - Migraciones

**Migración 1: Make genero and talla nullable**
```sql
ALTER TABLE prenda_pedido_tallas 
  MODIFY column genero VARCHAR(50) NULL,
  MODIFY column talla VARCHAR(50) NULL,
  MODIFY column tipo_talla VARCHAR(10) NULL,
  MODIFY column es_sobremedida TINYINT NULL;
```

**Migración 2: Add GENERICO to enum**
Agrega "GENERICO" a la lista de valores permitidos del campo `genero`.

---

## Flujo de Uso

### Usuarios finales:
1. Hacen clic en el botón **"SOLO CANTIDAD"** (4to botón)
2. Se abre una sección con un campo `<input type="number">`
3. Ingresan la cantidad total
4. Hacen clic en "Agregar"
5. Se muestra una tarjeta con la cantidad ingresada
6. El total de prendas se actualiza automáticamente
7. Pueden hacer clic en X en la tarjeta para eliminar la cantidad

### Detalles técnicos:
1. El frontend guarda la cantidad en `window.cantidadSoloSeleccionada`
2. Al guardar la prenda, se agrega a `cantidad_talla` como: `{ "GENERICO": { "SIN_ESPECIFICAR": <cantidad> } }`
3. Se envía al backend como parte de `prendaData`
4. El backend recibe la estructura y la procesa como cualquier otra talla
5. Se guardan en `prenda_pedido_tallas` con `genero='GENERICO'` y `talla='SIN_ESPECIFICAR'`

---

## Restricciones

- ✅ El usuario **PUEDE** combinar géneros normales CON "SOLO CANTIDAD"
- ❌ El usuario **NO PUEDE** combinar "SOLO CANTIDAD" con otros géneros en la misma sesión (se deseleccionan al seleccionar "SOLO CANTIDAD")
- ✅ Si desea cambiar de opción, puede desabilitar "SOLO CANTIDAD" y seleccionar géneros normales

---

## Base de Datos - Consultas útiles

**Ver todas las prendas con "SOLO CANTIDAD":**
```sql
SELECT * FROM prenda_pedido_tallas 
WHERE genero = 'GENERICO' AND talla = 'SIN_ESPECIFICAR';
```

**Contar cantidad total de prendas en pedido (incluyendo GENERICO):**
```sql
SELECT prenda_pedido_id, SUM(cantidad) as total
FROM prenda_pedido_tallas
GROUP BY prenda_pedido_id;
```

---

## Testing

### Manual Testing Checklist:
- [ ] ✅ Clic en botón "SOLO CANTIDAD" muestra el formulario
- [ ] ✅ Ingresar cantidad > 0 y hacer clic "Agregar" crea la tarjeta
- [ ] ✅ El total de prendas se actualiza correctamente
- [ ] ✅ Hacer clic en X elimina la tarjeta y resetea la cantidad
- [ ] ✅ El botón se desactiva al cambiar a otro género
- [ ] ✅ Cambiar a otro género oculta el formulario de "SOLO CANTIDAD"
- [ ] ✅ Guardar prenda con "SOLO CANTIDAD" crea el registro en BD
- [ ] ✅ Consultar BD muestra `genero='GENERICO'` y `talla='SIN_ESPECIFICAR'`

---

## Archivos Modificados

```
📁 resources/views/asesores/pedidos/modals/
  ├─ modal-agregar-prenda-nueva.blade.php          ✏️ Agregar HTML y funciones JS

📁 public/js/componentes/
  ├─ prenda-editor-modal.js                        ✏️ Agregar limpieza PASO 5.5
  ├─ prenda-form-collector.js                      ✏️ Agregar captura de cantidad_solo
  
📁 public/js/modulos/crear-pedido/procesos/
  ├─ gestion-items-pedido.js                       ✏️ Modificar validación

📁 database/migrations/
  ├─ 2026_02_25_000000_make_genero_talla_nullable_prenda_pedido_tallas.php  ✨ NUEVO
  ├─ 2026_02_25_000001_add_generico_to_genero_enum.php                      ✨ NUEVO
```

---

## Próximos Pasos

1. **Ejecutar las migraciones:**
```bash
php artisan migrate
```

2. **Probar en local:**
```bash
php artisan serve
# Ir a: http://localhost:8000/asesores/pedidos-editable/crear-nuevo
```

3. **Verificar en BD:**
```bash
SELECT * FROM prenda_pedido_tallas WHERE genero IS NULL OR talla IS NULL;
```

4. **Documentar en el sistema:**
   - Agregar nota sobre esta nueva opción en la capacitación de asesores
   - Documentar en el manual de usuario

---

## Notas de Desarrollo

- La variable global `window.cantidadSoloSeleccionada` almacena la cantidad temporalmente
- Se resetea automáticamente al cerrar el modal
- El `actualizarTotalPrendas()` fue reescrito para incluir GENERICO en el total
- No hay cambios en el backend (DTO, servicios, etc.) porque usa la estructura existente de `cantidad_talla`
