# 🧪 Guía de Testing: Validación del Bundle y Sistema de Dropdowns

## 🎯 Objetivo
Verificar que el bundle.js está funcionando correctamente y que el sistema de dropdowns + modales funciona sin errores.

---

## ✅ PASO 1: Limpiar Cache y Recargar

### En Terminal (PowerShell como Admin)
```powershell
cd c:\Users\Usuario\Documents\mundoindustrial
php artisan cache:clear
php artisan config:clear
```

### En Navegador
1. Abre la página: `http://localhost:8000/recibos-costura` (o tu URL)
2. **Ctrl + Shift + R** (Hard refresh para limpiar cache del navegador)
3. Verifica que la página carga correctamente

---

## 🔍 PASO 2: Abrir Console del Navegador
1. Presiona **F12** para abrir DevTools
2. Abre la pestaña **"Console"**
3. **BUSCA ESTE MENSAJE**:
   ```
   ✅ Bundle.js cargado: SÍ
   ```

### ✅ Si ves `SÍ` → Bundle está cargando
### ⚠️ Si ves `NO` → El bundle no cargó, esto es un problema!

---

## 🧪 PASO 3: Validar Tabla de Recibos

### 🔍 What to Check
En la consola, deberías ver:
```
[INFO] Inicializando RecibosCostruaModule...
[INFO] API Mock: buscando recibos...
[INFO] Recibos cargados: X filas (donde X es un número)
[INFO] Tabla renderizada en #recibos-table
```

### Visual: Tabla debe mostrar
- Columnas: N°, Estado, Área, Recibido, Días, Cliente, Abono, Encargado
- Al menos 1-2 filas de datos (si hay recibos en la BD)
- Botones de acción en cada fila (botón de ojo 👁️)

### ❌ Si NO hay datos
- **Abre Network Tab** → Busca `GET /api/recibos-costura`
- Verifica que responde 200 OK con datos
- Si error, revisar backend logs

---

## 🎯 PASO 4: Testing del Dropdown (CRÍTICO)

### 1️⃣ Click en Botón de Recibo
- **Busca**: Un recibo cualquiera en la tabla
- **Haz click**: En el botón **"Ver"** (en la columna de acciones, puede ser un ojo 👁️)
- **Esperado**: Un dropdown menu aparece con 3 opciones:
  ```
  Ver Detalles
  Seguimiento
  Novedades
  ```

### 2️⃣ Valida que el Dropdown:
- ✅ Aparece sin errores en consola
- ✅ Se posiciona cerca del botón que clickeaste
- ✅ Tiene las 3 opciones visibles
- ✅ Se cierra al hacer click fuera

### ❌ Si NO aparece el dropdown:
```
Revisa en Console (F12):
- ¿Hay error de JavaScript?
- ¿Dice algo como "Uncaught ReferenceError"?
- Nota el error exacto y repórtalo
```

### ❌ Si aparece pero con errores:
- En consola muestra errores en rojo
- El dropdown puede aparecer parcialmente o no funcionar
- Documenta el error exacto

---

## 🎪 PASO 5: Testing Modal - "Ver Detalles"

### 1️⃣ Click en "Ver Detalles" del Dropdown
- El dropdown debe estar abierto (del Paso 4)
- Haz click en **"Ver Detalles"**
- **Esperado**: Se abre un modal (ventana popup) con:
  - Título: Datos del recibo
  - Información del pedido
  - Botón cerrar (X)

### 2️⃣ Valida que el Modal:
- ✅ Aparece con overlay (fondo gris oscuro detrás)
- ✅ Muestra datos del recibo que seleccionaste
- ✅ Tiene botón de cerrar (X en la esquina)
- ✅ Sin errores en consola

### 3️⃣ Cierra el Modal
- Click en botón **X** o fuera del modal
- **Esperado**: Modal desaparece, overlay desaparece
- Tabla sigue siendo visible

### ❌ Si NO abre el modal:
```
En Console:
- Error: busca "openOrderDetailModal"
- ¿Dice función no definida?
- ¿Dice error al parsear JSON?
```

---

## 🚀 PASO 6: Testing Dropdown - "Seguimiento"

### 1️⃣ Vuelve a hacer click en botón de recibo
- Se abre dropdown nuevamente
- Haz click en **"Seguimiento"**
- **Esperado**: Se abre modal de seguimiento (o página)

### 2️⃣ Validaciones:
- ✅ Modal/página abre sin errores
- ✅ Muestra información de seguimiento del recibo
- ✅ Cierra correctamente

