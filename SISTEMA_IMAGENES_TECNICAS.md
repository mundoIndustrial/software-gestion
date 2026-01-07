# 📸 SISTEMA DE IMÁGENES PARA TÉCNICAS COMBINADAS

## 🎯 RESUMEN RÁPIDO

**Cuando subes imágenes para una técnica:**

1. **El navegador (Frontend)** recoge las imágenes via drag-and-drop
2. **Las envía al servidor** junto con los datos de la prenda (FormData)
3. **El servidor (Backend)** recibe los archivos y:
   - Los convierte a formato WebP (más pequeño y moderno)
   - Crea una miniatura de 300x300
   - Los guarda en `/public/cotizaciones/` en carpetas organizadas
   - Guarda las rutas en la base de datos

---

## 📁 CÓMO SE GUARDAN EN EL DISCO

### **TÉCNICA INDIVIDUAL (ej: Solo BORDADO)**

```
/public/cotizaciones/
└── 5/                              ← ID de la cotización
    └── simple/                     ← Significa: NO ES COMBINADA
        └── bordado/                ← Nombre del tipo de técnica
            ├── a1b2c3d4e5.webp     ← Imagen principal (optimizada)
            ├── thumb_a1b2c3d4e5.webp
            ├── original_a1b2c3d4e5.png
            │
            └── f6g7h8i9j0.webp     ← Si subiste más imágenes
                ├── thumb_f6g7h8i9j0.webp
                └── original_f6g7h8i9j0.jpg
```

**En el navegador se vería:** `/cotizaciones/5/simple/bordado/a1b2c3d4e5.webp`

---

### **TÉCNICA COMBINADA (ej: BORDADO + ESTAMPADO)**

```
/public/cotizaciones/
└── 5/                              ← ID de la cotización
    └── combinada/                  ← Significa: ES UNA TÉCNICA COMBINADA
        └── 1/                      ← Número de grupo combinado
            ├── bordado/            ← Primera técnica del grupo
            │   ├── a1b2c3d4e5.webp
            │   ├── thumb_a1b2c3d4e5.webp
            │   └── original_a1b2c3d4e5.png
            │
            └── estampado/          ← Segunda técnica del grupo
                ├── f6g7h8i9j0.webp
                ├── thumb_f6g7h8i9j0.webp
                └── original_f6g7h8i9j0.jpg
```

**En el navegador se vería:** `/cotizaciones/5/combinada/1/bordado/a1b2c3d4e5.webp`

---

## 💾 ESTRUCTURA EN LA BASE DE DATOS

### Tabla: `logo_cotizacion_tecnica_prendas`
```
id                                  ← ID único de la prenda
logo_cotizacion_id                  ← ID de la cotización
tipo_logo_id                        ← ID del tipo de técnica (bordado, etc)
nombre_prenda                       ← Nombre de la prenda (camiseta, gorra, etc)
observaciones                       ← Notas especiales
ubicaciones                         ← JSON: [hombro, pecho, etc]
talla_cantidad                      ← JSON: [M: 5, L: 10, etc]
grupo_combinado                     ← NULL si es simple, o número si es combinada
```

### Tabla: `logo_cotizacion_tecnica_prendas_fotos`
```
id                                  ← ID de la foto
logo_cotizacion_tecnica_prenda_id   ← ID de la prenda (relación)
ruta_webp                           ← /cotizaciones/5/simple/bordado/abc.webp
ruta_miniatura                      ← /cotizaciones/5/simple/bordado/thumb_abc.webp
ruta_original                       ← /cotizaciones/5/simple/bordado/original_abc.png
orden                               ← 0, 1, 2, 3... (orden en que se mostrará)
ancho                               ← 2000 (píxeles)
alto                                ← 1500 (píxeles)
tamaño                              ← 245632 (bytes)
```

---

## 🔄 FLUJO COMPLETO: Frontend → Backend

### 1️⃣ **Frontend (navegador)** - En `public/js/logo-cotizacion-tecnicas.js`

