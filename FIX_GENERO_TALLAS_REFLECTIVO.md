# Fix: Género y Tallas en Cotizaciones Reflectivo

## 🔴 Problema Identificado

Las **tallas** no se estaban capturando correctamente al enviar el formulario de cotizaciones reflectivo.

## 🔍 Root Cause

### Ubicación del Bug
**Archivo:** `resources/views/asesores/pedidos/create-reflectivo.blade.php`  
**Línea:** ~1770 (función de envío del formulario)

### El Problema Técnico

```javascript
// ❌ CÓDIGO ANTIGUO (BUGGY)
prenda.querySelectorAll('.talla-seleccionada').forEach(tallaDiv => {
    const tallaText = tallaDiv.textContent.trim();
    if (tallaText) {
        tallas.push(tallaText);
        cantidades[tallaText] = 1;
    }
});
```

**El problema:** El código buscaba elementos con clase `.talla-seleccionada`, pero las tallas agregadas **NO tienen esa clase**.

### Estructura Real de las Tallas

Cuando el usuario agrega tallas (líneas 2684-2691 o 2770-2777), se crean así:

```javascript
const tag = document.createElement('div');
tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; ...';
tag.innerHTML = `
    <span>${talla}</span>
    <button type="button" onclick="...">✕</button>
`;
tallasAgregadas.appendChild(tag);
```

**Estructura DOM real:**
```html
<div class="tallas-agregadas-reflectivo">
    <div style="...">
        <span>S</span>
        <button>✕</button>
    </div>
    <div style="...">
        <span>M</span>
        <button>✕</button>
    </div>
</div>
```

No hay clase `.talla-seleccionada` en ningún lado.

## ✅ Solución Implementada

### Código Corregido

```javascript
// ✅ CÓDIGO NUEVO (FIXED)
const tallas = [];
const cantidades = {};
const tallasContainer = prenda.querySelector('.tallas-agregadas-reflectivo');
if (tallasContainer) {
    tallasContainer.querySelectorAll('div > span:first-child').forEach(span => {
        const tallaText = span.textContent.trim();
        if (tallaText) {
            tallas.push(tallaText);
            cantidades[tallaText] = 1; // Valor por defecto
        }
    });
}
```

### Cambios Realizados

1. **Buscar el contenedor correcto**: `.tallas-agregadas-reflectivo`
2. **Seleccionar los spans**: `div > span:first-child` (el primer span de cada div)
3. **Extraer el texto**: `span.textContent.trim()`

### Logging Mejorado

Agregué logs detallados para verificar la captura de datos:

```javascript
console.log(`✅ Prenda ${index + 1}: ${tipo}`);
console.log(`   📍 Ubicaciones: ${ubicacionesDePrenda.length}`);
console.log(`   👤 Género: ${genero || 'No especificado'}`);
console.log(`   📏 Tallas: ${tallas.length > 0 ? tallas.join(', ') : 'Ninguna'}`);
```

## 📊 Flujo Completo de Datos

### Frontend → Backend → Base de Datos

```
1. Usuario agrega tallas (S, M, L, XL)
   ↓
2. Se crean divs en .tallas-agregadas-reflectivo
   ↓
3. Al enviar formulario:
   - Se busca .tallas-agregadas-reflectivo ✅
   - Se extraen spans con las tallas ✅
   - Se agregan a array: ['S', 'M', 'L', 'XL'] ✅
   ↓
4. Se envía JSON con prendas:
   {
     tipo: "Camiseta",
     tallas: ["S", "M", "L", "XL"],
     genero: "dama",
     cantidades: { "S": 1, "M": 1, "L": 1, "XL": 1 }
   }
   ↓
5. Backend (CotizacionController@storeReflectivo):
   - Crea prenda en prendas_cot
   - Guarda cada talla en prenda_tallas_cot ✅
   - Guarda género en prenda_variantes_cot ✅
```

## 🗄️ Tablas de Base de Datos

### 1. prendas_cot
```sql
CREATE TABLE prendas_cot (
    id BIGINT PRIMARY KEY,
    cotizacion_id BIGINT,
    nombre_producto VARCHAR(255),  -- Tipo de prenda
    descripcion TEXT,
    cantidad INT
);
```

