# Arquitectura: Separación de Prendas y EPPs

**Fecha**: 26 Enero 2026  
**Estado**: IMPLEMENTACIÓN COMPLETA (Frontend + Backend)  
**Cambio Principal**: Separación de pedidos en dos estructuras: `prendas[]` y `epps[]`

---

## 📋 Resumen Ejecutivo

Se ha refactorizado completamente el flujo de creación de pedidos para separar las **prendas** (ropa) de los **EPPs** (equipos de protección personal). Aunque antes se trataban como un solo array `items[]`, ahora:

- **Prendas**: Requieren tallas, variantes, telas, procesos especiales (bordado, estampado, etc.)
- **EPPs**: Son más simples - solo requieren cantidad, observaciones e imágenes (convertidas a WebP)

Esta separación refleja la realidad de la base de datos y permite un procesamiento diferenciado.

---

## 🏗️ Arquitectura de Capas

### CAPA 1: Frontend (JavaScript)

**Archivo**: `/public/js/modulos/crear-pedido/procesos/services/`

#### 1.1 ItemFormCollector.js
- **Responsabilidad**: Recolectar datos del formulario UI en estructura JSON
- **Cambio Principal**: Separa EPPs de prendas en líneas 235-248
  ```javascript
  // Detecta si es EPP por propiedad 'es_epp' 
  // o si está en window.itemsPedido
  if (item.es_epp || nombrePrenda === item.nombre_epp) {
    // → Array 'epps' con estructura {epp_id, nombre_epp, cantidad, observaciones}
  } else {
    // → Array 'prendas' con estructura {tipo, nombre_prenda, cantidad_talla, variaciones, procesos}
  }
  ```
- **Output**: 
  ```javascript
  {
    cliente: "...",
    asesora: "...",
    forma_de_pago: "...",
    prendas: [ {...prenda_completa}, {...prenda_completa} ],
    epps: [ {...epp_simple}, {...epp_simple} ]
  }
  ```

#### 1.2 PayloadNormalizer.js
- **Responsabilidad**: Limpiar payload antes de enviar al backend
- **Cambio Principal**: Detecta ambas estructuras (nueva y antigua) en líneas 22-71
  ```javascript
  // Si tiene 'prendas' o 'epps' → Estructura NUEVA
  // Si tiene 'items' → Estructura ANTIGUA (mantiene compatibilidad)
  
  // Prendas: Normaliza cantidad_talla de string a numbers
  // EPPs: Solo preserva epp_id, nombre_epp, cantidad, observaciones
  ```

#### 1.3 ItemAPIService.js
- **Responsabilidad**: Manejar comunicación API con backend
- **Headers**: `Accept: application/json` (agregado para forzar respuestas JSON)
- **Cambio**: Logging mejorado para detectar estructura en líneas 164-170

### CAPA 2: Validación (Laravel FormRequest)

**Archivo**: `/app/Http/Requests/CrearPedidoCompletoRequest.php`

#### 2.1 Validación Dual
- **Nuevas Reglas** (líneas 45-48):
  ```php
  'prendas' => 'nullable|array',
  'prendas.*.nombre_prenda' => 'required_if:prendas,!=null|string|max:255',
  'epps' => 'nullable|array',
  'epps.*.epp_id' => 'required_if:epps,!=null|integer|exists:epps,id',
  'epps.*.cantidad' => 'required_if:epps,!=null|integer|min:1',
  ```
- **Compatibilidad**: Mantiene `items[]` para estructura antigua

#### 2.2 Método failedValidation()
- Retorna **JSON** en lugar de HTML redirect en caso de error
- Permite que frontend reciba errores de validación en formato JSON

### CAPA 3: Controlador (Backend Logic)

