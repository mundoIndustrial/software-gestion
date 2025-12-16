# 📸 ANÁLISIS COMPLETO: URLs Y ALMACENAMIENTO DE IMÁGENES EN PRENDAS DE COTIZACIONES

## 🎯 Resumen Ejecutivo

Las imágenes de prendas en cotizaciones se almacenan en:
- **Locación**: `storage/app/public/cotizaciones/{cotizacion_id}/{tipo}/`
- **Formato**: WebP (conversión automática)
- **Estructura BD**: 3 tablas diferentes según tipo de imagen
- **URLs públicas**: `/storage/cotizaciones/{cotizacion_id}/{tipo}/{nombre}.webp`

---

## 📊 FLUJO COMPLETO DE ALMACENAMIENTO

### 1️⃣ CAPTURA DE IMAGEN EN FRONTEND

**Archivo**: `public/js/asesores/cotizaciones/guardado.js`

```javascript
// Las imágenes se guardan en memoria en window.imagenesEnMemoria
window.imagenesEnMemoria = {
    prendaConIndice: [      // Array de imágenes de prenda
        {
            prendaIndex: 0,  // Índice de la prenda
            file: File,      // Objeto File de JavaScript
            esGuardada: false // Es nueva (no está en BD)
        }
    ],
    telaConIndice: [        // Array de imágenes de tela
        {
            prendaIndex: 0,
            file: File,
            esGuardada: false
        }
    ],
    logo: []                // Array de logos
};
```

**Cómo se captura**:
- Drag & Drop en zonas designadas
- Click en áreas de carga
- Se añade a `window.imagenesEnMemoria` en memoria

---

### 2️⃣ ENVÍO AL SERVIDOR (FormData)

**Archivo**: `public/js/asesores/cotizaciones/guardado.js` (línea 244)

```javascript
async function guardarCotizacion() {
    const formData = new FormData();
    
    // ✅ FOTOS DE PRENDA (File objects o rutas guardadas)
    if (window.imagenesEnMemoria.prendaConIndice) {
        const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice
            .filter(p => p.prendaIndex === index);
        
        fotosDeEstaPrenda.forEach((item, fotoIndex) => {
            if (item.file instanceof File) {
                // Archivo nuevo (aún no guardado)
                formData.append(`prendas[${index}][fotos][]`, item.file);
            } else if (typeof item.file === 'string' && item.esGuardada) {
                // Ruta de imagen ya guardada
                formData.append(`prendas[${index}][fotos_guardadas][]`, item.file);
            }
        });
    }
}
```

**Estructura FormData enviado**:
```
prendas[0][nombre_producto]: "Camisa"
prendas[0][fotos][]: File (binary)
prendas[0][fotos][]: File (binary)
prendas[0][fotos_guardadas][]: "/storage/cotizaciones/37/prenda/..."
```

---

### 3️⃣ PROCESAMIENTO EN BACKEND

#### Controlador: `CotizacionController`

**Ruta**: `POST /asesores/cotizaciones` o `POST /asesores/cotizaciones/{id}`

```php
// 1. Se recibe el FormData
// 2. Se itera sobre los productos
foreach ($request->input('prendas', []) as $index => $productoData) {
    // 3. Se procesan las imágenes de prenda
    $fotos = $productoData['fotos'] ?? [];  // Nuevos archivos
    $fotosGuardadas = $productoData['fotos_guardadas'] ?? [];  // Rutas existentes
    
    // 4. Procesar cada foto
    foreach ($fotos as $foto) {
        // ProcesarImagenesCotizacionService maneja la conversión
        $ruta = $procesarImagenesService->procesarImagenPrenda(
            $foto,                    // UploadedFile
            $cotizacion->id,          // ID de cotización
            $prenda->id               // ID de prenda
        );
        // Resultado: "/storage/cotizaciones/37/prenda/prenda_1_imagen_timestamp_random.webp"
    }
}
```

#### Servicio: `ProcesarImagenesCotizacionService`

**Archivo**: `app/Application/Services/ProcesarImagenesCotizacionService.php`

```php
public function procesarImagenPrenda(
    UploadedFile $archivo, 
    int $cotizacionId, 
    int $prendaId
): string {
    // 1. Validar tipo MIME
    if (!in_array($archivo->getMimeType(), self::TIPOS_PERMITIDOS)) {
        throw new \Exception('Tipo de imagen no permitido');
    }
    
    // 2. Crear carpeta si no existe
    $rutaCarpeta = "storage/cotizaciones/{$cotizacionId}/prendas";
    Storage::disk('public')->makeDirectory($rutaCarpeta, 0755, true);
    
    // 3. Generar nombre único
    $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
    $nombreUnico = $this->generarNombreUnico($nombreOriginal);
    $nombreWebP = $nombreUnico . '.webp';
    
    // 4. Convertir a WebP usando ImageManager (GD)
    $manager = new ImageManager(new GdDriver());
    $imagen = $manager->read($archivo->getRealPath());
    $imagen->toWebp(80)->save($rutaCarpeta . '/' . $nombreWebP);
    
    // 5. Retornar URL pública
    return "/storage/{$rutaCarpeta}/{$nombreWebP}";
}
```

