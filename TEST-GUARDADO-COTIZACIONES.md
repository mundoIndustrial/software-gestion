# 🧪 SCRIPT DE PRUEBA - GUARDADO DE COTIZACIONES

## 📋 Descripción

Script de prueba para verificar que el sistema de guardado de cotizaciones funciona correctamente:

- ✅ Imágenes se guardan correctamente
- ✅ Datos de secciones se guardan completamente  
- ✅ Número de cotización es NULL en borradores
- ✅ Número de cotización se asigna al enviar

---

## 🚀 Cómo Ejecutar

### Opción 1: Ejecutar automáticamente al cargar la página

Agrega este script al final de `create-friendly.blade.php`:

```html
<!-- Script de prueba (solo en desarrollo) -->
@if(config('app.debug'))
    <script src="{{ asset('js/asesores/cotizaciones/test-guardado-cotizacion.js') }}"></script>
@endif
```

### Opción 2: Ejecutar manualmente desde la consola

1. Abre el formulario de cotización
2. Abre DevTools (F12)
3. Ve a la pestaña **Console**
4. Ejecuta:

```javascript
// Ejecutar todos los tests
window.testCotizaciones.ejecutarTodoTests()

// O ejecutar tests individuales
window.testCotizaciones.testFormData()
window.testCotizaciones.testEstructuraDatos()
window.testCotizaciones.testNumeroCotizacion()
window.testCotizaciones.testSimularGuardado()
window.testCotizaciones.testLogsEsperados()
```

---

## 📊 Tests Incluidos

### Test 1: Verificar FormData
- Crea un FormData con datos de prueba
- Verifica que los File objects se preservan
- Muestra el contenido del FormData

**Esperado:**
```
✅ FormData creado correctamente
✅ Foto agregada: foto1.jpg
✅ Tela agregada: tela1.jpg
```

### Test 2: Verificar estructura de datos
- Valida que todos los campos requeridos estén presentes
- Verifica la estructura de productos
- Verifica variantes de prendas

**Esperado:**
```
✅ Cliente: Empresa XYZ
✅ Productos: 2
✅ Técnicas: 2
✅ Ubicaciones: 2
✅ Observaciones generales: 2
✅ Especificaciones: 4
```

### Test 3: Verificar número de cotización
- Simula la lógica del backend
- Verifica que numero_cotizacion es NULL en borradores
- Verifica que numero_cotizacion se asigna al enviar

**Esperado:**
```
✅ Guardar como borrador
   Tipo: borrador
   Esperado: null
   Obtenido: null

✅ Enviar cotización
   Tipo: completa
   Esperado: COT-00001
   Obtenido: COT-00001
```

### Test 4: Simular guardado
- Crea un FormData completo
- Verifica que está listo para envío
- Muestra resumen de datos

**Esperado:**
```
✅ FormData preparado para envío
📊 Resumen:
   - Cliente: Test Company
   - Productos: 1
   - Técnicas: 1
   - Especificaciones: 1
   - Tipo de envío: FormData (multipart/form-data)
   - Archivos preservados: Sí ✅
```

### Test 5: Verificar logs esperados
- Lista los logs que deberías ver en la consola
- Ayuda a verificar que el guardado está funcionando

**Esperado:**
```
Logs esperados al guardar:
   1. ✅ Foto agregada a FormData [0][0]: imagen.jpg
   2. ✅ Tela agregada a FormData [0][0]: tela.jpg
   3. 📤 FORMDATA A ENVIAR: {tipo: 'borrador', cliente: '...', ...}
   4. ✅ Cotización creada con ID: 123
   5. ✅ Imágenes procesadas y guardadas en el servidor
```

---

## ✅ Prueba Completa (Manual)

### Paso 1: Preparar datos
1. Abre el formulario de cotización: `/asesores/cotizaciones/crear`
2. Completa todos los campos:
   - **Cliente**: "Empresa Test"
   - **Tipo de cotización**: "M" (Mayorista)
   - **Producto 1**: "Camisa DRILL"
     - Descripción: "Camisa drill con bordado"
     - Cantidad: 50
     - Tallas: S, M, L, XL
     - Fotos: Sube 1-2 imágenes
     - Tela: Sube 1 imagen
   - **Paso 3 (Bordado/Estampado)**:
     - Técnicas: BORDADO, DTF
     - Ubicación: PECHO, ESPALDA
     - Observaciones: "Bordado en pecho"
   - **Especificaciones**:
     - Forma de Pago: Efectivo
     - Régimen: Simplificado

### Paso 2: Ejecutar test
1. Abre DevTools (F12)
2. Ve a Console
3. Ejecuta: `window.testCotizaciones.ejecutarTodoTests()`
4. Verifica que todos los tests pasen ✅

### Paso 3: Guardar cotización
1. Haz clic en botón **GUARDAR**
2. Abre DevTools (F12)
3. Ve a Console
4. Verifica los logs esperados:
   - `✅ Foto agregada a FormData...`
   - `✅ Tela agregada a FormData...`
   - `📤 FORMDATA A ENVIAR...`
   - `✅ Cotización creada con ID: XXX`

