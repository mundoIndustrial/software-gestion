# ✅ SOLUCIÓN: FIX PARA GUARDAR BORRADOR EN TIPO PB

**Fecha:** 16 de Diciembre de 2025
**Problema:** Cuando clickeas "Guardar Borrador" en `/asesores/pedidos/create?tipo=PB`, no guarda correctamente
**Tipo de Cotización:** PB (Prenda + Bordado/Logo Combinada)
**Solución:** ⚡ RÁPIDA - Agregar 2 líneas al JavaScript

---

## 🔴 EL PROBLEMA

En `guardarCotizacion()` se está enviando `es_borrador: '1'` pero NO se está enviando `tipo` ni `accion`.

El controlador verifica:
```php
$esBorrador = $request->input('es_borrador');
$accion = $request->input('accion'); // 'guardar' o 'enviar'
```

---

## ✅ LA SOLUCIÓN

### PASO 1: Abrir archivo

```
public/js/asesores/cotizaciones/guardado.js
```

### PASO 2: Buscar esta sección (línea ~143)

```javascript
// Datos básicos
formData.append('es_borrador', '1'); // Marcar como borrador
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVenta);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

### PASO 3: REEMPLAZAR por esto

```javascript
// Datos básicos
formData.append('tipo', 'borrador');  // ← AGREGAR ESTA LÍNEA
formData.append('accion', 'guardar');  // ← AGREGAR ESTA LÍNEA
formData.append('es_borrador', '1'); // Marcar como borrador
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVenta);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

---

## ✅ PARA ENVIAR (BONUS)

### Buscar en `enviarCotizacion()` (línea ~683)

Busca esta sección:

```javascript
// Datos básicos
formData.append('tipo', 'enviada');
formData.append('es_borrador', '0');
```

### Asegúrate de que esté así:

```javascript
// Datos básicos
formData.append('tipo', 'enviada');        // ✅ Debe estar
formData.append('accion', 'enviar');       // ← Agregar si no está
formData.append('es_borrador', '0');       // ✅ Debe estar
formData.append('cliente', datos.cliente);
```

---

## 🧪 PROBAR LA SOLUCIÓN

1. Abre DevTools (F12)
2. Vete a Network tab
3. Haz click en "Guardar Borrador"
4. Busca el request a `/asesores/cotizaciones/guardar`
5. Ve a "Payload" o "Request body"
6. Verifica que esté incluido:
   - `tipo: borrador` ✅
   - `accion: guardar` ✅
   - `es_borrador: 1` ✅

Si todo está, debería guardar como borrador correctamente.

---

## 📊 RESULTADO ESPERADO

### Cuando haces clic en "Guardar Borrador":

```sql
SELECT * FROM cotizaciones WHERE id = 128;

-- Deberá mostrar:
id: 128
numero_cotizacion: NULL           ← Sin número
es_borrador: 1 (true)              ← Marcado como borrador
estado: BORRADOR                   ← Estado correcto
tipo_venta: M (o D, X)             ← Tipo de venta
cliente_id: ...                    ← Cliente asignado
asesor_id: (tu usuario)            ← Tu ID
```

### Cuando haces clic en "Enviar Cotización":

```sql
SELECT * FROM cotizaciones WHERE id = 128;

-- Deberá mostrar:
id: 128
numero_cotizacion: COT-202512-... ← Número GENERADO
es_borrador: 0 (false)             ← NO es borrador
estado: ENVIADA_CONTADOR           ← Estado cambiado
```

---

## 🎯 CHECKPOINTS

- [ ] Abriste el archivo `guardado.js`
- [ ] Encontraste la sección en línea ~143
- [ ] Agregaste `formData.append('tipo', 'borrador')`
- [ ] Agregaste `formData.append('accion', 'guardar')`
- [ ] Guardaste el archivo (Ctrl+S)
- [ ] Limpias cache del navegador (Ctrl+Shift+Delete)
- [ ] Recargaste la página (Ctrl+F5)
- [ ] Probaste guardando un borrador
- [ ] Verificaste en BD que `es_borrador=1`

---

**Si no funciona:**

1. Abre DevTools → Network tab
2. Haz clic en "Guardar Borrador"
3. Mira el payload enviado
4. Compáralo con lo que esperas
5. Si falta algo, verifica que las líneas se guardaron bien

---

**Estado:** ✅ LISTO PARA IMPLEMENTAR
**Tiempo de fix:** ~2 minutos
**Complejidad:** 🟢 Baja
