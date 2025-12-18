# 🧪 GUÍA DE TESTING - Correcciones de Pedidos REFLECTIVO

## 📍 URL A PROBAR
```
http://servermi:8000/asesores/pedidos-produccion/crear
```

---

## 🔧 PASO 1: PREPARACIÓN

1. **Abre la URL en el navegador**
2. **Abre Developer Tools:** `F12` o `Ctrl+Shift+I`
3. **Pestaña Console:** Para ver los logs de depuración

---

## ✅ TEST 1: VERIFICAR QUE EXISTA COTIZACIÓN REFLECTIVO

### Pasos:
1. En la búsqueda de cotización, escribe "REFLECTIVO" o similar
2. Debería aparecer una cotización de tipo REFLECTIVO
3. Si no hay, necesitas crear una para testing

### Resultado Esperado:
```
✅ Se muestra cotización REFLECTIVO en el dropdown
```

---

## ✅ TEST 2: ELIMINAR TALLA (REFLECTIVO)

### Pasos:
1. Selecciona la cotización REFLECTIVO
2. Desplázate a la sección de **Tallas y Cantidades**
3. Deberías ver un grid con tallas (XS, S, M, L, XL, etc.)
4. Cada talla tiene un botón rojo "×" en la esquina superior derecha
5. **Haz click en "×" de una talla (ej: "M")**

### Resultado Esperado ANTES DE CORRECCIÓN:
```
❌ Nada ocurre (función no existe)
❌ En consola: Error indefinido
```

### Resultado Esperado DESPUÉS DE CORRECCIÓN:
```
✅ Aparece popup de SweetAlert
   - Título: "Eliminar talla"
   - Mensaje: "¿Estás seguro de que quieres eliminar la talla M? No se incluirá en el pedido."
   - Botones: "Sí, eliminar" y "Cancelar"
```

### Si haces click en "Sí, eliminar":
```
✅ La talla desaparece del grid
✅ Aparece notificación de éxito: "La talla M no se incluirá en el pedido"
✅ En CONSOLA aparece: "✅ Talla M eliminada de la prenda 1"
```

### En la Consola del Navegador (F12):
```javascript
// Deberías ver exactamente esto:
✅ Talla M eliminada de la prenda 1

// Si intentas eliminar otra:
✅ Talla L eliminada de la prenda 1

// Etc...
```

---

## ✅ TEST 3: ELIMINAR IMAGEN DE PRENDA

### Pasos:
1. En la misma cotización, desplázate a **Fotos de la Prenda**
2. Verás miniaturas de imágenes
3. Cada imagen tiene un botón rojo "×" en la esquina superior derecha
4. **Haz click en "×" de una imagen**

### Resultado Esperado:
```
✅ Aparece popup de SweetAlert
   - Título: "Eliminar imagen"
   - Mensaje: "¿Estás seguro de que quieres eliminar esta imagen? No se guardará en el pedido."
```

### Si haces click en "Sí, eliminar":
```
✅ La imagen desaparece de la vista
✅ Aparece notificación: "La imagen no se incluirá en el pedido. Las imágenes restantes han sido procesadas."
```

### En la Consola (F12):
```javascript
// Deberías ver:
✅ Imagen de prenda 0 eliminada. Las imágenes restantes se procesarán correctamente.
🔄 Procesando imágenes restantes de prenda 0...
   📸 Imágenes de prenda restantes: 2
     - Foto 1 de prenda será incluida
     - Foto 2 de prenda será incluida
✅ Procesamiento completado. Las imágenes restantes están listas para ser enviadas al servidor.
```

---

## ✅ TEST 4: ELIMINAR IMAGEN DE TELA

### Pasos:
1. En la misma cotización, busca sección **Fotos de Telas**
2. Verás imágenes de telas
3. **Haz click en "×" de una imagen de tela**

### Resultado Esperado:
```
✅ Popup de confirmación
✅ Imagen desaparece
✅ Notificación: "Las imágenes restantes han sido procesadas"
```

### En la Consola:
```javascript
✅ Imagen de tela de prenda 0 eliminada. Las imágenes restantes se procesarán correctamente.
🔄 Procesando imágenes restantes de telas para prenda 0...
   📸 Imágenes de tela restantes: 1
     - Foto de tela 1 será incluida
✅ Procesamiento completado...
```

---

## ✅ TEST 5: ELIMINAR FOTO DE REFLECTIVO

### Pasos:
1. En la misma cotización, busca sección **Imágenes del Reflectivo**
2. Verás imágenes del reflectivo
3. **Haz click en "×" de una imagen de reflectivo**

### Resultado Esperado:
```
✅ Popup de confirmación
✅ Imagen desaparece
✅ Notificación: "Las imágenes restantes del reflectivo han sido procesadas"
```

### En la Consola:
```javascript
✅ Foto del reflectivo ID 45 eliminada. Las imágenes restantes se procesarán correctamente.
🔄 Procesando imágenes restantes de reflectivo...
   📸 Imágenes de reflectivo restantes: 2
     - Reflectivo ID 43 será incluido
     - Reflectivo ID 44 será incluido
✅ Procesamiento completado...
```

---

## ✅ TEST 6: ELIMINAR LOGO (BORDADO)