**Archivo**: `/app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

#### 3.1 Método crearPedido()
- **Línea 414**: Punto de entrada principal (POST /asesores/pedidos-editable/crear)
- **Detecta Estructura** (líneas 440-448):
  ```php
  $esEstructuraNueva = isset($validated['prendas']) || isset($validated['epps']);
  $esEstructuraAntiga = isset($validated['items']);
  
  // Si es nueva, convierte a items[] para compatibilidad con PedidoWebService
  if ($esEstructuraNueva && !$esEstructuraAntiga) {
    $validated['items'] = $validated['prendas'] ?? [];
    $validated['epps'] = $validated['epps'] ?? [];
  }
  ```

#### 3.2 Flujo Transaccional
1. **Decodificar JSON** y detectar estructura
2. **Crear Cliente** si no existe
3. **BEGIN TRANSACTION**
4. **Crear Pedido** (solo con prendas) via `PedidoWebService`
5. **Procesar Imágenes de Prendas** via `procesarYAsignarImagenes()`
6. **Procesar EPPs** via `procesarYAsignarEpps()` (NUEVO)
7. **COMMIT o ROLLBACK** con limpieza de archivos

#### 3.3 Método procesarYAsignarEpps() (NUEVO)
- **Línea 710**: Procesamiento específico para EPPs
- **Responsabilidades**:
  1. Validar que `epp_id` existe en tabla `epps`
  2. Crear registro en tabla `pedido_epp` con campos:
     - `pedido_id` (FK)
     - `epp_id` (FK)
     - `nombre_epp` (descriptivo)
     - `cantidad` (cantidad solicitada)
     - `observaciones` (notas opcionales)
  3. Procesar imágenes hacia `storage/app/public/pedidos/{id}/epps/`
  4. Convertir imágenes a **WebP** (responsabilidad de ImageUploadService)
  5. Crear registros en `pedido_epp_imagenes` con:
     - `pedido_epp_id` (FK)
     - `ruta_webp` (ruta de imagen convertida)
     - `orden` (secuencia)

### CAPA 4: Modelos (ORM)

#### 4.1 PedidoEpp
```php
class PedidoEpp extends Model {
    protected $table = 'pedido_epp';
    protected $fillable = ['pedido_id', 'epp_id', 'nombre_epp', 'cantidad', 'observaciones'];
    
    public function pedido() { return $this->belongsTo(PedidoProduccion::class); }
    public function imagenes() { return $this->hasMany(PedidoEppImagen::class); }
}
```

#### 4.2 PedidoEppImagen
```php
class PedidoEppImagen extends Model {
    protected $table = 'pedido_epp_imagenes';
    protected $fillable = ['pedido_epp_id', 'ruta_webp', 'orden'];
    
    public function pedidoEpp() { return $this->belongsTo(PedidoEpp::class); }
}
```

---

## 📊 Estructura de Datos

### REQUEST PAYLOAD (Nuevo)

```javascript
{
  "cliente": "Cliente ABC S.A.",
  "asesora": "asesora@empresa.com",
  "forma_de_pago": "Contado",
  "descripcion": "Pedido especial para evento",
  
  // PRENDAS (ropa con procesos especiales)
  "prendas": [
    {
      "tipo": "prenda_nueva",
      "nombre_prenda": "Polo Corporativo",
      "descripcion": "Polos para uniforme",
      "cantidad_talla": {
        "DAMA": {"XS": 5, "S": 10, "M": 8},
        "CABALLERO": {"M": 15, "L": 12, "XL": 8}
      },
      "variaciones": {
        "tipo_manga_id": 1,
        "tipo_broche_boton_id": 2
      },
      "telas": [
        {
          "tela_id": 10,
          "color_id": 5,
          "observaciones": "Tela premium 100% algodón"
        }
      ],
      "procesos": {
        "bordado": {
          "nombre": "bordado",
          "tipo": "bordado",
          "ubicaciones": ["PECHO", "ESPALDA"],
          "tallas": {"DAMA": 1, "CABALLERO": 1}
        }
      }
      // Archivos: prendas[0][imagenes][0], prendas[0][procesos][bordado][imagenes][0], etc.
    }
  ],
  
  // EPPS (Equipos de protección - estructura simple)
  "epps": [
    {
      "epp_id": 42,                          // Referencia a tabla 'epps'
      "nombre_epp": "Casco de Seguridad",    // Descriptivo
      "cantidad": 50,                         // Cantidad solicitada
      "observaciones": "Color azul marino"    // Notas opcionales
      // Archivos: epps[0][imagenes][0], epps[0][imagenes][1], etc.
    },
    {
      "epp_id": 15,
      "nombre_epp": "Guantes de Trabajo",
      "cantidad": 100,
      "observaciones": null
    }
  ]
}
```

### ARCHIVOS (FormData)

```
POST /asesores/pedidos-editable/crear