```javascript
// El usuario arrastra 3 imágenes para un BORDADO individual
// El código las guarda en un array:
const imagenesAgregadas = [
    File { name: "logo1.jpg", size: 250000 },
    File { name: "logo2.png", size: 180000 },
    File { name: "logo3.jpg", size: 220000 }
];

// Cuando hace clic en "Guardar", se crea un FormData:
const formData = new FormData();

formData.append('logo_cotizacion_id', 5);
formData.append('tipo_logo_id', 1);  // ID de BORDADO
formData.append('es_combinada', false);
formData.append('grupo_combinado', null);

// Las prendas (SIN archivos, solo datos):
formData.append('prendas', JSON.stringify([
    {
        nombre_prenda: "Camiseta",
        observaciones: "Pecho izquierdo",
        ubicaciones: ["pecho"],
        talla_cantidad: { "M": 5, "L": 10 }
    }
]));

// Los archivos se agregan por separado:
formData.append('imagenes_prenda_0_0', File { logo1.jpg });
formData.append('imagenes_prenda_0_1', File { logo2.png });
formData.append('imagenes_prenda_0_2', File { logo3.jpg });

// Enviar al servidor:
fetch('/api/logo-cotizacion-tecnicas/agregar', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    body: formData  // ← Multipart form data, NO JSON
});
```

---

### 2️⃣ **Backend (servidor)** - En `LogoCotizacionTecnicaController.php`

```php
// El controller recibe:
// - $request->input('logo_cotizacion_id') = 5
// - $request->input('es_combinada') = false
// - $request->input('grupo_combinado') = null
// - $request->input('prendas') = JSON string con datos
// - $request->file('imagenes_prenda_0_0') = Archivo 1
// - $request->file('imagenes_prenda_0_1') = Archivo 2
// - $request->file('imagenes_prenda_0_2') = Archivo 3

// PASO 1: Crear la prenda en BD
$prenda = LogoCotizacionTecnicaPrenda::create([
    'logo_cotizacion_id' => 5,
    'tipo_logo_id' => 1,
    'nombre_prenda' => 'Camiseta',
    'ubicaciones' => ['pecho'],
    'talla_cantidad' => ['M' => 5, 'L' => 10],
    'grupo_combinado' => null,  // Es simple
]);

// PASO 2: Procesar cada imagen
foreach ($imagenes as $imagen) {
    // Usar TecnicaImagenService para:
    
    // A) Guardar original:
    // /public/cotizaciones/5/simple/bordado/original_a1b2c3d4e5.png
    
    // B) Convertir a WebP y guardar:
    // /public/cotizaciones/5/simple/bordado/a1b2c3d4e5.webp
    
    // C) Crear miniatura y guardar:
    // /public/cotizaciones/5/simple/bordado/thumb_a1b2c3d4e5.webp
    
    // D) Guardar rutas en BD:
    LogoCotizacionTecnicaPrendaFoto::create([
        'logo_cotizacion_tecnica_prenda_id' => $prenda->id,
        'ruta_webp' => 'cotizaciones/5/simple/bordado/a1b2c3d4e5.webp',
        'ruta_miniatura' => 'cotizaciones/5/simple/bordado/thumb_a1b2c3d4e5.webp',
        'ruta_original' => 'cotizaciones/5/simple/bordado/original_a1b2c3d4e5.png',
        'orden' => 0,
        'ancho' => 2000,
        'alto' => 1500,
        'tamaño' => 245632
    ]);
}
```

---

## ⚙️ DIFERENCIA: SIMPLE vs COMBINADA

| Aspecto | SIMPLE | COMBINADA |
|---------|--------|-----------|
| Ruta en disco | `/simple/bordado/` | `/combinada/1/bordado/` |
| grupo_combinado | `null` | `1, 2, 3, ...` |
| Cuándo se usa | Una única técnica | BORDADO + ESTAMPADO juntos |
| Ejemplo | Solo bordado en la camiseta | Bordado en pecho + Estampado en espalda |