### 2. prenda_tallas_cot
```sql
CREATE TABLE prenda_tallas_cot (
    id BIGINT PRIMARY KEY,
    prenda_cot_id BIGINT,  -- FK a prendas_cot
    talla VARCHAR(50),      -- 'S', 'M', 'L', '6', '8', etc.
    cantidad INT            -- Cantidad por talla
);
```

### 3. prenda_variantes_cot
```sql
CREATE TABLE prenda_variantes_cot (
    id BIGINT PRIMARY KEY,
    prenda_cot_id BIGINT,  -- FK a prendas_cot
    genero_id BIGINT,       -- FK a generos_prenda
    tipo_prenda VARCHAR(255),
    color VARCHAR(255),
    -- ... otros campos
);
```

### 4. generos_prenda (tabla de referencia)
```sql
CREATE TABLE generos_prenda (
    id BIGINT PRIMARY KEY,
    nombre VARCHAR(255)  -- 'Dama', 'Caballero', 'Unisex'
);
```

## 🔧 Backend - Procesamiento Correcto

### CotizacionController@storeReflectivo (líneas 1576-1623)

```php
// 1. Guardar tallas en prenda_tallas_cot
if (!empty($prenda['tallas']) && is_array($prenda['tallas'])) {
    $cantidades = $prenda['cantidades'] ?? [];
    foreach ($prenda['tallas'] as $talla) {
        $cantidad = $cantidades[$talla] ?? 1;
        \App\Models\PrendaTallaCot::create([
            'prenda_cot_id' => $prendaCot->id,
            'talla' => $talla,
            'cantidad' => (int)$cantidad,
        ]);
    }
    Log::info('✅ Tallas guardadas para prenda', [
        'prenda_cot_id' => $prendaCot->id,
        'tallas_count' => count($prenda['tallas']),
        'tallas' => $prenda['tallas'],
    ]);
}

// 2. Guardar género en prenda_variantes_cot
if (!empty($prenda['genero'])) {
    $generoId = null;
    if ($prenda['genero'] === 'dama') {
        $generoId = \DB::table('generos_prenda')
            ->where(\DB::raw('LOWER(nombre)'), 'dama')
            ->value('id');
    } elseif ($prenda['genero'] === 'caballero') {
        $generoId = \DB::table('generos_prenda')
            ->where(\DB::raw('LOWER(nombre)'), 'caballero')
            ->value('id');
    }
    
    if ($generoId) {
        \App\Models\PrendaVarianteCot::updateOrCreate(
            ['prenda_cot_id' => $prendaCot->id],
            ['genero_id' => $generoId]
        );
        Log::info('✅ Género guardado en prenda_variantes_cot', [
            'prenda_cot_id' => $prendaCot->id,
            'genero' => $prenda['genero'],
            'genero_id' => $generoId
        ]);
    }
}
```

## 📝 Estado del Género

### ✅ Género - YA FUNCIONABA CORRECTAMENTE

El género **SÍ se estaba capturando correctamente** desde el inicio:

```javascript
// Línea 1765
const genero = prenda.querySelector('.talla-genero-select-reflectivo')?.value || '';
```

El select tiene las opciones:
- `value="dama"` → Dama
- `value="caballero"` → Caballero

Y el backend lo procesa correctamente mapeando a la tabla `generos_prenda`.

## ✅ Verificación de la Solución

### Logs en Consola del Navegador

Después del fix, deberías ver:

```
✅ Prenda 1: Camiseta
   📍 Ubicaciones: 2
   👤 Género: dama
   📏 Tallas: S, M, L, XL
   
📦 DATOS QUE SE ENVIARÁN:
   prendas completas: [
     {
       "tipo": "Camiseta",
       "descripcion": "Camiseta con reflectivo",
       "tallas": ["S", "M", "L", "XL"],
       "genero": "dama",
       "cantidades": {
         "S": 1,
         "M": 1,
         "L": 1,
         "XL": 1
       },
       "ubicaciones": [...]
     }
   ]
```

### Logs en Laravel (storage/logs/laravel.log)

