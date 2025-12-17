# Análisis e Implementación de Imágenes en Pedidos

**Fecha:** 17/12/2025  
**Estado:** 🔴 CRÍTICO - Imágenes NO se guardan actualmente  
**Prioridad:** ALTA

---

## 1. ESTADO ACTUAL DEL GUARDADO DE IMÁGENES

### ✅ Lo que FUNCIONA:
- ✅ Frontend captura URLs de imágenes en arrays: `fotos`, `telas`, `logos`
- ✅ JavaScript reúne las imágenes y las envía en el POST al backend
- ✅ Controller `PedidosProduccionController` RECIBE los datos de imágenes

### ❌ Lo que NO FUNCIONA:
- ❌ **Fotos de prenda**: NO se guardan en BD
- ❌ **Fotos de telas**: Se capturan pero NO se insertan en `prenda_fotos_tela_pedido`
- ❌ **Fotos de logo**: NO se guardan en BD
- ❌ **No hay modelo PrendaFotoPedido**: Tabla para fotos de prenda no existe
- ❌ **No hay modelo LogoPedido**: Tabla para logo de pedido no existe

---

## 2. ESTRUCTURA DE DATOS - FRONTEND

```javascript
// En crear-pedido-editable.js - Datos enviados al backend:

const prendaData = {
    // ... otros datos ...
    
    fotos: [
        "https://example.com/foto1.jpg",
        "https://example.com/foto2.jpg"
    ],
    
    telas: [
        "https://example.com/tela1.jpg",
        "https://example.com/tela2.jpg"
    ],
    
    logos: [
        "https://example.com/logo1.jpg"
    ]
};

// Se envía en el array 'prendas' del POST JSON
```

---

## 3. ESTRUCTURA DE BD - TABLAS EXISTENTES

### Tabla: `prenda_fotos_tela_pedido`
```sql
CREATE TABLE prenda_fotos_tela_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenda_pedido_id INT NOT NULL UNSIGNED,
    foto_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (prenda_pedido_id) REFERENCES prenda_pedido(id) ON DELETE CASCADE
);
```
- **Existe**: ✅ Sí
- **Usada**: ⚠️ No se inserta nada
- **Purpose**: Guardar URLs de fotos de telas para cada prenda

### Tabla: `prenda_pedido`
```sql
CREATE TABLE prenda_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pedido VARCHAR(50),
    nombre_prenda VARCHAR(255),
    descripcion LONGTEXT,
    cantidad INT,
    cantidad_talla JSON,
    color_id INT,
    tela_id INT,
    tipo_manga_id INT,
    tipo_broche_id INT,
    tiene_bolsillos BOOLEAN,
    tiene_reflectivo BOOLEAN,
    -- ... otros campos ...
);
```
- **Para fotos de prenda**: ❌ No hay columna foto_url ni relación
- **Para fotos de logo**: ❌ No hay lugar donde guardar

---

## 4. TABLAS A CREAR O RELACIONES A AGREGAR

### Opción A: Crear tablas separadas (RECOMENDADO)

#### Tabla: `prenda_fotos_pedido` (Para fotos de la prenda)
```sql
CREATE TABLE prenda_fotos_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenda_pedido_id INT NOT NULL UNSIGNED,
    foto_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prenda_pedido_id) REFERENCES prenda_pedido(id) ON DELETE CASCADE,
    INDEX idx_prenda_pedido (prenda_pedido_id)
);
```

#### Tabla: `logo_pedido` (Para logos del pedido)
```sql
CREATE TABLE logo_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_produccion_id INT NOT NULL UNSIGNED,
    logo_url VARCHAR(255) NOT NULL,
    tipo_ubicacion VARCHAR(50), -- PECHO, ESPALDA, MANGA, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_produccion_id) REFERENCES pedido_produccion(id) ON DELETE CASCADE,
    INDEX idx_pedido (pedido_produccion_id)
);
```

### Opción B: Agregar columnas JSON a prenda_pedido (ALTERNATIVA)
```sql
ALTER TABLE prenda_pedido ADD COLUMN fotos JSON COMMENT 'Array de URLs de fotos de la prenda';
ALTER TABLE pedido_produccion ADD COLUMN logos JSON COMMENT 'Array de URLs de logos del pedido';
```

---

## 5. MODELOS ELOQUENT A CREAR

### Modelo: `PrendaFotoPedido`
```php
// app/Models/PrendaFotoPedido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaFotoPedido extends Model
{
    protected $table = 'prenda_fotos_pedido';
    protected $fillable = ['prenda_pedido_id', 'foto_url'];
    public $timestamps = true;

    public function prendaPedido()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }
}
```

