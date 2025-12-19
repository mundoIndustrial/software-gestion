# 🎨 ANÁLISIS COMPLETO: Cómo se Crean y Guardan Pedidos LOGO en el Módulo Asesor

## 📋 Tabla de Contenidos
1. [Flujo General](#flujo-general)
2. [Frontend (JavaScript)](#frontend-javascript)
3. [Backend (Controlador)](#backend-controlador)
4. [Base de Datos](#base-de-datos)
5. [Rutas](#rutas)
6. [Ejemplos Prácticos](#ejemplos-prácticos)

---

## 🔄 Flujo General

```
Usuario selecciona cotización LOGO
         ↓
Carga prendas/datos de cotización
         ↓
Rellena formulario (Paso 3):
  - Descripción del logo
  - Técnicas (BORDADO, DTF, ESTAMPADO, SUBLIMADO)
  - Ubicaciones (PECHO, ESPALDA, MANGAS, etc.)
  - Observaciones
  - Fotos del logo
         ↓
Click "Crear Pedido"
         ↓
Detecta: esLogo = true
         ↓
Flujo LOGO (2 ENDPOINTS)
  ├─ POST /asesores/pedidos-produccion/crear-desde-cotizacion/{id}
  │  └─ Crea registro en tabla PedidoProduccion
  │
  └─ POST /asesores/pedidos/guardar-logo-pedido
     ├─ Crea registro en tabla LogoPedido
     └─ Guarda imágenes en storage
         ↓
Frontend redirige a /asesores/pedidos
```

---

## 💻 Frontend (JavaScript)

### Archivo: `public/js/crear-pedido-editable.js`

#### 1️⃣ **Detección de Tipo LOGO**

```javascript
// Línea ~1790
const esLogo = logoTecnicasSeleccionadas.length > 0 || 
               logoSeccionesSeleccionadas.length > 0 || 
               logoFotosSeleccionadas.length > 0;

console.log('🎨 [LOGO] Preparando datos de LOGO para enviar');
```

**Lógica:**
- Se detecta como LOGO si hay al menos uno de:
  - Técnicas seleccionadas en `logoTecnicasSeleccionadas[]`
  - Ubicaciones en `logoSeccionesSeleccionadas[]`
  - Fotos en `logoFotosSeleccionadas[]`

---

#### 2️⃣ **Primer Endpoint: Crear Pedido de Producción**

```javascript
// Línea ~1803
if (esLogo) {
    const bodyCrearPedido = {
        cotizacion_id: cotizacionId,
        forma_de_pago: formaPagoInput.value,
        prendas: []  // ← IMPORTANTE: Array VACÍO para LOGO
    };

    fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify(bodyCrearPedido)
    })
    .then(response => response.json())
    .then(dataCrearPedido => {
        if (!dataCrearPedido.success) {
            throw new Error(dataCrearPedido.message);
        }
        
        // Extraer ID del pedido
        const pedidoId = dataCrearPedido.logo_pedido_id || dataCrearPedido.pedido_id;
        
        // Proceder con el segundo endpoint...
    });
}
```

**Qué envía:**
- `cotizacion_id`: ID de la cotización LOGO
- `forma_de_pago`: Forma de pago del cliente
- `prendas`: Array VACÍO (no hay prendas en LOGO)

**Qué recibe:**
- `success`: Boolean indicando éxito
- `pedido_id` o `logo_pedido_id`: ID del registro creado

---

#### 3️⃣ **Segundo Endpoint: Guardar Datos LOGO**

```javascript
// Línea ~1821
const bodyLogoPedido = {
    pedido_id: pedidoId,                              // ← ID del pedido creado
    logo_cotizacion_id: logoCotizacionId,              // ← ID de cotización de logo
    descripcion: document.querySelector('textarea[id*="logo_descripcion"]')?.value || '',
    tecnicas: logoTecnicasSeleccionadas,               // ← Array de técnicas
    observaciones_tecnicas: document.querySelector('textarea[id*="logo_observaciones_tecnicas"]')?.value || '',
    ubicaciones: logoSeccionesSeleccionadas,           // ← Array de ubicaciones
    fotos: logoFotosSeleccionadas                      // ← Array de fotos
};

fetch('/asesores/pedidos/guardar-logo-pedido', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
    },
    body: JSON.stringify(bodyLogoPedido)
});
```

**Variables Globales Utilizadas:**

| Variable | Tipo | Contenido |
|----------|------|----------|
| `logoTecnicasSeleccionadas[]` | Array | `["BORDADO", "DTF"]` |
| `logoSeccionesSeleccionadas[]` | Array | Objetos con `{ubicacion, opciones, observaciones}` |
| `logoFotosSeleccionadas[]` | Array | Objetos con `{file, preview, url, existing}` |
| `logoCotizacionId` | Number | ID de LogoCotizacion |

---

#### 4️⃣ **Arrays Globales - Estructura Completa**

```javascript
// ========== UBICACIONES LOGO ==========
logoSeccionesSeleccionadas = [
    {
        ubicacion: "CAMISA",
        opciones: ["PECHO", "ESPALDA"],
        observaciones: "Bordado de alta resolución"
    },
    {
        ubicacion: "GORRAS",
        opciones: ["FRENTE"],
        observaciones: ""
    }
];

// ========== TÉCNICAS LOGO ==========
logoTecnicasSeleccionadas = [
    "BORDADO",
    "DTF",
    "ESTAMPADO"
];

// ========== FOTOS LOGO ==========
logoFotosSeleccionadas = [
    {
        file: File,                    // Archivo si es nuevo
        preview: "data:image/png;...",
        url: "/storage/logo_pedidos/...",
        existing: false                // true si es de cotización anterior
    },
    {
        url: "/storage/logo_cotizacion/imagen.jpg",
        existing: true,
        id: 123
    }
];
```

---

## ⚙️ Backend (Controlador)

### Archivo: `app/Http/Controllers/Asesores/PedidoProduccionController.php`

#### 1️⃣ **Método: `crearDesdeCotzacion()` (Línea 150-300)**

**Responsabilidad:** Crear el pedido de producción base

```php
// Detectar si es LOGO
$esLogoRequest = filter_var($request->input('esLogo', false), FILTER_VALIDATE_BOOLEAN);
$tipoCotizacion = $cotizacion->tipo_cotizacion_codigo ?? null;
$esCotizacionLogo = $esLogoRequest || $tipoCotizacion === 'L';

if ($esCotizacionLogo) {
    \Log::info('🎨 [PedidoProduccionController] Iniciando creación de pedido LOGO');
    
    // Generar número único
    $numeroPedido = 'LOGO-' . date('Ymd-His') . '-' . rand(100, 999);
    
    // Crear LogoPedido directamente
    $logoPedido = new LogoPedido([
        'pedido_id' => null,                                    // ← Puede ser null inicialmente
        'logo_cotizacion_id' => $request->input('logo_cotizacion_id', $cotizacion->id),
        'numero_pedido' => $numeroPedido,
        'descripcion' => $request->input('descripcion', 'Pedido de LOGO'),
        'tecnicas' => $request->input('tecnicas', []),
        'ubicaciones' => $request->input('ubicaciones', []),
        'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $logoPedido->save();
    
    // Procesar imágenes si existen
    if ($request->has('imagenes') && is_array($request->imagenes)) {
        foreach ($request->imagenes as $imagen) {
            try {
                $path = $imagen->store('public/bordado/pedidos/' . $logoPedido->id);
                $logoPedido->imagenes()->create([
                    'ruta' => str_replace('public/', 'storage/', $path),
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'tipo' => $imagen->getClientMimeType(),
                    'tamanio' => $imagen->getSize(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error al guardar imagen del logo: ' . $e->getMessage());
            }
        }
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Pedido de LOGO creado exitosamente',
        'pedido' => [
            'id' => $logoPedido->id,
            'numero_pedido' => $logoPedido->numero_pedido,
            'tipo' => 'logo'
        ]
    ]);
}
```

**Campos Guardados en BD:**
- `numero_pedido`: Identificador único del pedido
- `descripcion`: Descripción del logo
- `tecnicas`: JSON array de técnicas
- `ubicaciones`: JSON array de ubicaciones
- `observaciones_tecnicas`: Notas sobre técnicas
- `logo_cotizacion_id`: Referencia a cotización

---

#### 2️⃣ **Método: `guardarLogoPedido()` (Línea 700+)**

**Responsabilidad:** Guardar datos específicos del LOGO

```php
public function guardarLogoPedido(Request $request): JsonResponse
{
    // Validar datos
    $validated = $request->validate([
        'pedido_id' => 'required|numeric',
        'logo_cotizacion_id' => 'nullable|numeric',
        'descripcion' => 'nullable|string|max:1000',
        'tecnicas' => 'nullable|array',
        'ubicaciones' => 'nullable|array',
        'observaciones_tecnicas' => 'nullable|string',
        'fotos' => 'nullable|array'
    ]);

    try {
        DB::beginTransaction();

        // Buscar o crear LogoPedido
        $logoPedido = LogoPedido::findOrFail($validated['pedido_id']);
        
        // Actualizar datos
        $logoPedido->update([
            'descripcion' => $validated['descripcion'] ?? null,
            'tecnicas' => $validated['tecnicas'] ?? [],
            'ubicaciones' => $validated['ubicaciones'] ?? [],
            'observaciones_tecnicas' => $validated['observaciones_tecnicas'] ?? null,
        ]);

        // Procesar fotos (crear referencias o guardar nuevas)
        if (!empty($validated['fotos'])) {
            foreach ($validated['fotos'] as $foto) {
                if (isset($foto['existing']) && $foto['existing']) {
                    // Foto existente - solo crear referencia
                    LogoPedidoImagen::firstOrCreate([
                        'logo_pedido_id' => $logoPedido->id,
                        'ruta' => $foto['url']
                    ]);
                } else if (isset($foto['preview'])) {
                    // Foto nueva - convertir base64 a archivo
                    $imagenData = $foto['preview'];
                    if (strpos($imagenData, 'data:image') === 0) {
                        // Es base64
                        list($type, $imagenData) = explode(';', $imagenData);
                        list(, $imagenData) = explode(',', $imagenData);
                        $imagenData = base64_decode($imagenData);

                        // Guardar en storage
                        $filename = uniqid() . '.jpg';
                        $path = "logo_pedidos/{$logoPedido->id}/" . $filename;
                        Storage::disk('public')->put($path, $imagenData);

                        // Crear referencia en BD
                        LogoPedidoImagen::create([
                            'logo_pedido_id' => $logoPedido->id,
                            'ruta' => "/storage/{$path}"
                        ]);
                    }
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Logo pedido guardado correctamente',
            'logo_pedido' => [
                'id' => $logoPedido->id,
                'numero_pedido' => $logoPedido->numero_pedido,
                'descripcion' => $logoPedido->descripcion
            ]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error guardando logo pedido', ['error' => $e->getMessage()]);

        return response()->json([
            'success' => false,
            'message' => 'Error al guardar el logo'
        ], 500);
    }
}
```

**Lógica Importante:**
1. Valida todos los datos recibidos
2. Busca el LogoPedido por ID
3. Actualiza descripción, técnicas, ubicaciones, observaciones
4. Procesa fotos:
   - Si `existing: true` → solo referencia
   - Si `existing: false` → convertir base64 a archivo en storage

---

## 💾 Base de Datos

### Tabla: `logo_pedidos`

```sql
CREATE TABLE logo_pedidos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NULLABLE,           -- FK a pedidos_produccion (puede ser NULL)
    logo_cotizacion_id BIGINT UNSIGNED,            -- FK a logo_cotizaciones
    numero_pedido VARCHAR(50) UNIQUE,              -- LOGO-202512151745-123
    descripcion TEXT,                              -- Descripción del logo
    tecnicas JSON,                                 -- ["BORDADO", "DTF"]
    ubicaciones JSON,                              -- Array de ubicaciones
    observaciones_tecnicas TEXT,                   -- Observaciones por técnica
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos_produccion(id),
    FOREIGN KEY (logo_cotizacion_id) REFERENCES logo_cotizaciones(id)
);
```

### Tabla: `logo_pedido_imagenes`

```sql
CREATE TABLE logo_pedido_imagenes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    logo_pedido_id BIGINT UNSIGNED,                -- FK a logo_pedidos
    ruta VARCHAR(255),                             -- /storage/logo_pedidos/{id}/imagen.jpg
    nombre_original VARCHAR(255),                  -- Nombre original del archivo
    tipo VARCHAR(50),                              -- application/octet-stream
    tamanio INT,                                   -- Tamaño en bytes
    orden INT DEFAULT 0,                           -- Para ordenar imágenes
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (logo_pedido_id) REFERENCES logo_pedidos(id) ON DELETE CASCADE
);
```

---

## 🛣️ Rutas

### Archivo: `routes/asesores/pedidos.php`

```php
// ========== CREAR PEDIDO DESDE COTIZACIÓN ==========
Route::post('/pedidos-produccion/crear-desde-cotizacion/{cotizacion_id}',
    [PedidoProduccionController::class, 'crearDesdeCotzacion'])
    ->name('pedidos-produccion.crear-desde-cotizacion');

// ========== GUARDAR LOGO PEDIDO ==========
Route::post('/pedidos/guardar-logo-pedido',
    [PedidoProduccionController::class, 'guardarLogoPedido'])
    ->name('pedidos.guardar-logo-pedido');

// ========== OBTENER DATOS DE COTIZACIÓN (AJAX) ==========
Route::get('/cotizaciones/{cotizacion_id}',
    [PedidoProduccionController::class, 'obtenerDatosCotizacion'])
    ->name('cotizaciones.obtener-datos');

// ========== GUARDAR FOTOS DEL PEDIDO ==========
Route::post('/pedidos/guardar-fotos',
    [PedidoProduccionController::class, 'guardarFotosPedido'])
    ->name('pedidos.guardar-fotos');

// ========== ELIMINAR FOTO DE LOGO ==========
Route::post('/logos/{cotizacion_id}/eliminar-foto',
    [PedidoProduccionController::class, 'eliminarFotoLogo'])
    ->name('logos.eliminar-foto');
```

---

## 📝 Ejemplos Prácticos

### 1️⃣ **Ejemplo: Usuario Crea Pedido LOGO**

#### Datos en Frontend:
```javascript
logoCotizacionId = 5;

logoTecnicasSeleccionadas = ["BORDADO", "DTF"];

logoSeccionesSeleccionadas = [
    {
        ubicacion: "CAMISA",
        opciones: ["PECHO", "ESPALDA"],
        observaciones: "Bordado de alta calidad"
    }
];

logoFotosSeleccionadas = [
    {
        file: File,
        preview: "data:image/png;base64,iVBORw0KGgo...",
        existing: false
    }
];

formaPagoInput.value = "CONTADO";
```

#### Primer Request JSON:
```json
{
    "cotizacion_id": 45,
    "forma_de_pago": "CONTADO",
    "prendas": [],
    "esLogo": true
}
```

#### Respuesta del Servidor:
```json
{
    "success": true,
    "message": "Pedido de LOGO creado exitosamente",
    "pedido": {
        "id": 1234,
        "numero_pedido": "LOGO-20251219154530-456",
        "tipo": "logo"
    }
}
```

#### Segundo Request JSON:
```json
{
    "pedido_id": 1234,
    "logo_cotizacion_id": 5,
    "descripcion": "Logo bordado para uniforme",
    "tecnicas": ["BORDADO", "DTF"],
    "ubicaciones": [
        {
            "ubicacion": "CAMISA",
            "opciones": ["PECHO", "ESPALDA"],
            "observaciones": "Bordado de alta calidad"
        }
    ],
    "observaciones_tecnicas": "Usar hilo rojo para contraste",
    "fotos": [
        {
            "preview": "data:image/png;base64,iVBORw0KGgo...",
            "existing": false
        }
    ]
}
```

#### Registro Guardado en BD:
```sql
INSERT INTO logo_pedidos VALUES (
    1,
    NULL,
    5,
    'LOGO-20251219154530-456',
    'Logo bordado para uniforme',
    '["BORDADO","DTF"]',
    '[{"ubicacion":"CAMISA","opciones":["PECHO","ESPALDA"],"observaciones":"Bordado de alta calidad"}]',
    'Usar hilo rojo para contraste',
    NOW(),
    NOW()
);
```

---

### 2️⃣ **Flujo de Imágenes**

#### Imagen Nueva (Base64 → Archivo):
```
1. Usuario selecciona imagen
   ↓
2. JavaScript convierte a base64 y lo guarda en logoFotosSeleccionadas[]
   ↓
3. User envía formulario
   ↓
4. Backend recibe: "data:image/png;base64,iVBORw0KGgo..."
   ↓
5. Decodifica base64
   ↓
6. Guarda en: /storage/app/public/logo_pedidos/1234/xyz.jpg
   ↓
7. Crea referencia en BD: logo_pedido_imagenes
   ↓
8. URL pública: /storage/logo_pedidos/1234/xyz.jpg
```

#### Imagen Existente (Solo Referencia):
```
1. Imagen viene de cotización anterior
   ↓
2. Usuario ve previewen formulario
   ↓
3. Se marca como: existing: true, url: "..."
   ↓
4. Backend recibe imagen con existing: true
   ↓
5. Solo crea referencia en logo_pedido_imagenes
   ↓
6. NO duplica archivo en storage
```

---

### 3️⃣ **Validación de Campos**

| Campo | Validación | Ejemplo |
|-------|-----------|---------|
| `numero_pedido` | UNIQUE | LOGO-20251219154530-456 |
| `tecnicas` | JSON Array | `["BORDADO","DTF"]` |
| `ubicaciones` | JSON Array | `[{ubicacion:"CAMISA",...}]` |
| `fotos.preview` | Base64 | `data:image/png;base64,...` |
| `fotos.existing` | Boolean | `true` / `false` |

---

## 🔍 Debugging Tips

### Ver Logs:
```bash
tail -f storage/logs/laravel.log | grep "LOGO"
```

### Verificar BD:
```sql
SELECT * FROM logo_pedidos ORDER BY created_at DESC;
SELECT * FROM logo_pedido_imagenes WHERE logo_pedido_id = 1;
```

### Verificar Storage:
```bash
ls -la storage/app/public/logo_pedidos/
```

### Verificar Cotización Type:
```php
$cot = Cotizacion::find(45);
dd($cot->tipo_cotizacion_codigo);  // Debe ser 'L'
```

---

## 📊 Resumen de Características

✅ **Flujo de 2 endpoints** para máxima flexibilidad  
✅ **Números únicos automáticos** (LOGO-YYYYMMDD-HHMMSS-XXX)  
✅ **Almacenamiento seguro de imágenes** en storage public  
✅ **Soporte base64** para imágenes nuevas  
✅ **Reutilización de imágenes** de cotización anterior  
✅ **JSON fields** para datos complejos (técnicas, ubicaciones)  
✅ **Validación completa** en backend  
✅ **Logging detallado** con emojis para debugging  
✅ **Transacciones BD** para integridad  
✅ **Manejo robusto de errores**  

---

## 🎯 Puntos Clave

1. **Detección:** Si hay técnicas/ubicaciones/fotos = LOGO
2. **Flujo:** 2 requests (crear pedido → guardar logo)
3. **Imágenes:** Base64 → convertir a archivo → guardar en storage
4. **Números:** Secuencia única LOGO-YYYYMMDD-HHMMSS-XXX
5. **BD:** 2 tablas (logo_pedidos + logo_pedido_imagenes)
6. **Validación:** Backend valida y rechaza datos inválidos
7. **Errores:** Transacciones BD aseguran consistencia

