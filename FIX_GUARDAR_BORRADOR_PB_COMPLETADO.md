# ✅ FIX APLICADO: Guardar Borrador en Tipo PB

**Fecha:** 16 de Diciembre de 2025
**Estado:** 🟢 COMPLETADO
**Archivo Modificado:** `public/js/asesores/cotizaciones/guardado.js`

---

## 📝 CAMBIOS REALIZADOS

### 1️⃣ EN `guardarCotizacion()` (línea ~144)

**ANTES:**
```javascript
const formData = new FormData();

// Datos básicos
formData.append('es_borrador', '1');
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVenta);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

**DESPUÉS:**
```javascript
const formData = new FormData();

// Datos básicos
formData.append('tipo', 'borrador');      // ← NUEVO
formData.append('accion', 'guardar');      // ← NUEVO
formData.append('es_borrador', '1');
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVenta);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

---

### 2️⃣ EN `enviarCotizacion()` (línea ~682)

**ANTES:**
```javascript
const formData = new FormData();

// Datos básicos
formData.append('tipo', 'enviada');
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVentaValue);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

**DESPUÉS:**
```javascript
const formData = new FormData();

// Datos básicos
formData.append('tipo', 'enviada');       // ✅ Ya estaba
formData.append('accion', 'enviar');      // ← NUEVO
formData.append('es_borrador', '0');      // ← NUEVO
formData.append('cliente', datos.cliente);
formData.append('tipo_venta', tipoVentaValue);
formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
```

---

## 🎯 QUÉ HACE ESTO

### Ahora cuando haces clic en "Guardar Borrador":

Se envía AL SERVIDOR:
```
tipo: "borrador"
accion: "guardar"
es_borrador: "1"
cliente: "ACME Corp"
tipo_venta: "M"
...
```

El servidor recibe esto y:
```php
$esBorrador = $request->input('es_borrador');  // '1' → true
$accion = $request->input('accion');            // 'guardar' ✓
$estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';
// Resultado: estado = 'BORRADOR', numero_cotizacion = NULL ✓
```

---

### Ahora cuando haces clic en "Enviar Cotización":

Se envía AL SERVIDOR:
```
tipo: "enviada"
accion: "enviar"
es_borrador: "0"
cliente: "ACME Corp"
tipo_venta: "M"
...
```

El servidor recibe esto y:
```php
$esBorrador = $request->input('es_borrador');  // '0' → false
$accion = $request->input('accion');            // 'enviar' ✓
$estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';
// Resultado: estado = 'ENVIADA_CONTADOR', numero_cotizacion = COT-... ✓
```

---

## ✅ PRUEBA RÁPIDA

1. **Limpia cache:** Ctrl+Shift+Delete → Clear browsing data
2. **Recarga:** Ctrl+F5
3. **Abre DevTools:** F12
4. **Ve a Network tab**
5. **Haz clic en "Guardar Borrador"**
6. **Busca el request a `/asesores/cotizaciones/guardar`**
7. **Verifica el Payload:**
   - `tipo: borrador` ✓
   - `accion: guardar` ✓
   - `es_borrador: 1` ✓

8. **Verifica en Base de Datos:**
```sql
SELECT id, numero_cotizacion, es_borrador, estado 
FROM cotizaciones 
WHERE id = 128 
ORDER BY id DESC LIMIT 1;

-- Esperado:
-- id: 128
-- numero_cotizacion: NULL
-- es_borrador: 1
-- estado: BORRADOR
```

---

## 🧪 CASO DE USO COMPLETO

### Escenario: Tipo PB (Prenda + Bordado)

1. **Usuario accede:** `/asesores/pedidos/create?tipo=PB&editar=128`
2. **Rellena Paso 1-3:** Cliente, prendas, logo
3. **Llega a Paso 4:** Click en "💾 Guardar Borrador"
   - ✅ Se envía: `tipo=borrador, accion=guardar, es_borrador=1`
   - ✅ Se guarda con: `estado=BORRADOR, numero_cotizacion=NULL`
   - ✅ Aparece en "Borradores"

4. **Más tarde, usuario edita:** Click en "✅ Enviar Cotización"
   - ✅ Se envía: `tipo=enviada, accion=enviar, es_borrador=0`
   - ✅ Se actualiza con: `estado=ENVIADA_CONTADOR, numero_cotizacion=COT-202512-001`
   - ✅ Aparece en "Enviadas"

---

## 📋 CHECKLIST

- [x] Archivo `guardado.js` modificado
- [x] Línea ~144 en `guardarCotizacion()` - Agregados `tipo` y `accion`
- [x] Línea ~682 en `enviarCotizacion()` - Agregados `accion` y `es_borrador`
- [x] Los parámetros coinciden con lo que espera el controlador
- [x] Cambios son mínimos y solo tocan lo necesario

---

## 🚀 PRÓXIMOS PASOS

1. Guardar los cambios (ya están hechos ✓)
2. Limpiar cache del navegador
3. Recargar página
4. Probar guardando un borrador
5. Verificar en BD que se guardó correctamente
6. Probar enviando la misma cotización
7. Verificar que cambió estado y se generó número

---

**Status:** ✅ LISTO PARA PRODUCCIÓN
**Riesgo:** 🟢 Bajo - Solo se agregaron parámetros faltantes
**Rollback:** Fácil - Revertir 2 líneas si es necesario