### Modelo: `LogoPedido`
```php
// app/Models/LogoPedido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoPedido extends Model
{
    protected $table = 'logo_pedido';
    protected $fillable = ['pedido_produccion_id', 'logo_url', 'tipo_ubicacion'];
    public $timestamps = true;

    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
}
```

---

## 6. CAMBIOS EN CONTROLLER - `PedidosProduccionController.php`

### Función: `crearDesdeCotizacion()` - Agregar guardado de imágenes

**Ubicación actual:** Línea ~250-350

**Cambios necesarios:**

```php
public function crearDesdeCotizacion($cotizacionId)
{
    // ... código existente de creación de PedidoProduccion ...
    
    $pedido = PedidoProduccion::create([...]);
    
    // PROCESAMIENTO DE PRENDAS
    foreach ($request->prendas as $prendaData) {
        $prenda = PrendaPedido::create([...]);
        
        // ✅ NUEVO: Guardar fotos de PRENDA
        if (!empty($prendaData['fotos'])) {
            foreach ($prendaData['fotos'] as $fotoUrl) {
                PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'foto_url' => $fotoUrl
                ]);
            }
        }
        
        // ✅ EXISTENTE PERO ROTO: Guardar fotos de TELAS
        if (!empty($prendaData['telas'])) {
            foreach ($prendaData['telas'] as $telaUrl) {
                PrendaFotoTelaPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'foto_url' => $telaUrl
                ]);
            }
        }
    }
    
    // ✅ NUEVO: Guardar logos del PEDIDO (después de procesar todas las prendas)
    if (!empty($request->logos)) {
        foreach ($request->logos as $logoUrl) {
            LogoPedido::create([
                'pedido_produccion_id' => $pedido->id,
                'logo_url' => $logoUrl
            ]);
        }
    }
    
    return response()->json(['success' => true, 'pedido_id' => $pedido->id]);
}
```

---

## 7. CAMBIOS EN MODELS

### PrendaPedido Model - Agregar relación
```php
// app/Models/PrendaPedido.php

class PrendaPedido extends Model
{
    // ... código existente ...
    
    // ✅ NUEVA RELACIÓN
    public function fotos()
    {
        return $this->hasMany(PrendaFotoPedido::class);
    }
    
    public function fotosTela()
    {
        return $this->hasMany(PrendaFotoTelaPedido::class);
    }
}
```

### PedidoProduccion Model - Agregar relación
```php
// app/Models/PedidoProduccion.php

class PedidoProduccion extends Model
{
    // ... código existente ...
    
    // ✅ NUEVA RELACIÓN
    public function logos()
    {
        return $this->hasMany(LogoPedido::class);
    }
}
```

---

## 8. CAMBIOS EN FRONTEND - `crear-pedido-editable.js`

### Función: `crearDesdeCotizacion()` - Ya captura imágenes ✅

**Estado actual:** Las imágenes YA se recopilan y se envían ✅

```javascript
// Línea ~820-895
const crearDesdeCotizacion = async () => {
    // ... código existente ...
    
    // Las imágenes ya se capturan y se incluyen en prendas:
    const prendas = document.querySelectorAll('[data-prenda-index]');
    prendas.forEach(prendasEl => {
        const fotos = [];
        const telasUrls = [];
        const logosUrls = [];
        
        prendasEl.querySelectorAll('.foto-prenda img').forEach(img => {
            fotos.push(img.src);
        });
        
        prendasEl.querySelectorAll('.foto-tela img').forEach(img => {
            telasUrls.push(img.src);
        });
        
        prendasEl.querySelectorAll('.foto-logo img').forEach(img => {
            logosUrls.push(img.src);
        });
        
        prendaObj.fotos = fotos;
        prendaObj.telas = telasUrls;
        prendaObj.logos = logosUrls;
    });
};
```

✅ **CONCLUSIÓN:** El frontend YA envía las imágenes correctamente

---

## 9. CAMBIOS REQUERIDOS EN FORMULARIO - `crear-pedido-blade.blade.php`

### Necesario: Capturar ubicación de logos

Los logos necesitan saber dónde van (pecho, espalda, manga):

```javascript
// Agregar en crearDesdeCotizacion()
const logosConUbicacion = [];
prendasEl.querySelectorAll('.foto-logo-container').forEach(container => {
    const img = container.querySelector('img');
    const ubicacion = container.getAttribute('data-ubicacion'); // PECHO, ESPALDA, etc.
    
    if (img) {
        logosConUbicacion.push({
            url: img.src,
            ubicacion: ubicacion
        });
    }
});

prendaObj.logos = logosConUbicacion;
```

