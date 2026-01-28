# ANTES vs DESPUÉS - Imágenes de Procesos

## 🔴 ANTES (El Problema)

### Usuario edita proceso en modal:
```
┌────────────────────────────────┐
│  MODAL: Editar Proceso         │
│  ──────────────────────────────│
│                                │
│  [Ubicaciones]                 │
│  ☑ Pecho                       │
│  ☑ Manga                       │
│                                │
│  [Imágenes]                    │
│  📁 Seleccionar archivo        │
│  [Referencia.jpg]              │
│  ✓ Imagen cargada             │
│                                │
│  [Guardar cambios]             │
└────────────────────────────────┘
```

### Frontend envía PATCH:
```javascript
// ❌ PROBLEMA: JSON puro, sin archivos
fetch(`/api/prendas-pedido/3472/procesos/113`, {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json'  // ❌ JSON
    },
    body: JSON.stringify({
        ubicaciones: ["Pecho", "Manga"],
        observaciones: "...",
        imagenes: []  // ❌ Solo strings/URLs, no archivos
    })
});
```

### Backend recibe:
```json
{
    "ubicaciones": ["Pecho", "Manga"],
    "observaciones": "...",
    "imagenes": []
}

// ❌ No hay archivos en FormData
// request.hasFile('imagenes_nuevas') === false
```

### Resultado en BD:
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113;

-- ❌ Sin registros nuevos
-- Las imágenes no se guardan
```

### Log muestra:
```
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"raw_imagenes":[],"total_recibidas":0}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":0}
```

### Usuario ve en factura:
```
Proceso: Reflectivo
├─ Ubicaciones: Pecho, Manga ✓
├─ Observaciones: ... ✓
└─ Imágenes: [vacío] ❌ NO HAY IMÁGENES
```

---

## 🟢 DESPUÉS (La Solución)

### Usuario edita proceso en modal:
```
┌────────────────────────────────┐
│  MODAL: Editar Proceso         │
│  ──────────────────────────────│
│                                │
│  [Ubicaciones]                 │
│  ☑ Pecho                       │
│  ☑ Manga                       │
│                                │
│  [Imágenes]                    │
│  📁 Seleccionar archivo        │
│  [Referencia.jpg]  [Preview]   │
│  ✓ Imagen cargada en memoria  │
│  window.imagenesProcesoActual  │
│  = [File, null, null]         │
│                                │
│  [Guardar cambios]             │
└────────────────────────────────┘
```

### Frontend envía PATCH:
```javascript
// ✅ SOLUCIÓN: FormData con archivos
const patchFormData = new FormData();

// Agregar campos de cambios como JSON strings
patchFormData.append('ubicaciones', '["Pecho", "Manga"]');
patchFormData.append('observaciones', '...');
patchFormData.append('imagenes', '[]');

// ✅ NUEVO: Agregar archivos reales
if (window.imagenesProcesoActual) {
    window.imagenesProcesoActual.forEach((img, idx) => {
        if (img instanceof File) {
            patchFormData.append(`imagenes_nuevas[${idx}]`, img);
            // imagenes_nuevas[0]: <File object>
        }
    });
}

fetch(`/api/prendas-pedido/3472/procesos/113`, {
    method: 'PATCH',
    headers: {
        // ✅ Sin Content-Type: multipart/form-data
        // El navegador lo pone automáticamente
    },
    body: patchFormData
});
```

### Backend recibe:
```
PATCH /api/prendas-pedido/3472/procesos/113
Content-Type: multipart/form-data

Multipart form data:
├─ ubicaciones: "["Pecho", "Manga"]"
├─ observaciones: "..."
├─ imagenes: "[]"
└─ imagenes_nuevas[0]: <File: Referencia.jpg>  ✅ ARCHIVO

// ✅ request.hasFile('imagenes_nuevas') === true
```

### Backend procesa:
```php
// ✅ NUEVO: Extraer y procesar archivos
if ($request->hasFile('imagenes_nuevas')) {
    foreach ($request->file('imagenes_nuevas') as $imagen) {
        $rutas = $procesoFotoService->procesarFoto($imagen);
        // Convierte a WebP automáticamente
        // Retorna: ['ruta_webp' => 'procesos/proceso_20260127212136_964920.webp']
        $imagenesNuevasRutas[] = $rutas['ruta_webp'];
    }
}

// ✅ NUEVO: Mergear con existentes
$imagenesFinales = array_merge($imagenesJSON, $imagenesNuevasRutas);
// ['procesos/proceso_20260127212136_964920.webp']