```
[INFO] ✅ Tallas guardadas para prenda
       prenda_cot_id: 123
       tallas_count: 4
       tallas: ["S", "M", "L", "XL"]
       
[INFO] ✅ Género guardado en prenda_variantes_cot
       prenda_cot_id: 123
       genero: dama
       genero_id: 1
```

### Verificación en Base de Datos

```sql
-- Ver tallas guardadas
SELECT pt.*, pc.nombre_producto
FROM prenda_tallas_cot pt
JOIN prendas_cot pc ON pt.prenda_cot_id = pc.id
WHERE pc.cotizacion_id = [ID];

-- Resultado esperado:
-- | id | prenda_cot_id | talla | cantidad |
-- |----|---------------|-------|----------|
-- | 1  | 123           | S     | 1        |
-- | 2  | 123           | M     | 1        |
-- | 3  | 123           | L     | 1        |
-- | 4  | 123           | XL    | 1        |

-- Ver género guardado
SELECT pv.*, gp.nombre as genero_nombre
FROM prenda_variantes_cot pv
JOIN generos_prenda gp ON pv.genero_id = gp.id
WHERE pv.prenda_cot_id = 123;

-- Resultado esperado:
-- | id | prenda_cot_id | genero_id | genero_nombre |
-- |----|---------------|-----------|---------------|
-- | 1  | 123           | 1         | Dama          |
```

## 📁 Archivos Modificados

### Frontend
- `resources/views/asesores/pedidos/create-reflectivo.blade.php`
  - Línea 1770-1779: Corrección de captura de tallas
  - Línea 1813-1816: Logging mejorado

### Backend (Ya funcionaba correctamente)
- `app/Infrastructure/Http/Controllers/CotizacionController.php`
  - Línea 1576-1593: Guardado de tallas
  - Línea 1595-1623: Guardado de género

## 🎯 Resumen de Fixes

| Componente | Estado Anterior | Estado Actual | Fix Aplicado |
|------------|----------------|---------------|--------------|
| **Imágenes** | ❌ Se perdían | ✅ Se guardan | DataTransfer API |
| **Tallas** | ❌ No se capturaban | ✅ Se capturan | Selector correcto |
| **Género** | ✅ Funcionaba | ✅ Funciona | Sin cambios |
| **Ubicaciones** | ✅ Funcionaba | ✅ Funciona | Sin cambios |

## 🚀 Prueba Completa

Para verificar que todo funciona:

1. Ir a `http://servermi:8000/asesores/pedidos/create?tipo=RF`
2. Agregar una prenda:
   - Tipo: "Camiseta"
   - Descripción: "Camiseta con reflectivo"
   - **Género**: Seleccionar "Dama"
   - **Tallas**: Agregar S, M, L, XL
   - **Imágenes**: Subir 2-3 fotos
   - **Ubicaciones**: Agregar "Pecho" y "Espalda"
3. Abrir consola del navegador (F12)
4. Enviar formulario (Guardar Borrador)
5. Verificar logs en consola:
   ```
   ✅ Prenda 1: Camiseta
      📍 Ubicaciones: 2
      👤 Género: dama
      📏 Tallas: S, M, L, XL
   📸 Archivos guardados en input: 3
   ```
6. Verificar en Laravel logs: `storage/logs/laravel.log`
7. Verificar en base de datos:
   ```sql
   SELECT * FROM prenda_tallas_cot WHERE prenda_cot_id = [ID];
   SELECT * FROM prenda_variantes_cot WHERE prenda_cot_id = [ID];
   SELECT * FROM reflectivo_fotos_cotizacion WHERE reflectivo_cotizacion_id = [ID];
   ```

## ✅ Resultado Final

Ahora el sistema guarda correctamente:
- ✅ **Género** por prenda en `prenda_variantes_cot`
- ✅ **Tallas** por prenda en `prenda_tallas_cot`
- ✅ **Imágenes** por prenda en `reflectivo_fotos_cotizacion`
- ✅ **Ubicaciones** por prenda en `reflectivo_cotizacion.ubicacion`

Todo el flujo de cotizaciones reflectivo está completo y funcional.