Form Fields:
- pedido: "[JSON STRING]"
- prendas[0][imagenes][0]: File (image)
- prendas[0][procesos][bordado][imagenes][0]: File (image)
- epps[0][imagenes][0]: File (image)  ← Será convertido a WebP
- epps[0][imagenes][1]: File (image)  ← Será convertido a WebP
- epps[1][imagenes][0]: File (image)  ← Será convertido a WebP
```

### ALMACENAMIENTO EN BD

#### Tabla: pedido_epp
```sql
id          int PRIMARY KEY
pedido_id   int FOREIGN KEY → pedidos_produccion
epp_id      int FOREIGN KEY → epps
nombre_epp  varchar(255)
cantidad    int
observaciones varchar(500) NULL
created_at  timestamp
updated_at  timestamp
```

#### Tabla: pedido_epp_imagenes
```sql
id              int PRIMARY KEY
pedido_epp_id   int FOREIGN KEY → pedido_epp
ruta_webp       varchar(500)    ← Imagen convertida a WebP
orden           int             ← Secuencia de imágenes
created_at      timestamp
```

### ALMACENAMIENTO EN FILESYSTEM

```
storage/app/public/
└── pedidos/
    └── {pedido_id}/
        ├── prendas/
        │   ├── prenda_1_img_0.webp
        │   └── prenda_1_img_1.webp
        ├── telas/
        │   └── tela_0_img_0.webp
        ├── procesos/
        │   ├── BORDADO/
        │   │   └── proceso_bordado_0.webp
        │   └── ESTAMPADO/
        │       └── proceso_estampado_0.webp
        └── epps/
            ├── epp_42_img_0.webp  ← EPP 1, imagen 1
            ├── epp_42_img_1.webp  ← EPP 1, imagen 2
            └── epp_15_img_0.webp  ← EPP 2, imagen 1
```

---

## 🔄 Flujo de Ejecución

### Paso 1: Frontend - Recolectar Datos (ItemFormCollector.js)

```
User clicks "Guardar Pedido"
↓
ItemFormCollector.recolectarDatos()
├─ Lee prendas de GestionItemsUI
├─ Lee EPPs de window.itemsPedido
├─ Separa en arrays prendas[] y epps[]
└─ Return JSON con estructura separada
```

### Paso 2: Frontend - Normalizar Payload (PayloadNormalizer.js)

```
JSON del paso 1
↓
PayloadNormalizer.normalizarPedido()
├─ Detecta estructura (prendas/epps vs items)
├─ Limpia archivos File del payload
├─ Convierte strings a objetos donde necesario
├─ Preserva diferencias: prendas≠epps
└─ Return payload limpio para enviar
```

### Paso 3: Frontend - Construir FormData (ItemAPIService.js)

```
Payload normalizado
↓
realizarPeticion()
├─ Extrae archivos del payload
├─ Construye FormData:
│  ├─ pedido: JSON string
│  ├─ prendas[0][imagenes][0..n]: files
│  ├─ epps[0][imagenes][0..n]: files
│  └─ otros campos
├─ Headers: Accept: application/json
└─ POST al backend
```

### Paso 4: Backend - Validar Estructura (CrearPedidoCompletoRequest)

```
FormData llega
↓
CrearPedidoCompletoRequest::validated()
├─ Valida prendas[*]:
│  ├─ nombre_prenda (required)
│  ├─ cantidad_talla (object)
│  ├─ variaciones (optional)
│  ├─ procesos (optional)
│  └─ telas (optional)
├─ Valida epps[*]:
│  ├─ epp_id (required, must exist in epps table)
│  ├─ cantidad (required, >= 1)
│  ├─ nombre_epp (optional)
│  └─ observaciones (optional)
└─ Si hay error → failedValidation() retorna JSON
```

### Paso 5: Backend - Crear Pedido (crearPedido controller)

```
JSON validado + archivos
↓
CrearPedidoEditableController::crearPedido()
├─ 1. Decodificar JSON metadata
├─ 2. Detectar estructura (nueva vs antigua)
├─ 3. Normalizar para compatibilidad (items[] es lo esperado)
├─ 4. Obtener/Crear cliente
├─ 5. BEGIN TRANSACTION
├─ 6. Crear pedido via PedidoWebService
│  └─ Procesa solo prendas (items[])
├─ 7. procesarYAsignarImagenes() → prendas + telas + procesos
├─ 8. procesarYAsignarEpps() → NUEVO, procesa solo epps[]
├─ 9. COMMIT
└─ 10. Return JSON con pedido_id
```

### Paso 6: Backend - Procesar Prendas (procesarYAsignarImagenes)

```
Para cada prenda en items[]:
├─ Procesar imágenes de prenda
│  └─ imageUploadService.guardarImagenDirecta()
│     → storage/app/public/pedidos/{id}/prendas/
│     → Crear PrendaFotoPedido
├─ Procesar imágenes de telas
│  └─ imageUploadService.guardarImagenDirecta()
│     → storage/app/public/pedidos/{id}/telas/
│     → Crear PrendaFotoTelaPedido
└─ Procesar imágenes de procesos
   └─ imageUploadService.guardarImagenDirecta()
      → storage/app/public/pedidos/{id}/procesos/{TIPO}/
      → Crear PedidosProcessImagenes