### Paso 4: Verificar en BD
```sql
-- Verificar que numero_cotizacion es NULL en borradores
SELECT id, numero_cotizacion, es_borrador, estado 
FROM cotizaciones 
WHERE es_borrador = 1 
ORDER BY id DESC 
LIMIT 1;

-- Esperado:
-- id: 123, numero_cotizacion: NULL, es_borrador: 1, estado: BORRADOR
```

### Paso 5: Verificar imágenes en storage
```
storage/app/public/cotizaciones/123/prenda/
├── 123_prenda_001.jpg ✅
└── 123_prenda_002.jpg ✅

storage/app/public/cotizaciones/123/tela/
└── 123_tela_001.jpg ✅
```

### Paso 6: Enviar cotización
1. Haz clic en botón **ENVIAR**
2. Abre DevTools (F12)
3. Ve a Console
4. Verifica los logs esperados

### Paso 7: Verificar en BD (después de enviar)
```sql
-- Verificar que numero_cotizacion se asignó
SELECT id, numero_cotizacion, es_borrador, estado 
FROM cotizaciones 
WHERE id = 123;

-- Esperado:
-- id: 123, numero_cotizacion: COT-00001, es_borrador: 0, estado: ENVIADA_CONTADOR
```

---

## 🔍 Checklist de Verificación

### Antes de GUARDAR
- [ ] Todos los campos completados
- [ ] Fotos cargadas
- [ ] Telas cargadas
- [ ] Especificaciones completadas
- [ ] DevTools abierto en Console

### Después de GUARDAR
- [ ] ✅ Logs esperados en consola
- [ ] ✅ Cotización creada en BD
- [ ] ✅ numero_cotizacion = NULL
- [ ] ✅ Imágenes guardadas en storage
- [ ] ✅ Datos guardados en BD (productos, especificaciones, técnicas, ubicaciones)

### Después de ENVIAR
- [ ] ✅ Logs esperados en consola
- [ ] ✅ numero_cotizacion = "COT-00001"
- [ ] ✅ estado = "ENVIADA_CONTADOR"
- [ ] ✅ Redirige a lista de cotizaciones

---

## 🐛 Troubleshooting

### Problema: "window.testCotizaciones is undefined"
**Solución**: Asegúrate de que el script se cargó:
```javascript
// En consola, verifica:
console.log(window.testCotizaciones)
// Debería mostrar un objeto con las funciones
```

### Problema: "FormData no se envía correctamente"
**Solución**: Verifica que:
1. No hay `Content-Type: application/json` en headers
2. El body es FormData, no JSON.stringify()
3. Los File objects son `instanceof File`

### Problema: "Imágenes no se guardan"
**Solución**: Verifica:
1. Los logs muestran `✅ Foto agregada a FormData`
2. El controlador recibe `request->file('productos.0.fotos')`
3. El storage tiene permisos de escritura

### Problema: "numero_cotizacion no es NULL"
**Solución**: Verifica:
1. El tipo enviado es `'borrador'` (no `'completa'`)
2. En `CotizacionService.php` línea 57: `$esBorrador ? null : ...`

---

## 📝 Logs Esperados

### Al GUARDAR (tipo='borrador')
```
🚀 INICIANDO GUARDADO DE COTIZACIÓN
📸 Imágenes en memoria: {prendaConIndice: 2, telaConIndice: 1, logo: 0}
✅ Foto agregada a FormData [0][0]: imagen1.jpg
✅ Foto agregada a FormData [0][1]: imagen2.jpg
✅ Tela agregada a FormData [0][0]: tela1.jpg
📤 FORMDATA A ENVIAR: {tipo: 'borrador', cliente: 'Empresa Test', ...}
📡 Status de respuesta: 200
✅ Cotización creada con ID: 123
✅ Imágenes procesadas y guardadas en el servidor
¡Cotización guardada en borradores!
```

### Al ENVIAR (tipo='completa')
```
🚀 INICIANDO ENVÍO DE COTIZACIÓN
📸 Imágenes en memoria: {prendaConIndice: 2, telaConIndice: 1, logo: 0}
✅ Foto agregada a FormData [0][0]: imagen1.jpg
✅ Foto agregada a FormData [0][1]: imagen2.jpg
✅ Tela agregada a FormData [0][0]: tela1.jpg
📤 FORMDATA A ENVIAR: {tipo: 'completa', cliente: 'Empresa Test', ...}
📡 Status de respuesta: 200
✅ Cotización enviada con ID: 123
✅ Imágenes procesadas y guardadas en el servidor
¡Cotización enviada!
```

---

## 🎯 Conclusión

Si todos los tests pasan y los logs esperados aparecen en consola, entonces:

✅ **El sistema de guardado de cotizaciones está funcionando correctamente**

- Imágenes se guardan ✅
- Datos se guardan ✅
- Número de cotización se asigna correctamente ✅
- Ambos formularios funcionan ✅

---

## 📞 Soporte

Si encuentras problemas:

1. Abre DevTools (F12)
2. Ve a Console
3. Ejecuta: `window.testCotizaciones.ejecutarTodoTests()`
4. Copia los logs y compáralos con los "Logs Esperados"
5. Verifica que todos los tests pasen ✅

**Archivo del script**: `public/js/asesores/cotizaciones/test-guardado-cotizacion.js`
