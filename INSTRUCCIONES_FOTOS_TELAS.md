# 📸 CÓMO CARGAR FOTOS DE MÚLTIPLES TELAS EN COTIZACIONES

## ⚠️ PROBLEMA DETECTADO

Las fotos de telas **NO se guardaron** porque **NO se cargaron correctamente** en el formulario.

---

## ✅ SOLUCIÓN: PASO A PASO

### PASO 1: Agregar las telas en la tabla "COLOR, TELA Y REFERENCIA"

Dentro del formulario de cada prenda, ve a la sección **"COLOR, TELA Y REFERENCIA"** y haz lo siguiente:

1. **Llenar la primera fila (Tela 1)**:
   - Color: Ej. "Negro"
   - Tela: Ej. "Algodón"
   - Referencia: Ej. "ALG-001"
   - **IMPORTANTE**: Carga la FOTO de esta tela en la celda "Imagen Tela"

2. **Hacer clic en el botón "+ Agregar Tela"** (esquina superior derecha):
   - Se creará una NUEVA FILA para una segunda tela

3. **Llenar la segunda fila (Tela 2)**:
   - Color: Ej. "Azul"
   - Tela: Ej. "Poliéster"
   - Referencia: Ej. "POL-002"
   - **IMPORTANTE**: Carga la FOTO de esta tela en la celda "Imagen Tela"

---

## 📁 CÓMO CARGAR FOTOS DE TELAS

Para cada fila de tela:

1. **Localiza la celda "Imagen Tela"** (4ª columna de la tabla)
2. **Haz clic en la zona de carga** (donde dice "CLIC" o "ARRASTRA")
3. **Selecciona la imagen** de esa tela (JPG, PNG, etc.)
4. Verás un **preview de la imagen** inmediatamente
5. **Máximo 3 fotos por tela**

---

## 🔍 VERIFICACIÓN

Antes de guardar la cotización, verifica que:

✅ **Primera tela (índice 0)**:
- [ ] Color está rellenado
- [ ] Tela está rellenado
- [ ] Referencia está rellenado
- [ ] Foto está cargada (ves preview)

✅ **Segunda tela (índice 1)** (si agregaste):
- [ ] Color está rellenado
- [ ] Tela está rellenado
- [ ] Referencia está rellenado
- [ ] Foto está cargada (ves preview)

---

## 🐛 SI SIGUE SIN FUNCIONAR

Si las fotos aún NO se cargan o NO aparecen previews:

1. **Abre la consola del navegador** (F12)
2. Ve a la pestaña **"Console"**
3. **Carga una foto** y busca mensajes como:
   - `🔥 agregarFotoTela LLAMADA:`
   - `✅ Foto 1 de tela 0 guardada:`
   - `📊 Estado actual de telasSeleccionadas:`

4. **Captura de pantalla** de los mensajes de consola y comparte con el equipo

---

## ❌ PROBLEMAS COMUNES

### "El botón '+ Agregar Tela' no responde"
- Verifica que estés dentro de una prenda (`.producto-card`)
- Intenta refrescar la página

### "Las fotos no aparecen en preview"
- Asegúrate de hacer clic en la zona punteada azul
- Prueba con otra imagen
- Verifica que el archivo sea válido

### "Dice 'Máximo 3 fotos permitidas'"
- Solo puedes cargar máximo 3 fotos por tela
- Intenta eliminar una foto haciendo clic en la "X"

---

## 📝 NOTAS IMPORTANTES

- **Las fotos se almacenan EN MEMORIA** hasta que hagas clic en "Enviar"
- Si refrescas la página, **se perderán las fotos cargadas**
- El sistema automáticamente sube las fotos a `/storage/app/public/telas/cotizaciones/`
- Las fotos se guardan en la tabla `prenda_tela_fotos_cot`

---

## 📊 DATOS GUARDADOS

Cuando el sistema funcionacorrectamente, en la BD se guarda:
- `prenda_tela_fotos_cot`.`prenda_cot_id` = ID de la prenda en cotización
- `prenda_tela_fotos_cot`.`ruta_original` = URL pública de la foto
- `prenda_tela_fotos_cot`.`orden` = Orden de la foto (1, 2, 3...)