```

### Paso 7: Backend - Procesar EPPs (procesarYAsignarEpps) NUEVO

```
Para cada epp en epps[]:
├─ 1. Validar epp_id existe en tabla epps
├─ 2. Crear PedidoEpp:
│  ├─ pedido_id = current pedido
│  ├─ epp_id = reference to epps table
│  ├─ nombre_epp = EPP name
│  ├─ cantidad = quantity
│  └─ observaciones = notes
├─ 3. Para cada imagen EPP:
│  ├─ imageUploadService.guardarImagenDirecta()
│  │  → storage/app/public/pedidos/{id}/epps/
│  │  → Conversión automática a WebP por ImageUploadService
│  ├─ Crear PedidoEppImagen:
│  │  ├─ pedido_epp_id = just created
│  │  ├─ ruta_webp = WebP path
│  │  └─ orden = sequence
│  └─ Log success
└─ Return to crearPedido() for COMMIT
```

---

## 🧪 Testing Manual

### 1. Test Structure Detection

**Enviar con estructura NUEVA**:
```json
{
  "cliente": "Test Cliente",
  "asesora": "test@test.com",
  "forma_de_pago": "Contado",
  "prendas": [{...}],
  "epps": [{...}]
}
```

**Logs esperados**:
```
[CrearPedidoEditableController] Estructura detectada
nueva: SÍ (prendas/epps)
antigua: NO
```

### 2. Test EPP Creation

**Request**:
```javascript
FormData {
  pedido: JSON with epps[],
  epps[0][imagenes][0]: image.jpg,
  epps[0][imagenes][1]: image.png,
  epps[1][imagenes][0]: image.jpg
}
```

**Logs esperados**:
```
[CrearPedidoEditableController] 📦 Procesando EPPs
pedido_id: 123
epps_count: 2

[CrearPedidoEditableController] EPP creado
pedido_epp_id: 45
epp_id: 42
cantidad: 50

[CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP)
pedido_epp_id: 45
webp: pedidos/123/epps/epp_42_img_0.webp
```

### 3. Test Backward Compatibility

**Enviar con estructura ANTIGUA**:
```json
{
  "cliente": "Old Test",
  "asesora": "test@test.com",
  "forma_de_pago": "Contado",
  "items": [{tipo: "prenda_nueva", nombre_prenda: "...", ...}]
}
```

**Comportamiento esperado**:
- Detector reconoce como `items[]` (antigua)
- Procesa como antes
- No intenta procesar `epps[]`
- Funciona sin cambios

---

## 📝 Cambios Implementados

### Frontend Changes
- `ItemFormCollector.js` - Separa EPPs en array distinct
- `PayloadNormalizer.js` - Normaliza ambas estructuras
- `ItemAPIService.js` - Logging mejorado

### Backend Changes
- `CrearPedidoCompletoRequest.php` - Validación dual (prendas + epps)
- `CrearPedidoEditableController.php`:
  - Detección de estructura
  - Normalización para compatibilidad
  - Método `procesarYAsignarEpps()` nuevo
  - Imágenes EPP → WebP
  - Registros `PedidoEpp` + `PedidoEppImagen`

### Database (Asumido - verificar)
- Tabla `pedido_epp` existe
- Tabla `pedido_epp_imagenes` existe
- Tabla `epps` existe (catálogo)

---

##  Próximos Pasos

### 1. Testing Integral
- [ ] Test con prendas SOLAS (sin EPPs)
- [ ] Test con EPPs SOLOS (sin prendas)
- [ ] Test con prendas + EPPs (mixed)
- [ ] Test backward compatibility (items[])
- [ ] Test validación FormRequest

### 2. Frontend Verification
- [ ] Verificar que ItemFormCollector detecta EPPs correctamente
- [ ] Verificar que PayloadNormalizer maneja ambas estructuras
- [ ] Verificar envío de archivos para epps[i][imagenes][j]

### 3. Image Processing Verification
- [ ] Verificar que ImageUploadService convierte a WebP
- [ ] Verificar rutas en filesystem: `pedidos/{id}/epps/`
- [ ] Verificar registros en BD

### 4. Error Handling
- [ ] epp_id inválido → error validación clara
- [ ] Imagen corrupta → rollback transacción
- [ ] Error conversión WebP → limpieza de archivos

### 5. Documentación
- [ ] API docs con estructura nueva
- [ ] Guía para frontend developers
- [ ] Ejemplos curl/postman

---

## 🔍 Debugging

### Ver estructura en logs
Logs clave para verificar flujo:

```php
// Frontend
console.log('[ItemFormCollector] Prendas:', prendas.length, 'EPPs:', epps.length);

// Backend
Log::info('[CrearPedidoEditableController] Estructura detectada', [
    'nueva' => $esEstructuraNueva,
    'antigua' => $esEstructuraAntiga,
]);

Log::info('[CrearPedidoEditableController] 📦 Procesando EPPs', [
    'pedido_id' => $pedidoId,
    'epps_count' => count($epps),
]);
```

### Common Issues

| Problema | Causa | Solución |
|----------|-------|----------|
| "Unexpected token '<'" | HTML error response | Agregar `Accept: application/json` header |
| "El epp_id es obligatorio" | Validación fallando | Verificar epps[].epp_id existe |
| "epp_id... does not exist" | No existe en tabla epps | Verificar ID en tabla epps |
| Imagen no guardada | FormData key incorrecto | Usar `epps[i][imagenes][j]` |
| WebP no se crea | ImageUploadService falla | Verificar ImageMagick/GD instalado |
| Transacción fallida | Error en cualquier paso | Revisar logs, BD se rollback automático |

---

## 📚 Referencias

**Tablas Relacionadas**:
- `pedidos_produccion` - pedido principal
- `prendas_pedido` - items de ropa
- `prenda_pedido_tallas` - tallas por prenda
- `prenda_pedido_variantes` - variantes (manga, botones)
- `prenda_pedido_colores_telas` - telas y colores
- `prenda_fotos_pedido` - imágenes de prendas
- `prenda_fotos_tela_pedido` - imágenes de telas
- `pedidos_procesos_prenda_detalles` - procesos (bordado, estampado)
- `pedidos_procesos_imagenes` - imágenes de procesos
- `epps` - catálogo de equipos  NUEVA RELACIÓN
- `pedido_epp` - items EPP por pedido  NUEVA TABLA
- `pedido_epp_imagenes` - imágenes de EPPs  NUEVA TABLA

**Servicios Clave**:
- `PedidoWebService` - Crear pedido completo
- `ImageUploadService` - Guardar/convertir imágenes a WebP
- `CrearPedidoCompletoRequest` - Validación FormRequest

---

## Checklist de Finalización

- [x] Frontend separa prendas y epps
- [x] Frontend normaliza ambas estructuras  
- [x] Backend valida prendas y epps
- [x] Backend detecta estructura
- [x] Backend procesa prendas (existente)
- [x] Backend procesa EPPs (nuevo)
- [x] EPPs guardan imágenes en WebP
- [x] Registros en pedido_epp + pedido_epp_imagenes
- [ ] Testing integral (pendiente)
- [ ] Documentación frontend (pendiente)

---

**Última Actualización**: 26 Enero 2026, 14:30 UTC  
**Responsable**: GitHub Copilot AI Assistant
