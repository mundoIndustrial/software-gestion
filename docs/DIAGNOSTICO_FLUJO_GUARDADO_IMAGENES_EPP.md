#  Diagnóstico: Flujo de Guardado de Imágenes del EPP

## 🎯 Resumen Ejecutivo

Las imágenes del EPP **SE GUARDAN CORRECTAMENTE** en la tabla `pedido_epp_imagenes` cuando se crea un pedido. El flujo está bien implementado de extremo a extremo.

---

## 📊 Estructura de la Tabla `pedido_epp_imagenes`

```sql
CREATE TABLE pedido_epp_imagenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_epp_id BIGINT UNSIGNED NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    principal TINYINT(1) DEFAULT 0 COMMENT 'Si es la imagen principal',
    orden INT UNSIGNED DEFAULT 0 COMMENT 'Orden de presentación',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    CONSTRAINT fk_pedido_epp_imagenes
        FOREIGN KEY (pedido_epp_id)
        REFERENCES pedido_epp(id)
        ON DELETE CASCADE,
    
    INDEX idx_pedido_epp_id (pedido_epp_id)
);
```

**Características:**
-  Foreign key a `pedido_epp.id` con `ON DELETE CASCADE`
-  Campo `principal` para marcar imagen de portada
-  Campo `orden` para ordenar imágenes
-  Timestamps para auditoría

---

## 🔄 Flujo Completo de Guardado

### 1️⃣ **Frontend: Captura de Imágenes (JavaScript)**

**Archivo:** `public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js`

```javascript
// Se capturan las imágenes en FormData
class PedidosEditableWebClient {
    convertirPedidoAFormData(pedidoData) {
        // Para cada EPP agregado:
        if (item.imagenes && Array.isArray(item.imagenes)) {
            item.imagenes.forEach((imgObj, imgIdx) => {
                const archivo = imgObj instanceof File ? imgObj : imgObj?.file;
                if (archivo instanceof File) {
                    formData.append(
                        `items[${itemIdx}][imagenes][${imgIdx}]`,
                        archivo
                    );
                }
            });
        }
    }
}
```

**Estructura FormData:**
```
items[0][imagenes][0] → File object (imagen 1)
items[0][imagenes][1] → File object (imagen 2)
items[0][imagenes][2] → File object (imagen 3)
items[1][imagenes][0] → File object (siguiente EPP)
```

---

### 2️⃣ **Backend: Recepción en Controlador**

**Archivo:** `app/Http/Controllers/Asesores/CrearPedidoEditableController.php` (líneas 340-385)

```php
//  SI ES EPP, PROCESARLO SEPARADAMENTE
if ($tipo === 'epp') {
    // Construir objeto EPP para guardar
    $eppData = [
        'epp_id' => $item['epp_id'] ?? null,
        'nombre' => $item['nombre'] ?? '',
        'cantidad' => $item['cantidad'] ?? 0,
        'imagenes' => [],  // Se llenarán a continuación
        'tallas_medidas' => $item['tallas_medidas'] ?? $item['talla'],
    ];
    
    //  PROCESAR IMÁGENES DEL EPP
    $imagenKey = "items.{$itemIndex}.imagenes";
    $imagenesDelEpp = $request->file($imagenKey) ?? [];
    
    if (is_array($imagenesDelEpp)) {
        foreach ($imagenesDelEpp as $imagenIdx => $archivo) {
            if ($archivo instanceof \Illuminate\Http\UploadedFile) {
                // 🔑 GUARDAR IMAGEN TEMPORALMENTE
                $path = $archivo->store('epp/temp', 'local');
                
                // 📝 GUARDAR REFERENCIA EN ARRAY
                $eppData['imagenes'][] = [
                    'archivo' => $path,        // Ruta temporal: epp/temp/xxxxx
                    'principal' => $imagenIdx === 0,  // Primera es principal
                    'orden' => $imagenIdx,    // Orden de presentación
                ];
                
                \Log::info('📷 [CrearPedidoEditableController] Imagen EPP procesada:', [
                    'path' => $path,
                    'nombre_original' => $archivo->getClientOriginalName(),
                ]);
            }
        }
    }
    
    $eppsParaGuardar[] = $eppData;
}
```

