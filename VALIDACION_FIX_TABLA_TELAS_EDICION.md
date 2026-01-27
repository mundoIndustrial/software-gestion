# Guía de Validación - Fix Tabla de Telas en Edición

## 🧪 Pasos para Validar la Solución

### 1. Abrir Modal de Edición de Prenda

```
1. Ir a Pedidos → Editar Pedido Producción
2. Buscar una prenda con telas (ej: Pedido 2763, Prenda "CAMISA DRILL")
3. Hacer clic en el botón EDITAR de la prenda
4. Se abrirá el modal de edición
```

### 2. Verificar que se Muestre la Tabla de Telas

**Debe verse:**
- ✅ Tabla con columnas: **TELA | COLOR | REFERENCIA | FOTO | ACCIONES**
- ✅ Una fila para cada tela guardada en la BD
- ✅ Los valores correctos en cada celda:
  - **TELA**: Nombre de la tela (ej: "drill")
  - **COLOR**: Nombre del color (ej: "dsfdfs")
  - **REFERENCIA**: Referencia (ej: código interno)
  - **FOTO**: Thumbnail de la imagen de la tela
  - **ACCIONES**: Botón rojo de eliminar

### 3. Verificar la Consola de Browser

Abrir DevTools (F12) → Console, debe verse logs como:

```javascript
[actualizarTablaTelas] 🔄 Iniciando actualización de tabla...
[actualizarTablaTelas] 📋 Modo: EDICIÓN, Telas a mostrar: 1
[actualizarTablaTelas] 🧵 Procesando tela 0: {
    nombre: "drill",
    color: "dsfdfs", 
    referencia: "",
    imagenes_count: 1
}
[actualizarTablaTelas] 📸 Primera imagen de tela 0: {previewUrl: "..."}
[actualizarTablaTelas] 📋 Caso previewUrl: /storage/pedidos/...
```

### 4. Probar Funcionalidad de Eliminación

```javascript
1. Hacer clic en botón de eliminar (X rojo) de una tela
2. Confirmar en el modal de confirmación
3. La tela debe desaparecer de la tabla
4. Si guarda cambios, debería enviarse la actualización al servidor
```

### 5. Probar Agregar Nueva Tela

```javascript
1. En la primera fila de la tabla, completar:
   - TELA: (seleccionar de dropdown)
   - COLOR: (escribir)
   - REFERENCIA: (escribir)
   - FOTO: (subir imagen)
2. Hacer clic en "Agregar Tela"
3. Nueva tela aparece en la tabla
4. Verificar que la foto se muestre en la tabla
```

## 📊 Casos de Uso

### ✅ Caso 1: Edición con Telas Existentes
- Prenda tiene telas guardadas en BD
- Modal abre mostrando tabla con todas las telas
- Tabla incluye: nombre, color, referencia, foto

### ✅ Caso 2: Edición sin Telas
- Prenda sin telas en BD
- Tabla aparece vacía (solo fila de inputs)
- Usuario puede agregar nuevas telas

### ✅ Caso 3: Prenda Nueva
- Crear prenda nueva
- Tabla vacía, agregar telas desde cero
- Debe funcionar como antes (no hay regresiones)

### ✅ Caso 4: Mezcla (Editar + Agregar)
- Editar prenda con telas existentes
- Agregar tela nueva
- Debe mostrar ambas y guardar correctamente

## 🔍 Debug - Si no funciona

### Problema: Tabla vacía pero logs dicen "1 tela a mostrar"

**Solución:**
1. Abrir DevTools → Elements
2. Buscar elemento `<tbody id="tbody-telas">`
3. Verificar que tenga `<tr>` con contenido
4. Si no tiene `<tr>`, revisar que `actualizarTablaTelas()` se ejecute

### Problema: "Tela (Sin nombre)" / "Sin color"

**Causa:** Las propiedades de la tela no coinciden con lo esperado

**Solución:**
1. En console, ejecutar:
```javascript
console.log('telasAgregadas:', window.telasAgregadas);
console.log('telasEdicion:', window.telasEdicion);
console.log('telasCreacion:', window.telasCreacion);
```
2. Verificar estructura de cada objeto tela
3. Revisar contra la lógica de normalización

### Problema: Fotos no aparecen

**Causa:** Estructura de URL de imagen incorrecta

**Solución:**
1. En console:
```javascript
window.telasAgregadas[0].imagenes[0]  // Ver estructura
```
2. Debe tener uno de estos campos:
   - `previewUrl` (prioritario)
   - `url`
   - `ruta_webp`
   - `ruta_original`

## ✅ Validación Final

Cuando todo funcione correctamente, verificar que:

```
✅ Tabla muestra telas de BD en edición
✅ Propiedades se normalizan correctamente
✅ Fotos se muestran como thumbnails
✅ Botón eliminar funciona
✅ Agregar tela nueva funciona
✅ Sin regresiones en creación de prendas nuevas
✅ Console sin errores
✅ Guardado envía datos correctamente
```

## 📝 Logs Esperados en Edición de Prenda 3475

```
[actualizarTablaTelas] 🔄 Iniciando actualización de tabla...
[actualizarTablaTelas] 📋 Modo: EDICIÓN, Telas a mostrar: 1
[actualizarTablaTelas] 🧵 Procesando tela 0: {nombre: "drill", color: "dsfdfs", referencia: "", imagenes_count: 1}
[actualizarTablaTelas] 📸 Primera imagen de tela 0: {previewUrl: "..."}
[actualizarTablaTelas] 🔍 Estructura de imagen 0: {...}
[actualizarTablaTelas] 📋 Caso previewUrl: /storage/pedidos/2763/tela/...
[actualizarTablaTelas] ✅ blobUrl para imagen 0: /storage/pedidos/2763/tela/...
```

---

**Archivo modificado:** `public/js/modulos/crear-pedido/telas/gestion-telas.js`  
**Funciones afectadas:**
- `window.actualizarTablaTelas()` - Renderiza tabla de telas
- `window.eliminarTela(index)` - Elimina tela de la tabla

**Compatibilidad:** Soporta ambos modos (Creación y Edición)