---

## 🚀 ARCHIVOS ACTUALIZADOS

### ✅ Created:
- `database/migrations/2026_01_07_create_logo_cotizacion_tecnica_prendas_fotos_table.php`
- `app/Services/TecnicaImagenService.php`
- `app/Models/LogoCotizacionTecnicaPrendaFoto.php`

### ✅ Updated:
- `app/Models/LogoCotizacionTecnicaPrenda.php` (agregó relación con fotos)
- `app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php` (procesa FormData e imágenes)

### ℹ️ No necesita cambios:
- `public/js/logo-cotizacion-tecnicas.js` (ya envía FormData correctamente)

---

## 📊 DIAGRAMA DE FLUJO

```
Usuario sube imágenes en modal
              ↓
Frontend recoge archivos en array
              ↓
Usuario hace click en "Guardar técnica"
              ↓
JavaScript crea FormData con:
  - Metadatos (JSON)
  - Archivos (multipart)
              ↓
POST a /api/logo-cotizacion-tecnicas/agregar
              ↓
Backend recibe FormData
              ↓
Crea LogoCotizacionTecnicaPrenda en BD
              ↓
Para cada imagen:
  - Lee archivo
  - Convierte a WebP
  - Crea miniatura
  - Guarda 3 versiones en disco
  - Guarda rutas en LogoCotizacionTecnicaPrendaFoto
              ↓
Retorna success + IDs creados
              ↓
Frontend actualiza tabla de técnicas
```

---

## 🔍 LOGS QUE VERÁS EN LARAVEL

```
📸 Guardando imagen de técnica
  cotizacion_id: 5
  tipo_logo: bordado
  grupo_combinado: null
  filename: logo1.jpg

✓ Imagen original guardada
  ruta: cotizaciones/5/simple/bordado/original_a1b2c3d4e5.png
  ancho: 3000
  alto: 2250

✅ WebP guardado
  ruta: cotizaciones/5/simple/bordado/a1b2c3d4e5.webp

✅ Miniatura guardada
  ruta: cotizaciones/5/simple/bordado/thumb_a1b2c3d4e5.webp

✅ Técnica agregada completamente
  logo_cotizacion_id: 5
  ruta_almacenamiento: cotizaciones/5/simple/bordado
```

---

## ✨ VENTAJAS DE ESTA ESTRUCTURA

✅ **Claridad**: Fácil saber si es simple o combinada por la ruta
✅ **Organización**: Cada cotización, tipo y grupo en su carpeta
✅ **Eficiencia**: WebP es 30-40% más pequeño que JPG
✅ **Rendimiento**: Miniatura para previsualizaciones rápidas
✅ **Trazabilidad**: 3 versiones guardadas para auditoría
✅ **Escalabilidad**: Estructura preparada para crecer

---

## 🤔 PREGUNTAS FRECUENTES

**P: ¿Dónde veo las imágenes?**
R: En `/public/cotizaciones/` en tu servidor. Por ej: `/public/cotizaciones/5/simple/bordado/abc123.webp`

**P: ¿Por qué 3 versiones de cada imagen?**
R: Como en tu tabla anterior `logo_fotos_cot`:
- Original: por si necesitas el archivo sin procesar
- WebP: para mostrar en el navegador (más rápido)
- Miniatura: para previsualizaciones sin descargar la full

**P: ¿Qué pasa si subo una imagen muy grande?**
R: El servidor la redimensiona automáticamente a máximo 2000x2000 píxeles

**P: ¿Cómo muestro las imágenes en el navegador?**
R: Simplemente usa la ruta: `<img src="/cotizaciones/5/simple/bordado/abc.webp" />`

**P: ¿Si es combinada, ¿qué grupo_combinado le asigno?**
R: El servidor lo asigna automáticamente: 1 para el primer grupo, 2 para el segundo, etc.
