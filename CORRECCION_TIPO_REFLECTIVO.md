# ✅ CORRECCIÓN: Mostrar Cotización Reflectivo Correctamente

## 🔴 PROBLEMA
Cuando se guardaba una cotización tipo RF (reflectivo), se mostraba como "tipo prenda" en lugar de mostrar "reflectivo".

## ✅ SOLUCIONES IMPLEMENTADAS

### 1. **Agregar `tipo` al Formulario JavaScript**
**Archivo**: `resources/views/asesores/pedidos/create-reflectivo.blade.php`

```javascript
// Antes: No enviaba tipo
const data = {
    cliente, asesora, fecha, action, descripcion_reflectivo, ...
}

// Después: Envía tipo RF
const data = {
    cliente, asesora, fecha, action, tipo: 'RF', descripcion_reflectivo, ...
}
```

### 2. **Validar el `tipo` en el Controlador**
**Archivo**: `app/Infrastructure/Http/Controllers/CotizacionController.php`

Agregué validación:
```php
'tipo' => 'required|in:RF',
```

### 3. **Cargar Relaciones Correctas en showView()**
**Archivo**: `app/Infrastructure/Http/Controllers/CotizacionController.php`

Ahora carga:
- `reflectivoCotizacion` (datos del reflectivo)
- `tipoCotizacion` (para mostrar el tipo correcto)

### 4. **Actualizar Mapa de Tipos en Tab Navigation**
**Archivo**: `resources/views/components/cotizaciones/show/tabs.blade.php`

Agregué:
```php
'RF' => 'Reflectivo',
$esReflectivo = $cotizacion->tipo === 'RF';
$tieneReflectivo = $cotizacion->reflectivoCotizacion !== null;
```

### 5. **Crear Tab para Mostrar Reflectivo**
**Archivo**: `resources/views/components/cotizaciones/show/reflectivo-tab.blade.php`

Nuevo componente que muestra:
- ✅ Descripción del reflectivo
- ✅ Ubicaciones (con detalles)
- ✅ Observaciones generales
- ✅ Imágenes subidas

### 6. **Incluir Reflectivo Tab en la Vista**
**Archivo**: `resources/views/asesores/cotizaciones/show.blade.php`

```php
@include('components.cotizaciones.show.reflectivo-tab', [
    'cotizacion' => $cotizacion
])
```

## 📊 FLUJO CORRECCIÓN

```
Formulario RF enviado
    ↓
JavaScript agrega tipo: 'RF'
    ↓
Controlador valida tipo = 'RF'
    ↓
Crea Cotizacion con tipo_cotizacion_id = RF
    ↓
Crea ReflectivoCotizacion con datos
    ↓
Guarda imágenes
    ↓
showView carga relaciones completas
    ↓
Vista muestra "REFLECTIVO" en lugar de "PRENDA"
    ↓
Tab de Reflectivo se muestra con toda la información
```

## 🎯 RESULTADO

Ahora cuando se accede a `/asesores/cotizaciones/51/ver`:
- ✅ Muestra tipo correcto: "REFLECTIVO"
- ✅ Aparece tab "REFLECTIVO" en lugar de tab "PRENDAS"
- ✅ Muestra descripción, ubicaciones, observaciones e imágenes del reflectivo
- ✅ Datos se guardan correctamente en `reflectivo_cotizacion`

## 🔍 VALIDACIONES

- Tipo se envía como 'RF' desde el formulario
- Controlador valida que sea 'RF'
- Se crea automáticamente tipo de cotización si no existe
- Vista carga las relaciones necesarias
- Tab de reflectivo solo aparece si tieneReflectivo = true