### Pasos:
1. En la misma cotización, busca sección **Fotos del Bordado** (si existe)
2. Verás imágenes del logo/bordado
3. **Haz click en "×" de una imagen de logo**

### Resultado Esperado:
```
✅ Popup de confirmación
✅ Imagen desaparece
✅ Notificación: "Las imágenes restantes han sido procesadas"
```

### En la Consola:
```javascript
✅ Imagen de logo eliminada. Las imágenes restantes del logo se procesarán correctamente.
🔄 Procesando imágenes restantes de logo...
   📸 Imágenes de logo restantes: 1
     - Logo 1 será incluido
✅ Procesamiento completado...
```

---

## ✅ TEST 7: CREAR PEDIDO COMPLETO (CON ELIMINACIONES)

### Pasos:
1. En la misma cotización
2. Elimina varias tallas
3. Elimina varias imágenes de prenda
4. Elimina imágenes de reflectivo
5. **Agrega cantidades a las tallas restantes**
6. **Haz click en "Crear Pedido"**

### Resultado Esperado:
```
✅ En la consola verás el envío:
   📤 Enviando datos: {cotizacion_id: 45, forma_de_pago: "...", prendas: Array(2), ...}

✅ Luego de 2-3 segundos:
   ✅ Respuesta del servidor: {success: true, message: "...", ...}

✅ Popup de éxito: "¡Éxito! Pedido de producción creado exitosamente"

✅ Redirección a: /asesores/pedidos
```

### Verificar en la BD (Opcional):
```sql
SELECT * FROM pedidos_produccion WHERE numero_pedido = 'PED-XXXXX';
-- Verificar que:
-- ✅ Las tallas eliminadas NO aparecen en el JSON
-- ✅ Las imágenes eliminadas NO aparecen en el JSON
-- ✅ Solo imágenes/tallas que NO fueron eliminadas están presentes
```

---

## ❌ CASOS DE ERROR A VERIFICAR

### Error 1: Función no existe
```javascript
// ❌ Si ves en consola:
Uncaught ReferenceError: eliminarTallaReflectivo is not defined

// ✅ Solución: Verificar que el archivo fue actualizado correctamente
// grep -n "eliminarTallaReflectivo" public/js/crear-pedido-editable.js
```

### Error 2: SweetAlert no está disponible
```javascript
// ❌ Si ves en consola:
Uncaught ReferenceError: Swal is not defined

// ✅ Solución: Verificar que SweetAlert2 esté incluida en la vista
// <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

### Error 3: El elemento no se encuentra
```javascript
// ❌ En consola:
⚠️ No se encontró el elemento de talla M para prenda 0

// ✅ Solución: Verificar que el selector sea correcto
// El elemento debe tener: data-talla="M" data-prenda="0"
```

---

## 🔍 CÓMO LEER LA CONSOLA

1. **Abre Developer Tools:** `F12`
2. **Ve a pestaña "Console"**
3. **Busca líneas con:**
   - `✅` = Éxito
   - `❌` = Error
   - `⚠️` = Advertencia
   - `🔄` = Procesamiento
   - `📸` = Imágenes
   - `📤` = Envío al servidor

---

## 📋 CHECKLIST DE VERIFICACIÓN

| Prueba | Estado | Notas |
|--------|--------|-------|
| Cotización REFLECTIVO aparece | ✅ | Si no aparece, crear una |
| Botón "×" de talla existe | ✅ | Debe aparecer en cada talla |
| Popup de confirmación aparece | ✅ | SweetAlert debe estar cargado |
| Talla se elimina del DOM | ✅ | Desaparece de la pantalla |
| Consola registra la acción | ✅ | Debe aparecer mensaje con ✅ |
| Imagen desaparece | ✅ | Después de confirmar eliminación |
| Procesamiento se ejecuta | ✅ | Debe verse en consola "Procesando..." |
| Pedido se crea correctamente | ✅ | Redirección a /asesores/pedidos |
| BD tiene datos correctos | ✅ | Solo datos NO eliminados |

---

## 🚨 SI ALGO FALLA

1. **Revisar consola (F12)** para ver error exacto
2. **Verificar que archivo fue actualizado:** 
   ```bash
   grep -n "eliminarTallaReflectivo\|procesarImagenesRestantes" public/js/crear-pedido-editable.js
   ```
3. **Hacer Hard Refresh:** `Ctrl+Shift+R` (limpiar caché)
4. **Revisar que no haya errores de sintaxis:**
   - Búsqueda de `console.error` en consola
   - Búsqueda de líneas rojas en consola

---

## 💾 ARCHIVOS MODIFICADOS

- ✅ `public/js/crear-pedido-editable.js` - Completamente actualizado

## 📊 ANTES VS DESPUÉS

| Aspecto | Antes ❌ | Después ✅ |
|---------|----------|-----------|
| Eliminar talla | No funciona | Funciona con confirmación |
| Eliminar imagen | Sin validación | Con validación de restantes |
| Feedback usuario | Nada | SweetAlert + Consola |
| Datos al servidor | Posibles errores | Garantizado consistencia |
| Logs | No | Detallado en consola |

---

**Última actualización:** Diciembre 2025  
**Estado:** Ready for Testing  
**Prioridad:** Alta