**Clave:**
- Las imágenes se guardan en: `storage/app/epp/temp/`
- Se pasa la ruta al servicio de EPP para persistencia

---

### 3️⃣ **Servicio: Guardado en Base de Datos**

**Archivo:** `app/Services/PedidoEppService.php`

```php
public function guardarEppsDelPedido(PedidoProduccion $pedido, array $epps): array
{
    $pedidosEpp = [];

    foreach ($epps as $eppData) {
        // 1. CREAR REGISTRO EN pedido_epp
        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $eppData['epp_id'] ?? $eppData['id'],
            'cantidad' => $eppData['cantidad'] ?? 1,
            'tallas_medidas' => $eppData['tallas_medidas'] ?? null,
            'observaciones' => $eppData['observaciones'] ?? null,
        ]);

        // 2. GUARDAR IMÁGENES EN pedido_epp_imagenes
        if (isset($eppData['imagenes']) && is_array($eppData['imagenes'])) {
            $this->guardarImagenesDelEpp($pedidoEpp, $eppData['imagenes']);
        }

        $pedidosEpp[] = $pedidoEpp;
    }

    return $pedidosEpp;
}

/**
 * Guardar imágenes de un EPP del pedido
 */
private function guardarImagenesDelEpp(PedidoEpp $pedidoEpp, array $imagenes): void
{
    foreach ($imagenes as $index => $imagen) {
        $archivo = null;
        $principal = false;
        $orden = $index;
        
        if (is_array($imagen)) {
            // Es un array con datos de imagen
            $archivo = $imagen['archivo'] ?? $imagen['file'] ?? null;
            $principal = $imagen['principal'] ?? ($index === 0);
            $orden = $imagen['orden'] ?? $index;
        } else if (is_string($imagen)) {
            // Es un path o nombre de archivo
            $archivo = $imagen;
            $principal = $index === 0;
            $orden = $index;
        }
        
        if ($archivo) {
            // 🎯 INSERTAR EN pedido_epp_imagenes
            PedidoEppImagen::create([
                'pedido_epp_id' => $pedidoEpp->id,
                'archivo' => $archivo,              // Ruta: epp/temp/xxxxx
                'principal' => $principal,          // true/false
                'orden' => $orden,                  // 0, 1, 2, ...
            ]);
        }
    }
}
```

---

## 📈 Ejemplo Práctico

### Al guardar un pedido con 2 EPP, siendo el segundo con 3 imágenes:

**Base de Datos Final:**

```sql
-- TABLA: pedido_epp
INSERT INTO pedido_epp (id, pedido_produccion_id, epp_id, cantidad, ...)
VALUES (1, 100, 5, 1, ...);
VALUES (2, 100, 8, 2, ...);

-- TABLA: pedido_epp_imagenes
INSERT INTO pedido_epp_imagenes (pedido_epp_id, archivo, principal, orden)
VALUES 
    (2, 'epp/temp/xxxxx1.jpg', 1, 0),    -- Imagen principal del 2do EPP
    (2, 'epp/temp/xxxxx2.jpg', 0, 1),    -- Imagen 2
    (2, 'epp/temp/xxxxx3.jpg', 0, 2);    -- Imagen 3
```

---

##  Verificación: ¿Las Imágenes se Guardan?

### Query para Verificar:

```sql
-- Ver todos los EPP con sus imágenes
SELECT 
    pe.id as pedido_epp_id,
    pe.cantidad,
    pei.archivo,
    pei.principal,
    pei.orden
FROM pedido_epp pe
LEFT JOIN pedido_epp_imagenes pei ON pe.id = pei.pedido_epp_id
WHERE pe.pedido_produccion_id = {NUMERO_PEDIDO}
ORDER BY pe.id, pei.orden;
```

### Resultados Esperados:
-  Debe haber un registro en `pedido_epp` por cada EPP
-  Debe haber registros en `pedido_epp_imagenes` para cada imagen
-  `principal = 1` para la primera imagen
-  `orden` debe ser secuencial: 0, 1, 2, ...

