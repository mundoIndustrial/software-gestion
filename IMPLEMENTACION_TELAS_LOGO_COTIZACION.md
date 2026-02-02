# Implementación: Cargar Telas desde logo_cotizacion_telas_prenda

## Objetivo
Cuando una cotización es de tipo **Logo** y el usuario selecciona una prenda, las telas se deben extraer de la tabla **`logo_cotizacion_telas_prenda`** en lugar de las telas normales de `prenda_tela_cot`.

## Cambios Realizados

### 1. **Modelo PrendaCot** 
📁 `app/Models/PrendaCot.php`

**Nueva relación agregada:**
```php
/**
 * Relación: Una prenda puede tener múltiples telas/colores/referencias en una cotización de logo
 * Se usa cuando la cotización es de tipo Logo para obtener las telas específicas de esta prenda
 */
public function logoCotizacionTelasPrenda(): HasMany
{
    return $this->hasMany(LogoCotizacionTelasPrenda::class, 'prenda_cot_id');
}
```

Esta relación permite acceder a las telas específicas de una prenda en una cotización de Logo.

---

### 2. **Backend: Controlador PedidosProduccionViewController**
📁 `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`

**Cambios en el método `obtenerPrendaCompleta()`:**

#### a) Cargar el tipo de cotización:
```php
$cotizacion = Cotizacion::with([
    'tipoCotizacion',  // ✅ Cargar para verificar si es Logo
    'prendas' => function($query) use ($prendaId) {
        // ...
```

#### b) Cargar la nueva relación de telas:
```php
'logoCotizacionTelasPrenda' => function($q) {  // ✅ Nueva relación
    // Cargar todas las telas/colores/referencias para esta prenda en logo
}
```

#### c) Lógica para procesar telas según tipo de cotización:
```php
// ✅ LÓGICA NUEVA: Si es cotización de tipo Logo, usar telas desde logo_cotizacion_telas_prenda
$esLogoCotizacion = $cotizacion->tipoCotizacion && 
                     (strtolower($cotizacion->tipoCotizacion->nombre) === 'logo' || 
                      strtolower($cotizacion->tipoCotizacion->nombre) === 'bordado');

if ($esLogoCotizacion && $prenda->logoCotizacionTelasPrenda && count($prenda->logoCotizacionTelasPrenda) > 0) {
    // Procesar telas desde logo_cotizacion_telas_prenda
    foreach ($prenda->logoCotizacionTelasPrenda as $telaLogo) {
        $tela_data = [
            'id' => $telaLogo->id,
            'nombre_tela' => $telaLogo->tela ?? 'SIN NOMBRE',
            'color' => $telaLogo->color ?? '',
            'referencia' => $telaLogo->ref ?? '',  // Campo "ref" de la tabla
            'descripcion' => '',
            'imagenes' => []
        ];
        // ... procesar imagen si existe ...
        $telasFormato[] = $tela_data;
    }
} else {
    // Usar lógica tradicional de PrendaTelaCot
    // ... código existente ...
}
```

**Ventajas:**
- ✅ Las telas se cargan desde la tabla correcta (`logo_cotizacion_telas_prenda`)
- ✅ Las referencias (`ref`) se incluyen automáticamente
- ✅ Compatible hacia atrás: si no es Logo, usa el método tradicional

---

### 3. **Backend: CrearPedidoEditableController**
📁 `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

**Cambios en el método `crearDesdeCotizacion()`:**

Se agregaron las cargas de relaciones necesarias en la query inicial:
```php
$cotizaciones = Cotizacion::with([
    'cliente',
    'tipoCotizacion',  // ✅ Agregar el tipo de cotización
    'prendas' => function($query) {
        $query->with([
            'fotos', 
            'telaFotos', 
            'tallas', 
            'variantes',
            'reflectivo.fotos',
            'logoCotizacionTelasPrenda' => function($q) {  // ✅ Nueva carga
                // Cargar todas las telas/colores/referencias para esta prenda en cotización de logo
            }
        ]);
    },
    'logoCotizacion.fotos',
    'logoCotizacion.telasPrendas',  // ✅ Agregar telasPrendas de la cotización de logo
    'reflectivoCotizacion.fotos'
])
```

---

### 4. **Frontend: cargar-prendas-cotizacion.js**
📁 `public/js/modulos/crear-pedido/integracion/cargar-prendas-cotizacion.js`

**Cambios en el método `transformarDatos()`:**

#### a) Procesar telas desde Logo:
```javascript
// ✅ LÓGICA NUEVA: Verificar si hay telas desde logoCotizacionTelasPrenda
let telasDesdeLogo = [];
if (data.prenda?.logoCotizacionTelasPrenda && Array.isArray(data.prenda.logoCotizacionTelasPrenda)) {
    console.log('[transformarDatos] 🎯 TELAS DESDE LOGO_COTIZACION_TELAS_PRENDA DETECTADAS');
    
    telasDesdeLogo = data.prenda.logoCotizacionTelasPrenda.map((telaLogo, idx) => {
        return {
            id: telaLogo.id,
            nombre_tela: telaLogo.tela || 'SIN NOMBRE',
            color: telaLogo.color || '',
            grosor: '',
            referencia: telaLogo.ref || '',  // ✅ Las referencias vienen en campo "ref"
            composicion: '',
            imagenes: telaLogo.img ? [{
                ruta: telaLogo.img,
                ruta_webp: telaLogo.img,
                uid: `existing-logo-tela-${telaLogo.id}`
            }] : [],
            origen: 'logo_cotizacion'
        };
    });
}
```

#### b) Lógica de combinación inteligente de telas (prioridades):
```javascript
// ✅ COMBINACIÓN INTELIGENTE: Priorizar Logo > Backend > Variantes
let telasFormato = [];

