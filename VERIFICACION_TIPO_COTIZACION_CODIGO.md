# 🔍 VERIFICACIÓN - tipo_cotizacion_codigo EN FORMULARIO

**Fecha:** 10 de Diciembre de 2025
**Estado:** ⏳ PENDIENTE DE VERIFICACIÓN

---

## 🎯 PROBLEMA IDENTIFICADO

El formulario debe enviar `tipo_cotizacion_codigo` (P, L, PL) para que el servicio pueda:

1. Buscar el tipo en la tabla `tipos_cotizacion`
2. Obtener el `tipo_cotizacion_id` (1, 2, 3)
3. Guardar en la tabla `cotizaciones`

---

## 📋 FLUJO ACTUAL

```
FORMULARIO
    ↓
Envía: tipo_cotizacion_codigo = 'P' | 'L' | 'PL'
    ↓
CotizacionService::crear()
    ↓
Busca: TipoCotizacion::where('codigo', 'P')->first()
    ↓
Obtiene: tipo_cotizacion_id = 3
    ↓
Guarda en BD: cotizaciones.tipo_cotizacion_id = 3
```

---

## ⚠️ VERIFICACIÓN NECESARIA

### En el formulario (Blade)
```blade
<!-- Debe haber un campo oculto o selector que envíe tipo_cotizacion_codigo -->
<input type="hidden" name="tipo_cotizacion_codigo" value="P">
<!-- O -->
<select name="tipo_cotizacion_codigo">
    <option value="P">Prenda</option>
    <option value="L">Logo</option>
    <option value="PL">Prenda + Logo</option>
</select>
```

### En JavaScript (guardado.js)
```javascript
// Antes de enviar, debe incluir:
const datos = {
    cliente: ...,
    tipo_cotizacion_codigo: 'P',  // ← IMPORTANTE
    tipo_venta: 'M',
    productos: [...],
    ...
};
```

### En el Controlador
```php
// Debe pasar tipo_cotizacion_codigo al servicio
$cotizacion = $this->cotizacionService->crear(
    $request->validated(),  // Debe incluir tipo_cotizacion_codigo
    'borrador'
);
```

---

## 📊 MAPEO DE CÓDIGOS

| Código | ID | Nombre | Descripción |
|--------|----|---------|----|
| **P** | **3** | Prenda | Solo prendas |
| **L** | **2** | Logo | Solo logo/bordado |
| **PL** | **1** | Prenda/Logo | Prendas + logo |

---

## 🔧 CÓMO VERIFICAR

### 1. En el navegador (DevTools)
```javascript
// Abrir Console (F12)
// Crear una cotización
// Ver en Network → Request Payload
// Debe incluir: "tipo_cotizacion_codigo": "P"
```

### 2. En los logs
```bash
tail -f storage/logs/laravel.log
# Buscar: "Tipo cotización detectado"
# Debe mostrar: "codigo": "P", "tipo_cotizacion_id": 3
```

### 3. En la BD
```sql
SELECT id, cliente, tipo_cotizacion_id FROM cotizaciones ORDER BY id DESC LIMIT 1;
-- Debe mostrar: tipo_cotizacion_id = 3 (o 1, 2)
```

---

## ✅ CHECKLIST

- [ ] Formulario envía `tipo_cotizacion_codigo`
- [ ] Servicio recibe `tipo_cotizacion_codigo`
- [ ] Servicio busca en `tipos_cotizacion`
- [ ] Servicio obtiene `tipo_cotizacion_id`
- [ ] BD guarda `tipo_cotizacion_id` correctamente
- [ ] Logs muestran "Tipo cotización detectado"

---

## 📁 ARCHIVOS A REVISAR

1. **Formulario (Blade)**
   - `resources/views/cotizaciones/create.blade.php`
   - `resources/views/components/paso-*.blade.php`

2. **JavaScript**
   - `public/js/asesores/cotizaciones/guardado.js`
   - Buscar donde se construye el objeto de datos

3. **Controlador**
   - `app/Infrastructure/Http/Controllers/CotizacionController.php`
   - Método que recibe el formulario

4. **Servicio**
   - `app/Services/CotizacionService.php` ✅ YA VERIFICADO

---

## 🟢 ESTADO ACTUAL

**Servicio:** ✅ Listo para recibir `tipo_cotizacion_codigo`
**BD:** ✅ Guarda correctamente `tipo_cotizacion_id`
**Formulario:** ⏳ PENDIENTE DE VERIFICAR
**JavaScript:** ⏳ PENDIENTE DE VERIFICAR

---

**Verificación pendiente:** 10 de Diciembre de 2025