---

## 10. TEST UNIT - Verificación

**Archivo:** `tests/Feature/CrearPedidoDesdeCotizacionEditableTest.php`

**Tests incluidos:**

1. ✅ `test_crear_pedido_guarda_datos_editados_completo()`
   - Verifica que datos editados se guardan
   - Verifica que FALLA guardado de imágenes (esperado)

2. ✅ `test_crear_pedido_con_multiples_prendas_editadas()`
   - Verifica múltiples prendas con diferentes telas/colores

3. ✅ `test_estructura_datos_en_base_datos()`
   - Imprime estructura completa de datos en BD
   - Verifica que IDs se hereden de cotización

4. 🔴 `test_imagenes_telas_deberían_guardarse()`
   - Marcado como SKIP
   - Documenta el problema que debe ser corregido
   - Será usado para validar la solución

---

## 11. PLAN DE IMPLEMENTACIÓN

### Fase 1: Crear migraciones y modelos
- [ ] Crear migración: `create_prenda_fotos_pedido_table`
- [ ] Crear migración: `create_logo_pedido_table`
- [ ] Crear modelos: `PrendaFotoPedido`, `LogoPedido`
- [ ] Agregar relaciones en `PrendaPedido` y `PedidoProduccion`

### Fase 2: Actualizar Controller
- [ ] Importar nuevos modelos en `PedidosProduccionController`
- [ ] Agregar bloque de guardado de fotos de prenda
- [ ] Agregar bloque de guardado de fotos de tela (corregir lo existente)
- [ ] Agregar bloque de guardado de logos

### Fase 3: Validar Frontend
- [ ] Verificar que `crear-pedido-editable.js` captura imágenes ✅ (ya funciona)
- [ ] Agregar captura de ubicación de logos
- [ ] Validar estructura de datos enviada

### Fase 4: Testing
- [ ] Ejecutar test `test_crear_pedido_guarda_datos_editados_completo()`
- [ ] Verificar en BD que se crean registros en `prenda_fotos_pedido`
- [ ] Verificar en BD que se crean registros en `prenda_fotos_tela_pedido`
- [ ] Verificar en BD que se crean registros en `logo_pedido`
- [ ] Ejecutar test `test_imagenes_telas_deberían_guardarse()` (debe pasar)

### Fase 5: UI - Mostrar imágenes guardadas
- [ ] Crear vista de detalle de pedido con galería de imágenes
- [ ] Mostrar fotos de prenda
- [ ] Mostrar fotos de telas
- [ ] Mostrar logos con ubicación
- [ ] Agregar funcionalidad para eliminar imágenes guardadas

---

## 12. ANÁLISIS DE IMPACTO

### ¿Qué pasa si no se implementa?

1. **Producción pierde referencias visuales**
   - No ve fotos de la prenda original
   - No ve fotos de las telas elegidas
   - No ve dónde van los logos

2. **Errores de fabricación**
   - Colores pueden no coincidirconas fotos
   - Logos pueden colocarse incorrectamente
   - Variaciones textuales pero sin referencia visual

3. **Requiere búsquedas manuales**
   - Operarios deben buscar fotos en cotización original
   - Aumenta tiempo de producción
   - Aumenta errores

### ¿Qué gana si se implementa?

✅ Producción tiene referencia visual completa
✅ Reduce errores de fabricación
✅ Acelera proceso de producción
✅ Mejor trazabilidad
✅ Datos documentados en BD para auditoría

---

## 13. RESUMEN DEL ESTADO

| Aspecto | Estado | Notas |
|---------|--------|-------|
| Frontend captura imágenes | ✅ Funciona | Ya se recopilan y envían |
| Backend recibe imágenes | ✅ Funciona | Llegan en request |
| Guardar fotos de prenda | ❌ No implementado | Necesita tabla + modelo |
| Guardar fotos de telas | ⚠️ Tabla existe pero no se usa | Necesita INSERT en controller |
| Guardar logos | ❌ No implementado | Necesita tabla + modelo |
| Test unitario | ✅ Creado | Documentados problemas |
| Datos en descripción | ✅ Funciona | Texto + variaciones guardadas |

---

## 14. PRÓXIMOS PASOS

1. **Inmediato:** Ejecutar test para confirmar estado actual ✅
2. **Corto plazo:** Crear migraciones y modelos (Fase 1)
3. **Medio plazo:** Implementar guardado en controller (Fase 2)
4. **Validación:** Ejecutar tests hasta que pasen (Fase 4)
5. **UI/UX:** Mostrar imágenes guardadas (Fase 5)