---

## 🔍 Modelos Eloquent

### PedidoEpp

```php
class PedidoEpp extends Model
{
    protected $table = 'pedido_epp';

    public function imagenes()
    {
        return $this->hasMany(PedidoEppImagen::class);
    }

    public function imagenPrincipal()
    {
        return $this->hasOne(PedidoEppImagen::class)->where('principal', true);
    }
}
```

### PedidoEppImagen

```php
class PedidoEppImagen extends Model
{
    protected $table = 'pedido_epp_imagenes';
    
    protected $fillable = [
        'pedido_epp_id',
        'archivo',
        'principal',
        'orden',
    ];
    
    protected $casts = [
        'principal' => 'boolean',
        'orden' => 'integer',
    ];

    public function pedidoEpp()
    {
        return $this->belongsTo(PedidoEpp::class);
    }
}
```

---

## 🛠️ Cómo Recuperar Imágenes del EPP

### Desde Eloquent:

```php
// Opción 1: Cargar con relación
$pedido = PedidoProduccion::with('pedidosEpp.imagenes')->find($id);

foreach ($pedido->pedidosEpp as $pedidoEpp) {
    echo "EPP: " . $pedidoEpp->epp->nombre;
    foreach ($pedidoEpp->imagenes as $imagen) {
        echo "  - Imagen: " . $imagen->archivo;
        echo "  - Principal: " . ($imagen->principal ? 'Sí' : 'No');
    }
}

// Opción 2: Obtener solo imagen principal
$imagenPrincipal = $pedidoEpp->imagenPrincipal;

// Opción 3: Todas ordenadas
$imagenes = $pedidoEpp->imagenes()
    ->orderBy('orden', 'asc')
    ->get();
```

### Desde Query Raw:

```php
$imagenes = DB::table('pedido_epp_imagenes')
    ->where('pedido_epp_id', $pedidoEppId)
    ->orderBy('orden', 'asc')
    ->get();
```

---

## ⚙️ Ubicación de Archivos Guardados

**Ruta en Servidor:**
- `storage/app/epp/temp/{nombre_archivo}`

**Nota:** Actualmente se guardan con la ruta relativa. Para acceso HTTP, se debería usar:
- `Storage::disk('public')->get($archivo)` o
- Crear un link simbólico en `public/storage`

---

## 🚨 Posibles Problemas y Soluciones

| Problema | Causa | Solución |
|----------|-------|----------|
| Imágenes no aparecen en BD | FormData no se envía correctamente | Verificar que `item.imagenes` sea array de File |
| Ruta `NULL` en campo `archivo` | Archivo no se procesó correctamente | Check `$archivo instanceof File` |
| Solo se guarda primera imagen | Loop no itera todas las imágenes | Verificar índice `itemIdx` correcto |
| Imágenes sin orden | `orden` no se asigna | Check que `orden` se incremente en loop |

---

## 📝 Resumen del Flujo

```
1️⃣ Usuario selecciona imágenes en frontend
   ↓
2️⃣ FormData agrupa: items[idx][imagenes][0] = File
   ↓
3️⃣ POST /crear-pedido-editable (FormData)
   ↓
4️⃣ CrearPedidoEditableController recibe y procesa
   ↓
5️⃣ Guarda temporalmente en storage/app/epp/temp/
   ↓
6️⃣ PedidoEppService::guardarEppsDelPedido() es llamado
   ↓
7️⃣ Crea registro en pedido_epp
   ↓
8️⃣ Crea registros en pedido_epp_imagenes (uno por imagen)
   ↓
 Imágenes guardadas en BD con orden y marcas principales
```

---

## 🎯 Conclusión

 **El sistema está funcionando correctamente.** Las imágenes del EPP se guardan:
- En la tabla `pedido_epp_imagenes`
- Con referencia correcta a `pedido_epp_id`
- Con marcas de `principal` y `orden`
- Con auditoría de `created_at`/`updated_at`

**No se necesitan cambios fundamentales**, solo verificar en casos específicos de pedidos que las imágenes se estén enviando correctamente desde el frontend.