// Guardar en BD
foreach ($imagenesFinales as $ruta) {
    DB::table('pedidos_procesos_imagenes')->insert([
        'proceso_prenda_detalle_id' => 113,
        'ruta_webp' => $ruta,
        'orden' => 1,
        'created_at' => now()
    ]);
}
```

### Resultado en BD:
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113;

-- ✅ Registro nuevo creado
-- proceso_prenda_detalle_id: 113
-- ruta_webp: procesos/proceso_20260127212136_964920.webp
-- created_at: 2026-01-28 02:21:38
-- updated_at: 2026-01-28 02:21:38
```

### Log muestra:
```
[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"total_recibidas":1}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":1,"rutas":["procesos/..."]}
```

### Usuario ve en factura:
```
Proceso: Reflectivo
├─ Ubicaciones: Pecho, Manga ✓
├─ Observaciones: ... ✓
└─ Imágenes: ✓ APARECE LA IMAGEN
   └─ [referencia.jpg - 500x500px]
```

---

## 📊 Comparativa Detallada

| Aspecto | ANTES ❌ | DESPUÉS ✅ |
|---------|----------|----------|
| **Tipo de envío** | JSON puro | FormData (multipart) |
| **Content-Type** | application/json | multipart/form-data |
| **Archivos incluidos** | ❌ No | ✅ Sí |
| **Rutas procesadas** | 0 | N (cantidad de archivos) |
| **Registros en BD** | 0 | N |
| **Aparece en factura** | ❌ No | ✅ Sí |
| **Log: total_recibidas** | 0 | N |
| **Log: cantidad agregada** | 0 | N |

---

## 🔄 Flujo Comparativo

### ANTES (Incorrecto)
```
┌─────────────┐
│  Usuario    │  Carga imagen
│  en Modal   │  en memoria
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│  window.imagenes │
│  ProcesoActual   │  = [File]
│  (Tiene archivo) │
└──────┬───────────┘
       │
       ▼
┌──────────────────────┐
│  registrarCambios()  │  Normaliza a string
│                      │  (pierde el archivo)
└──────┬───────────────┘
       │
       ▼
┌────────────────────────────────────┐
│  JSON.stringify({                  │
│    imagenes: []  ❌ Vacío          │
│  })                                │
└──────┬─────────────────────────────┘
       │
       ▼
┌────────────────┐
│  Backend recibe│
│  JSON sin      │
│  archivos ❌   │
└──────┬─────────┘
       │
       ▼
┌────────────────┐
│  BD vacía ❌   │
│  (0 imágenes)  │
└────────────────┘
```

### DESPUÉS (Correcto)
```
┌─────────────┐
│  Usuario    │  Carga imagen
│  en Modal   │  en memoria
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│  window.imagenes │
│  ProcesoActual   │  = [File] ✅
│  (Tiene archivo) │
└──────┬───────────┘
       │
       ▼
┌───────────────────────────────┐
│  Construir FormData:          │
│  - cambios: JSON string       │
│  - imagenes_nuevas[0]: File ✅
└──────┬────────────────────────┘
       │
       ▼
┌─────────────────────────────────┐
│  PATCH + FormData               │
│  headers: (sin Content-Type)    │
│  body: FormData con File ✅     │
└──────┬──────────────────────────┘
       │
       ▼
┌──────────────────────────────┐
│  Backend recibe:             │
│  request.hasFile(imagenes)✅ │
│  Procesa con                 │
│  ProcesoFotoService ✅       │
└──────┬───────────────────────┘
       │
       ▼
┌────────────────────────────┐
│  BD con imagen nueva ✅    │
│  (1 registro creado)       │
│  ruta_webp: .../file.webp │
└────────────────────────────┘
```

---

## 💡 Key Differences

### JavaScript (Frontend)

**ANTES:**
```javascript
body: JSON.stringify(cambios)
// {
//   "imagenes": []  ← Pierde el archivo
// }
```

**DESPUÉS:**
```javascript
body: patchFormData
// FormData {
//   "ubicaciones": "..."
//   "imagenes_nuevas[0]": <File>  ← Archivo incluido
// }
```

### PHP (Backend)

**ANTES:**
```php
$data = $request->all();
// Solo tiene datos JSON, sin archivos
$validated['imagenes'] = [];  // Vacío
```

**DESPUÉS:**
```php
if ($request->hasFile('imagenes_nuevas')) {  // ✅ Detecta archivo
    $files = $request->file('imagenes_nuevas');
    foreach ($files as $imagen) {
        $procesoFotoService->procesarFoto($imagen);
    }
}
```

---

## ✨ Impacto

### Antes
```
❌ Usuario sube imagen
❌ No se guarda
❌ No aparece en factura
❌ Usuario sin feedback visual
```

### Después
```
✅ Usuario sube imagen
✅ Se guarda en BD
✅ Aparece en factura inmediatamente
✅ Usuario ve las imágenes en recibo
✅ Puede editar múltiples veces
```