### ❌ Si NO funciona:
- Nota el error en consola
- Verifica que existe modal HTML para seguimiento en blade.php

---

## 📱 PASO 7: Testing Dropdown - "Novedades"

### 1️⃣ Vuelve a hacer click en botón de recibo
- Se abre dropdown nuevamente
- Haz click en **"Novedades"**
- **Esperado**: Se abre modal de novedades

### 2️⃣ Validaciones:
- ✅ Modal abre sin errores
- ✅ Muestra información de novedades
- ✅ Cierra correctamente

---

## 📋 Checklist de Validación

### Consola (F12)
- [ ] Mensaje: `✅ Bundle.js cargado: SÍ`
- [ ] Mensaje: `[INFO] Inicializando RecibosCostruaModule...`
- [ ] Mensaje: `[INFO] Recibos cargados: X filas`
- [ ] ❌ NO hay errores rojo en consola
- [ ] ❌ NO hay "Uncaught TypeError" o similar

### UI - Tabla
- [ ] Tabla renderiza con datos
- [ ] Columnas son visibles
- [ ] Al menos 1 recibo visible
- [ ] Botones de acción presentes

### UI - Dropdown
- [ ] Dropdown abre al clickear botón
- [ ] Dropdown muestra 3 opciones
- [ ] Dropdown se cierra al clickear fuera
- [ ] Sin errores al abrir

### UI - Modal "Ver Detalles"
- [ ] Modal se abre correctamente
- [ ] Muestra datos del recibo
- [ ] Modal tiene botón cerrar
- [ ] Modal se cierra sin errores

### UI - Modal "Seguimiento"
- [ ] Modal se abre al seleccionar de dropdown
- [ ] Muestra información relevante
- [ ] Se cierra correctamente

### UI - Modal "Novedades"
- [ ] Modal se abre al seleccionar de dropdown
- [ ] Muestra información relevante
- [ ] Se cierra correctamente

---

## 🐛 Troubleshooting: Errores Comunes

### Error: "Uncaught ReferenceError: EstadoRecibo is not defined"
**Causa**: Bundle.js no cargó correctamente
**Solución**: 
- Hard refresh (Ctrl+Shift+R)
- Verifica Network tab → `bundle.js` status 200
- Revisa permisos del archivo

### Error: "Cannot read property 'querySelector' of null"
**Causa**: Elemento HTML no existe en DOM
**Solución**:
- Verifica blade.php tiene `<div id="recibos-table"></div>`
- Bundle intenta escribir en lugar que no existe

### Dropdown no abre
**Causa**: Evento click no se registra
**Solución**:
- Verifica que botón tiene clase correcta
- Busca en bundle: `addEventListener('click'...)`
- Revisa que selectores CSS coincidan

### Modal no tiene datos
**Causa**: API no devuelve datos o JSON es inválido
**Solución**:
- Network tab → GET `/api/recibos-costura`
- Response debe ser JSON válido
- Verifica que backend está respondiendo

---

## 📝 Cómo Reportar Errores

Si algo no funciona, recopila:

```
1. SCREENSHOT del error en consola (F12)
2. URL de la página donde ocurre
3. Datos del recibo que estabas testeando
4. Paso exacto donde falla
5. Error exacto que dice en consola

Ejemplo de reporte:
"Al clickear el botón de acción en el recibo #123, el dropdown no abre.
Console muestra: 'Uncaught TypeError: crearDropdownRecibos is not defined'
Bundle.js status: Network tab muestra 200 OK
Cache cleared: Sí (Ctrl+Shift+R)"
```

---

## ✨ Si TODO Funciona Correctamente

Felicidades! 🎉

Esto significa:
- ✅ Bundle.js está cargando correctamente
- ✅ Arquitectura modular en JavaScript funciona
- ✅ Sistema de dropdowns y modales es operacional
- ✅ Estamos listos para:
  - Testing de filtros
  - Testing de segumiento
  - Migración de funciones en blade.php
  - Optimizaciones de performance

**Próximo paso**: Revisar PLAN_MIGRACION_RECIBOS_MODULAR.md y comenzar FASE A

---

## 🎮 Testing Rápido (2 minutos)

Si estás en prisa, solo verifica:
1. F12 → Console → `✅ Bundle.js cargado: SÍ`
2. Tabla muestra datos
3. Click en botón → dropdown aparece
4. Click "Ver Detalles" → modal abre

Si los 4 pasos funcionan → ✅ TODO BIEN
Si alguno falla → ❌ REVISAR ERRORES EN CONSOLA