**Estructura de carpeta creada**:
```
storage/app/public/cotizaciones/
└── 37/                              (ID de cotización)
    ├── prendas/
    │   ├── prenda_1_imagen_1702564859_1234.webp
    │   ├── prenda_1_imagen_1702564860_5678.webp
    │   ├── prenda_2_imagen_1702564861_9012.webp
    │   └── ...
    ├── telas/
    │   ├── tela_1_rojo_1702564862_3456.webp
    │   ├── tela_2_azul_1702564863_7890.webp
    │   └── ...
    └── logos/
        └── logo_empresa_1702564864_1011.webp
```

---

### 4️⃣ ALMACENAMIENTO EN BASE DE DATOS

Las imágenes se guardan en **3 tablas diferentes** según su tipo:

#### Tabla 1: `prenda_fotos_cot` (Fotos de Prendas)

```sql
CREATE TABLE prenda_fotos_cot (
    id INT PRIMARY KEY,
    prenda_cot_id INT,           -- FK a prendas_cot
    ruta_original VARCHAR(255),  -- URL original ó ruta convertida
    ruta_webp VARCHAR(255),      -- URL en WebP
    ruta_miniatura VARCHAR(255), -- Miniatura (null generalmente)
    orden INT,                   -- Orden de visualización
    ancho INT,                   -- Ancho en píxeles
    alto INT,                    -- Alto en píxeles
    tamaño INT,                  -- Tamaño en bytes
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Ejemplo real**:
```
id: 1
prenda_cot_id: 5
ruta_original: /storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp
ruta_webp: /storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp
ruta_miniatura: NULL
orden: 1
ancho: 1920
alto: 1080
tamaño: 245000
```

#### Tabla 2: `prenda_tela_fotos_cot` (Fotos de Telas)

```sql
CREATE TABLE prenda_tela_fotos_cot (
    id INT PRIMARY KEY,
    prenda_cot_id INT,           -- FK a prendas_cot
    ruta_original VARCHAR(255),  -- URL de tela
    ruta_webp VARCHAR(255),      -- URL en WebP
    ruta_miniatura VARCHAR(255), -- Miniatura (null)
    orden INT,                   -- Orden
    ancho INT,
    alto INT,
    tamaño INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Ejemplo real**:
```
id: 1
prenda_cot_id: 5
ruta_original: /storage/cotizaciones/37/telas/tela_1_rojo_1702564859_1234.webp
ruta_webp: /storage/cotizaciones/37/telas/tela_1_rojo_1702564859_1234.webp
orden: 1
tamaño: 189000
```

#### Tabla 3: `logo_fotos_cot` (Fotos de Logos)

```sql
CREATE TABLE logo_fotos_cot (
    id INT PRIMARY KEY,
    logo_cotizacion_id INT,  -- FK a logo_cotizacion
    ruta_original VARCHAR(255),
    ruta_webp VARCHAR(255),
    ruta_miniatura VARCHAR(255),
    orden INT,
    ancho INT,
    alto INT,
    tamaño INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔗 RELACIONES DE MODELOS

```php
// PrendaCot (prenda de cotización)
class PrendaCot extends Model {
    // Relación: Una prenda tiene muchas fotos
    public function fotos(): HasMany {
        return $this->hasMany(PrendaFotoCot::class, 'prenda_cot_id');
    }
    
    // Relación: Una prenda tiene muchas fotos de tela
    public function telaFotos(): HasMany {
        return $this->hasMany(PrendaTelaFotoCot::class, 'prenda_cot_id');
    }
}

// PrendaFotoCot (foto individual de prenda)
class PrendaFotoCot extends Model {
    protected $table = 'prenda_fotos_cot';
    
    // Accessor: URL pública de la imagen
    public function getUrlAttribute(): string {
        $ruta = $this->ruta_webp ?? $this->ruta_original;
        // Garantiza que devuelva /storage/...
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        return '/storage/' . ltrim($ruta, '/');
    }
}
```

---

## 🌐 URLs FINALES GENERADAS

### Patrón de URL

```
/storage/cotizaciones/{cotizacion_id}/{tipo}/{nombre}.webp
```

### Ejemplos Reales

```
🖼️ Fotos de Prenda:
  /storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp
  /storage/cotizaciones/37/prendas/prenda_2_imagen_1702564860_5678.webp

🧵 Fotos de Tela:
  /storage/cotizaciones/37/telas/tela_1_rojo_1702564861_9012.webp
  /storage/cotizaciones/37/telas/tela_2_azul_1702564862_3456.webp

🎨 Logos:
  /storage/cotizaciones/37/logos/logo_empresa_1702564863_7890.webp
```

### En HTML/JSON

```html
<img src="/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp" />
```

```json
{
    "id": 5,
    "nombre_producto": "Camisa",
    "fotos": [
        {
            "id": 1,
            "url": "/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp",
            "ruta_webp": "/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp",
            "orden": 1
        }
    ]
}
```

---

## 🔄 CICLO COMPLETO DESDE LA BD

### 1. Obtener Prenda con Fotos

```php
$prenda = PrendaCot::with('fotos')->find(5);

// Acceder a las fotos
foreach ($prenda->fotos as $foto) {
    echo $foto->url;  // Usa el accessor, devuelve URL pública
    // Resultado: /storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp
}
```

### 2. En API (JSON)

```php
return response()->json([
    'prenda' => $prenda->load('fotos')->toArray()
]);

// Respuesta:
{
    "id": 5,
    "nombre_producto": "Camisa",
    "fotos": [
        {
            "id": 1,
            "prenda_cot_id": 5,
            "ruta_original": "/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp",
            "ruta_webp": "/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp",
            "orden": 1,
            "url": "/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp"
        }
    ]
}
```

### 3. En la Vista (Blade)

```blade
@foreach($prenda->fotos as $foto)
    <img src="{{ $foto->url }}" alt="Foto de {{ $prenda->nombre_producto }}">
    <!-- Genera: <img src="/storage/cotizaciones/37/prendas/prenda_1_imagen_1702564859_1234.webp" /> -->
@endforeach
```

---

## 🛡️ SIMBÓLICA DE ARCHIVOS

### Nombres de Archivos

```
prenda_{prenda_id}_{tipo_imagen}_{timestamp}_{random}.webp
tela_{prenda_id}_{nombre_tela}_{timestamp}_{random}.webp
logo_{nombre}_{timestamp}_{random}.webp
```

**Ejemplo**:
- `prenda_1_imagen_1702564859_1234.webp`
  - `prenda_` = tipo
  - `1` = ID de prenda
  - `imagen` = nombre original
  - `1702564859` = timestamp
  - `1234` = hash aleatorio

### Generación de Nombre Único

```php
private function generarNombreUnico(string $nombre): string {
    $timestamp = time();
    $random = substr(md5(uniqid(mt_rand(), true)), 0, 4);
    return "{$nombre}_{$timestamp}_{$random}";
}
```

---

## 📁 ESTRUCTURA EN DISCO

```
storage/
└── app/
    └── public/
        └── cotizaciones/
            ├── 1/
            │   ├── prendas/
            │   │   ├── prenda_1_camisa_1702564859_1234.webp
            │   │   └── prenda_2_pantalon_1702564860_5678.webp
            │   ├── telas/
            │   │   └── tela_1_drill_1702564861_9012.webp
            │   └── logos/
            │       └── logo_empresa_1702564862_3456.webp
            ├── 37/
            │   ├── prendas/
            │   │   ├── prenda_1_imagen_1702564859_1234.webp
            │   │   └── prenda_2_imagen_1702564860_5678.webp
            │   ├── telas/
            │   │   ├── tela_1_rojo_1702564861_9012.webp
            │   │   └── tela_2_azul_1702564862_3456.webp
            │   └── logos/
            │       └── logo_empresa_1702564863_7890.webp
            └── ...
```

---

## ⚙️ CONFIGURACIÓN IMPORTANTE

### Storage Symlink

Para que las imágenes sean accesibles públicamente, debe existir un symlink:

```bash
# Crear symlink (ejecutar una sola vez)
php artisan storage:link

# Esto crea: public/storage → storage/app/public
```

### Acceso Web

```
Physical Path: storage/app/public/cotizaciones/37/prendas/imagen.webp
Web URL:      /storage/cotizaciones/37/prendas/imagen.webp
```

---

## 🔍 DEBUGGING: Cómo Verificar URLs

### En Base de Datos

```sql
SELECT id, prenda_cot_id, ruta_webp, orden 
FROM prenda_fotos_cot 
WHERE prenda_cot_id = 5;
```

### En API Response

```bash
curl http://localhost:8000/api/prendas/5
```

### En Consola del Navegador

```javascript
// Verificar imágenes en memoria
console.log(window.imagenesEnMemoria.prendaConIndice);

// Verificar URL en imagen renderizada
document.querySelector('img').src;  // /storage/cotizaciones/37/prendas/...
```

### En Blade

```blade
@php
    dd($prenda->fotos->map(fn($f) => $f->url));
@endphp
```

---

## 📋 CHECKLIST DE URLS EN PRENDAS

- [x] Imágenes se convierten a WebP automáticamente
- [x] URLs siguen patrón `/storage/cotizaciones/{id}/{tipo}/{archivo}.webp`
- [x] Se almacenan en `storage/app/public/` (no en BD completa)
- [x] URLs relativas se guardan en BD (sin dominio)
- [x] Accessors en modelos garantizan formato correcto
- [x] Se pueden acceder vía symlink de Laravel
- [x] Nombres únicos previenen sobrescrituras
- [x] Estructura de carpetas organizada por cotización y tipo

---

## 🎯 PRÓXIMOS PASOS

Para mejorar este sistema podrías:

1. **Miniaturas**: Generar versión miniatura automáticamente
2. **Caché**: CDN para servir imágenes más rápido
3. **Compresión**: Ajustar calidad WebP según dispositivo
4. **Validación**: Verificar dimensiones mínimas de imagen
5. **Limpieza**: Eliminar imágenes huérfanas periódicamente