if (telasDesdeLogo && telasDesdeLogo.length > 0) {
    console.log('[transformarDatos] 🎯 USANDO TELAS DESDE LOGO (máxima prioridad)');
    telasFormato = [...telasDesdeLogo];  // ✅ Máxima prioridad: telas de Logo
} else if (telasDesdeBackend && telasDesdeBackend.length > 0) {
    console.log('[transformarDatos] 📋 USANDO TELAS DESDE BACKEND');
    telasFormato = [...telasDesdeBackend];  // Segunda prioridad
    // ... enriquecer con variantes ...
} else if (telasDesdeVariantes && telasDesdeVariantes.length > 0) {
    console.log('[transformarDatos] 🔄 USANDO TELAS DESDE VARIANTES (fallback)');
    telasFormato = [...telasDesdeVariantes];  // Última opción
}
```

**Ventajas del JavaScript:**
- ✅ Prioriza automáticamente las telas de Logo
- ✅ Mantiene compatibilidad hacia atrás
- ✅ Incluye logging detallado para debugging
- ✅ Maneja correctamente el campo `ref` de la tabla

---

## Flujo Completo

### Cuando el usuario elige una prenda de una cotización de tipo Logo:

1. **Frontend** → Llama a `CargadorPrendasCotizacion.cargarPrendaCompletaDesdeCotizacion()`
2. **Backend** → Endpoint `/asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}`
3. **Backend** → Detecta que es Logo, carga `logoCotizacionTelasPrenda`
4. **Backend** → Devuelve JSON con telas de la tabla `logo_cotizacion_telas_prenda`
5. **Frontend** → `transformarDatos()` procesa las telas
6. **Frontend** → Prioriza telas de Logo y construye `telasFormato`
7. **UI** → Muestra las telas en el modal de edición de prenda

---

## Tabla: logo_cotizacion_telas_prenda

```sql
CREATE TABLE logo_cotizacion_telas_prenda (
    id bigint unsigned auto_increment primary key,
    logo_cotizacion_id bigint unsigned,
    prenda_cot_id bigint unsigned,
    tela varchar(255),           -- Nombre de la tela
    color varchar(255),          -- Color de la tela
    ref varchar(255),            -- Referencia de la tela (NUEVO CAMPO CLAVE)
    img varchar(255),            -- Imagen de la tela
    created_at timestamp,
    updated_at timestamp
);
```

**Campos importantes:**
- `prenda_cot_id`: Relación con la prenda de cotización
- `ref`: Referencia de la tela (ahora se usa correctamente en el pedido)
- `tela`, `color`, `img`: Datos de la tela

---

## Testing

### Para verificar que funciona correctamente:

1. **En el navegador:**
   - Ir a `/asesores/pedidos-editable/crear-desde-cotizacion`
   - Seleccionar una cotización de tipo **Logo**
   - Seleccionar una prenda
   - ✅ Las telas deben venir de `logo_cotizacion_telas_prenda`
   - ✅ Las referencias (`ref`) deben aparecer

2. **En los logs:**
   ```
   [CargadorPrendasCotizacion] 🎯 TELAS DESDE LOGO_COTIZACION_TELAS_PRENDA DETECTADAS
   [transformarDatos] 🎯 USANDO TELAS DESDE LOGO (máxima prioridad)
   ```

3. **En la consola del navegador:**
   ```javascript
   console.log('[transformarDatos] 🎨 [Tela Logo 0]', {
       id: 1,
       tela: 'Algodón',
       color: 'Blanco',
       ref: 'ALG-BLA-001',
       img: '/storage/...'
   });
   ```

---

## Notas Importantes

✅ **Compatibilidad hacia atrás:** Si una cotización NO es de tipo Logo, sigue usando el método tradicional

✅ **Detección automática:** El código detecta automáticamente si es Logo por el `tipo_cotizacion`

✅ **Logging completo:** Se agregaron logs en backend y frontend para facilitar debugging

✅ **Campo `ref`:** La tabla `logo_cotizacion_telas_prenda` ya tiene el campo `ref`, ahora se usa correctamente

❌ **No requiere migraciones:** La tabla ya existe, solo se modificó el código

---

## Archivos Modificados

1. ✅ `app/Models/PrendaCot.php` - Agregada relación `logoCotizacionTelasPrenda()`
2. ✅ `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php` - Lógica de carga de telas por tipo
3. ✅ `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php` - Eager loading de relaciones
4. ✅ `public/js/modulos/crear-pedido/integracion/cargar-prendas-cotizacion.js` - Procesamiento de telas desde Logo

---

**Fecha de implementación:** 2 de Febrero de 2026
**Estado:** ✅ Completado
